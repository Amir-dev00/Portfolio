<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\BookingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingAdminController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = Booking::query()->orderByDesc('starts_at');
        if (in_array($status, ['confirmed', 'cancelled', 'completed'], true)) {
            $q->where('status', $status);
        }
        if ($search = trim((string) $request->query('q'))) {
            $q->where(function ($inner) use ($search) {
                $inner->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_email', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        return view('admin.bookings.index', [
            'bookings' => $q->paginate(25)->withQueryString(),
            'settings' => BookingSettings::get(),
            'status' => $status,
            'q' => $search ?? '',
            'upcoming' => Booking::query()->where('status', 'confirmed')->where('starts_at', '>=', now())->count(),
        ]);
    }

    public function settings(): View
    {
        return view('admin.bookings.settings', [
            'settings' => BookingSettings::get(),
            'rules' => AvailabilityRule::query()->orderBy('weekday')->orderBy('sort_order')->get(),
            'blocks' => BlockedSlot::query()->orderByDesc('starts_at')->limit(100)->get(),
            'weekdays' => $this->weekdayLabels(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'duration' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer' => ['required', 'integer', 'min:0', 'max:240'],
            'min_notice' => ['required', 'integer', 'min:0', 'max:10080'],
            'max_days' => ['required', 'integer', 'min:1', 'max:365'],
            'timezone' => ['required', 'timezone'],
            'slot_interval' => ['required', 'integer', 'min:5', 'max:240'],
            'owner_notify' => ['nullable', 'boolean'],
            'visitor_notify' => ['nullable', 'boolean'],
        ]);
        $data['enabled'] = $request->boolean('enabled');
        $data['owner_notify'] = $request->boolean('owner_notify');
        $data['visitor_notify'] = $request->boolean('visitor_notify');
        BookingSettings::put($data);

        return back()->with('status', 'Booking settings saved. Public availability updates immediately.');
    }

    public function saveAvailability(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rules' => ['nullable', 'array'],
            'rules.*.weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i'],
            'rules.*.is_active' => ['nullable', 'boolean'],
        ]);

        AvailabilityRule::query()->delete();
        foreach ($data['rules'] ?? [] as $i => $rule) {
            if (($rule['start_time'] ?? '') >= ($rule['end_time'] ?? '')) {
                continue;
            }
            AvailabilityRule::query()->create([
                'weekday' => (int) $rule['weekday'],
                'start_time' => $rule['start_time'].':00',
                'end_time' => $rule['end_time'].':00',
                'is_active' => array_key_exists('is_active', $rule) ? (bool) $rule['is_active'] : true,
                'sort_order' => $i,
            ]);
        }

        return back()->with('status', 'Weekly availability updated.');
    }

    public function storeBlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'all_day' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);
        $tz = BookingSettings::get()['timezone'] ?: 'Asia/Tehran';
        $allDay = $request->boolean('all_day');
        if ($allDay) {
            $starts = CarbonImmutable::createFromFormat('Y-m-d', $data['date'], $tz)->startOfDay()->utc();
            $ends = CarbonImmutable::createFromFormat('Y-m-d', $data['date'], $tz)->endOfDay()->utc();
        } else {
            $request->validate([
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            ]);
            $starts = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['start_time'], $tz)->utc();
            $ends = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['end_time'], $tz)->utc();
        }
        BlockedSlot::query()->create([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'all_day' => $allDay,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Blocked slot added.');
    }

    public function destroyBlock(BlockedSlot $block): RedirectResponse
    {
        $block->delete();

        return back()->with('status', 'Blocked slot removed.');
    }

    public function show(Booking $booking): View
    {
        return view('admin.bookings.show', [
            'booking' => $booking,
            'settings' => BookingSettings::get(),
        ]);
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->bookings->cancel($booking);

        return back()->with('status', 'Booking cancelled. The slot is available again.');
    }

    public function complete(Booking $booking): RedirectResponse
    {
        $this->bookings->markCompleted($booking);

        return back()->with('status', 'Booking marked completed.');
    }

    public function exportCsv(): Response
    {
        $rows = Booking::query()->orderByDesc('starts_at')->get();
        $lines = ['reference,name,email,phone,topic,starts_at_utc,ends_at_utc,timezone,status,created_at'];
        foreach ($rows as $b) {
            $lines[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', [
                $b->reference,
                $b->visitor_name,
                $b->visitor_email,
                $b->visitor_phone,
                $b->topic,
                $b->starts_at,
                $b->ends_at,
                $b->timezone,
                $b->status,
                $b->created_at,
            ]));
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="bookings-'.now()->format('Ymd').'.csv"',
        ]);
    }

    protected function weekdayLabels(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }
}
