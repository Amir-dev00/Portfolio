<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BookingService
{
    public function __construct(private AvailabilityService $availability) {}

    /**
     * Atomically reserve a slot. Throws ValidationException on conflict.
     *
     * @param  array{name:string,email:string,phone?:?string,topic?:?string,message?:?string,starts_at:string,timezone:string}  $input
     */
    public function create(array $input, ?string $ip = null): Booking
    {
        $settings = BookingSettings::get();
        if (! ($settings['enabled'] ?? true)) {
            throw ValidationException::withMessages([
                'booking' => ['Online booking is currently disabled.'],
            ]);
        }

        $startsAt = CarbonImmutable::parse($input['starts_at'])->utc();
        $endsAt = $startsAt->addMinutes((int) $settings['duration']);
        $timezone = $input['timezone'] ?: $settings['timezone'];

        return DB::transaction(function () use ($input, $ip, $startsAt, $endsAt, $timezone, $settings) {
            $buffer = (int) $settings['buffer'];
            // Serialize concurrent writers for the overlapping window.
            Booking::query()
                ->whereIn('status', ['confirmed', 'completed'])
                ->where('starts_at', '<', $endsAt->addMinutes($buffer))
                ->where('ends_at', '>', $startsAt->subMinutes($buffer))
                ->lockForUpdate()
                ->get();

            if (! $this->availability->isSlotOpen($startsAt, $endsAt)) {
                throw ValidationException::withMessages([
                    'starts_at' => ['That time was just taken. Please choose another slot.'],
                ]);
            }

            $booking = Booking::query()->create([
                'reference' => $this->uniqueReference(),
                'visitor_name' => trim($input['name']),
                'visitor_email' => strtolower(trim($input['email'])),
                'visitor_phone' => filled($input['phone'] ?? null) ? trim((string) $input['phone']) : null,
                'topic' => filled($input['topic'] ?? null) ? trim((string) $input['topic']) : null,
                'message' => filled($input['message'] ?? null) ? trim((string) $input['message']) : null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'timezone' => $timezone,
                'status' => 'confirmed',
                'cancel_token' => Str::random(48),
                'ip_hash' => $ip ? hash('sha256', $ip) : null,
            ]);

            $this->maybeNotify($booking, $settings);

            return $booking;
        });
    }

    public function cancel(Booking $booking, string $reason = 'cancelled'): Booking
    {
        if ($booking->status === 'cancelled') {
            return $booking;
        }
        $booking->status = 'cancelled';
        $booking->save();

        return $booking;
    }

    public function markCompleted(Booking $booking): Booking
    {
        $booking->status = 'completed';
        $booking->save();

        return $booking;
    }

    public function ics(Booking $booking): string
    {
        $uid = $booking->reference.'@portfolio.local';
        $stamp = CarbonImmutable::now('UTC')->format('Ymd\THis\Z');
        $start = CarbonImmutable::parse($booking->starts_at)->utc()->format('Ymd\THis\Z');
        $end = CarbonImmutable::parse($booking->ends_at)->utc()->format('Ymd\THis\Z');
        $summary = $this->escapeIcs('Call with Amirhossein Zamanzade');
        $desc = $this->escapeIcs(($booking->topic ?: 'Book a Call').' · Ref '.$booking->reference);
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AZ Portfolio//Book a Call//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$stamp,
            'DTSTART:'.$start,
            'DTEND:'.$end,
            'SUMMARY:'.$summary,
            'DESCRIPTION:'.$desc,
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    protected function uniqueReference(): string
    {
        do {
            $ref = strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (Booking::query()->where('reference', $ref)->exists());

        return $ref;
    }

    protected function escapeIcs(string $value): string
    {
        return addcslashes($value, ",;\\");
    }

    protected function maybeNotify(Booking $booking, array $settings): void
    {
        $mailConfigured = filled(config('mail.mailers.smtp.host')) || config('mail.default') === 'log';
        if (! $mailConfigured) {
            return;
        }

        try {
            $ownerEmail = SiteSetting::query()->where('key', 'social')->value('value')['email'] ?? null;
            if (($settings['owner_notify'] ?? true) && filled($ownerEmail)) {
                Mail::raw(
                    "New booking {$booking->reference}\n{$booking->visitor_name} <{$booking->visitor_email}>\n"
                    .CarbonImmutable::parse($booking->starts_at)->timezone($settings['timezone'])->toDayDateTimeString()
                    ."\n".($booking->topic ?: '—'),
                    function ($message) use ($ownerEmail, $booking) {
                        $message->to($ownerEmail)->subject('New booking · '.$booking->reference);
                    }
                );
            }
            if ($settings['visitor_notify'] ?? true) {
                Mail::raw(
                    "Your call is confirmed.\nReference: {$booking->reference}\n"
                    .CarbonImmutable::parse($booking->starts_at)->timezone($booking->timezone)->toDayDateTimeString()
                    .' ('.$booking->timezone.')',
                    function ($message) use ($booking) {
                        $message->to($booking->visitor_email)->subject('Booking confirmed · '.$booking->reference);
                    }
                );
            }
        } catch (Throwable $e) {
            Log::warning('booking.mail_failed', ['reference' => $booking->reference, 'error' => $e->getMessage()]);
        }
    }
}
