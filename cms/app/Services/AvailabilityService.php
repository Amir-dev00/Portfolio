<?php

namespace App\Services;

use App\Models\AvailabilityRule;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Support\Jalali;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class AvailabilityService
{
    public function settings(): array
    {
        return BookingSettings::get();
    }

    public function ownerTz(): string
    {
        return $this->settings()['timezone'] ?: 'Asia/Tehran';
    }

    /**
     * Month availability for the public calendar.
     *
     * @return array{year:int,month:int,calendar:string,days:array<int,array>,weekdays:array,month_label:string}
     */
    public function monthGrid(int $year, int $month, string $calendar = 'gregorian', string $locale = 'en'): array
    {
        $calendar = $calendar === 'jalali' ? 'jalali' : 'gregorian';
        $settings = $this->settings();
        $tz = $this->ownerTz();

        if ($calendar === 'jalali') {
            [$gy, $gm, $gd] = Jalali::toGregorian($year, $month, 1);
            $start = CarbonImmutable::create($gy, $gm, $gd, 0, 0, 0, $tz);
            $daysInMonth = $this->jalaliDaysInMonth($year, $month);
            [$egy, $egm, $egd] = Jalali::toGregorian($year, $month, $daysInMonth);
            $end = CarbonImmutable::create($egy, $egm, $egd, 23, 59, 59, $tz);
            $monthLabel = Jalali::monthName($month, $locale).' '.Jalali::digits((string) $year, $locale);
            $weekStartsOnSaturday = true;
        } else {
            $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $tz);
            $end = $start->endOfMonth();
            $daysInMonth = $start->daysInMonth;
            $monthLabel = $start->locale($locale === 'fa' ? 'fa' : 'en')->translatedFormat('F Y');
            $weekStartsOnSaturday = $locale === 'fa';
        }

        $rules = AvailabilityRule::query()->where('is_active', true)->orderBy('sort_order')->get()->groupBy('weekday');
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if ($calendar === 'jalali') {
                [$gy, $gm, $gd] = Jalali::toGregorian($year, $month, $d);
                $day = CarbonImmutable::create($gy, $gm, $gd, 12, 0, 0, $tz);
            } else {
                $day = CarbonImmutable::create($year, $month, $d, 12, 0, 0, $tz);
            }
            $iso = $day->toDateString();
            $slots = $this->slotsForDate($iso, $settings, $rules);
            $days[] = [
                'day' => $d,
                'date' => $iso,
                'available' => count($slots) > 0,
                'weekday' => (int) $day->format('w'),
                'label' => Jalali::digits((string) $d, $locale),
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar,
            'month_label' => $monthLabel,
            'weekdays' => $weekStartsOnSaturday
                ? Jalali::weekdayNames($locale === 'fa' ? 'fa' : 'en')
                : ($locale === 'fa'
                    ? ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش']
                    : ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']),
            'week_starts_on' => $weekStartsOnSaturday ? 6 : 0,
            'days' => $days,
            'today' => CarbonImmutable::now($tz)->toDateString(),
        ];
    }

    /**
     * @return list<array{start:string,end:string,label:string,start_utc:string,end_utc:string}>
     */
    public function publicSlots(string $dateYmd, ?string $visitorTz = null): array
    {
        $settings = $this->settings();
        if (! ($settings['enabled'] ?? true)) {
            return [];
        }
        $rules = AvailabilityRule::query()->where('is_active', true)->orderBy('sort_order')->get()->groupBy('weekday');
        $slots = $this->slotsForDate($dateYmd, $settings, $rules);
        $displayTz = $visitorTz ?: $this->ownerTz();

        return array_map(function (array $slot) use ($displayTz) {
            $start = CarbonImmutable::parse($slot['start_utc'])->timezone($displayTz);
            $end = CarbonImmutable::parse($slot['end_utc'])->timezone($displayTz);

            return [
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'label' => $start->format('H:i'),
                'start_utc' => $slot['start_utc'],
                'end_utc' => $slot['end_utc'],
            ];
        }, $slots);
    }

    public function isSlotOpen(CarbonImmutable $startsAtUtc, CarbonImmutable $endsAtUtc, ?int $ignoreBookingId = null): bool
    {
        $settings = $this->settings();
        $tz = $this->ownerTz();
        $localStart = $startsAtUtc->timezone($tz);
        $date = $localStart->toDateString();
        $rules = AvailabilityRule::query()->where('is_active', true)->orderBy('sort_order')->get()->groupBy('weekday');
        $open = $this->slotsForDate($date, $settings, $rules, $ignoreBookingId);

        foreach ($open as $slot) {
            if ($slot['start_utc'] === $startsAtUtc->utc()->toIso8601String()
                && $slot['end_utc'] === $endsAtUtc->utc()->toIso8601String()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int,Collection<int,AvailabilityRule>>  $rulesByWeekday
     * @return list<array{start_utc:string,end_utc:string}>
     */
    protected function slotsForDate(string $dateYmd, array $settings, Collection $rulesByWeekday, ?int $ignoreBookingId = null): array
    {
        $tz = $settings['timezone'] ?: 'Asia/Tehran';
        $day = CarbonImmutable::createFromFormat('Y-m-d', $dateYmd, $tz)->startOfDay();
        $now = CarbonImmutable::now('UTC');
        $minStart = $now->addMinutes((int) $settings['min_notice']);
        $maxDay = CarbonImmutable::now($tz)->startOfDay()->addDays((int) $settings['max_days']);

        if ($day->lt(CarbonImmutable::now($tz)->startOfDay()) || $day->gt($maxDay)) {
            return [];
        }

        $weekday = (int) $day->format('w');
        /** @var Collection<int,AvailabilityRule> $windows */
        $windows = $rulesByWeekday->get($weekday, collect());
        if ($windows->isEmpty()) {
            return [];
        }

        $duration = (int) $settings['duration'];
        $buffer = (int) $settings['buffer'];
        $interval = (int) $settings['slot_interval'];
        $busy = $this->busyIntervals($day, $ignoreBookingId);

        $slots = [];
        foreach ($windows as $window) {
            $winStart = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd.' '.$this->normalizeTime($window->start_time), $tz);
            $winEnd = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd.' '.$this->normalizeTime($window->end_time), $tz);
            for ($cursor = $winStart; $cursor->addMinutes($duration)->lte($winEnd); $cursor = $cursor->addMinutes($interval)) {
                $slotStart = $cursor;
                $slotEnd = $cursor->addMinutes($duration);
                if ($slotEnd->gt($winEnd)) {
                    break;
                }
                $startUtc = $slotStart->utc();
                $endUtc = $slotEnd->utc();
                if ($startUtc->lt($minStart)) {
                    continue;
                }
                if ($this->overlapsAny($startUtc, $endUtc->addMinutes($buffer), $busy)) {
                    continue;
                }
                $slots[] = [
                    'start_utc' => $startUtc->toIso8601String(),
                    'end_utc' => $endUtc->toIso8601String(),
                ];
            }
        }

        return $slots;
    }

    /**
     * @return list<array{0:CarbonImmutable,1:CarbonImmutable}>
     */
    protected function busyIntervals(CarbonImmutable $dayLocal, ?int $ignoreBookingId = null): array
    {
        $tz = $this->ownerTz();
        $dayStart = $dayLocal->startOfDay()->utc();
        $dayEnd = $dayLocal->endOfDay()->utc();
        $settings = $this->settings();
        $buffer = (int) $settings['buffer'];

        $busy = [];
        $bookings = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at']);

        foreach ($bookings as $booking) {
            $busy[] = [
                CarbonImmutable::parse($booking->starts_at)->utc(),
                CarbonImmutable::parse($booking->ends_at)->utc()->addMinutes($buffer),
            ];
        }

        $blocks = BlockedSlot::query()
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at']);

        foreach ($blocks as $block) {
            $busy[] = [
                CarbonImmutable::parse($block->starts_at)->utc(),
                CarbonImmutable::parse($block->ends_at)->utc(),
            ];
        }

        return $busy;
    }

    /** @param list<array{0:CarbonImmutable,1:CarbonImmutable}> $busy */
    protected function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, array $busy): bool
    {
        foreach ($busy as [$bStart, $bEnd]) {
            if ($start->lt($bEnd) && $end->gt($bStart)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeTime(mixed $time): string
    {
        $raw = (string) $time;
        if (preg_match('/^\d{2}:\d{2}$/', $raw)) {
            return $raw.':00';
        }

        return strlen($raw) >= 8 ? substr($raw, 0, 8) : $raw;
    }

    protected function jalaliDaysInMonth(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }

        // Esfand: 29 or 30 (leap).
        [, , $gd29] = Jalali::toGregorian($jy, 12, 29);
        [, , $gd30] = Jalali::toGregorian($jy, 12, 30);
        // If 30 Esfand maps to a valid next-day progression vs 29, accept 30.
        [$gy29, $gm29] = Jalali::toGregorian($jy, 12, 29);
        [$gy30, $gm30, $d30] = Jalali::toGregorian($jy, 12, 30);
        $a = Carbon::create($gy29, $gm29, $gd29)->addDay();
        $b = Carbon::create($gy30, $gm30, $d30);

        return $a->equalTo($b) ? 30 : 29;
    }
}
