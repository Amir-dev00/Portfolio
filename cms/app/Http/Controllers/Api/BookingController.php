<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\BookingSettings;
use App\Support\Jalali;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private AvailabilityService $availability,
        private BookingService $bookings,
    ) {}

    public function config(Request $request): JsonResponse
    {
        $locale = $request->query('locale') === 'fa' ? 'fa' : 'en';

        return response()->json($this->availability->bootstrap($locale)['config']);
    }

    /** Config + initial month in one round-trip for first paint. */
    public function bootstrap(Request $request): JsonResponse
    {
        $locale = $request->query('locale') === 'fa' ? 'fa' : 'en';

        return response()->json($this->availability->bootstrap($locale));
    }

    public function month(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1300', 'max:2200'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'calendar' => ['nullable', 'in:gregorian,jalali'],
            'locale' => ['nullable', 'in:en,fa'],
        ]);
        $locale = ($data['locale'] ?? 'en') === 'fa' ? 'fa' : 'en';
        $calendar = ($data['calendar'] ?? null) ?: ($locale === 'fa' ? 'jalali' : 'gregorian');

        return response()->json(
            $this->availability->monthGrid((int) $data['year'], (int) $data['month'], $calendar, $locale)
        );
    }

    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'tz' => ['nullable', 'timezone'],
        ]);

        return response()->json([
            'date' => $data['date'],
            'slots' => $this->availability->publicSlots($data['date'], $data['tz'] ?? null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $key = 'booking-create:'.($request->ip() ?: 'unknown');
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json(['message' => 'Too many attempts. Please wait a minute.'], 429);
        }
        RateLimiter::hit($key, 60);

        // Honeypot — bots fill "company_website".
        if (filled($request->input('company_website'))) {
            return response()->json([
                'ok' => true,
                'booking' => [
                    'reference' => 'OK',
                    'starts_at' => CarbonImmutable::now()->toIso8601String(),
                ],
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'topic' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'timezone' => ['required', 'timezone'],
        ]);

        try {
            $booking = $this->bookings->create($data, $request->ip());
        } catch (ValidationException $e) {
            throw $e;
        }

        $settings = BookingSettings::get();
        $local = CarbonImmutable::parse($booking->starts_at)->timezone($booking->timezone);
        $ownerLocal = CarbonImmutable::parse($booking->starts_at)->timezone($settings['timezone'] ?: 'Asia/Tehran');

        return response()->json([
            'ok' => true,
            'booking' => [
                'reference' => $booking->reference,
                'starts_at' => CarbonImmutable::parse($booking->starts_at)->utc()->toIso8601String(),
                'ends_at' => CarbonImmutable::parse($booking->ends_at)->utc()->toIso8601String(),
                'timezone' => $booking->timezone,
                'duration' => (int) $settings['duration'],
                'local_date' => $local->toDateString(),
                'local_time' => $local->format('H:i'),
                'owner_date' => $ownerLocal->toDateString(),
                'owner_time' => $ownerLocal->format('H:i'),
                'email_notified' => $this->bookings->visitorEmailSent(),
                'ics_url' => url('/api/booking/'.$booking->reference.'/ics'),
            ],
        ], 201);
    }

    public function ics(string $reference): Response
    {
        $booking = Booking::query()->where('reference', $reference)->where('status', 'confirmed')->firstOrFail();
        $body = $this->bookings->ics($booking);

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="booking-'.$booking->reference.'.ics"',
        ]);
    }

    /** Helper for the frontend when switching calendars. */
    public function convert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'in:gregorian,jalali'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'day' => ['required', 'integer', 'min:1', 'max:31'],
        ]);
        if ($data['from'] === 'gregorian') {
            [$y, $m, $d] = Jalali::toJalali((int) $data['year'], (int) $data['month'], (int) $data['day']);

            return response()->json(['calendar' => 'jalali', 'year' => $y, 'month' => $m, 'day' => $d]);
        }
        [$y, $m, $d] = Jalali::toGregorian((int) $data['year'], (int) $data['month'], (int) $data['day']);

        return response()->json(['calendar' => 'gregorian', 'year' => $y, 'month' => $m, 'day' => $d]);
    }
}
