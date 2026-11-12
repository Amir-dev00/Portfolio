<?php

namespace Tests\Feature;

use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\SiteSetting;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class BookingOwnerSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::query()->updateOrCreate(
            ['key' => 'booking'],
            ['value' => [
                'enabled' => true,
                'duration' => 30,
                'buffer' => 0,
                'min_notice' => 0,
                'max_days' => 60,
                'timezone' => 'Asia/Tehran',
                'slot_interval' => 30,
                'owner_notify' => false,
                'visitor_notify' => false,
            ]]
        );

        foreach ([6, 0, 1, 2, 3] as $i => $weekday) {
            AvailabilityRule::query()->create([
                'weekday' => $weekday,
                'start_time' => '10:00:00',
                'end_time' => '13:00:00',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        config([
            'smsir.enabled' => true,
            'smsir.api_key' => 'test-api-key',
            'smsir.line_number' => '30004505000017',
            'smsir.owner_mobile' => '09121234567',
            'smsir.timeout' => 5,
            'smsir.base_url' => 'https://api.sms.ir',
            'mail.default' => 'array',
        ]);
    }

    public function test_confirmed_booking_sends_one_sms_to_owner_mobile(): void
    {
        Http::fake([
            'api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => [
                    'packId' => 'pack-1',
                    'messageIds' => ['881122'],
                    'cost' => 1.0,
                ],
            ], 200),
        ]);

        $booking = $this->createBooking();

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($booking) {
            $data = $request->data();
            $local = $booking->starts_at->timezone('Asia/Tehran')->format('Y-m-d H:i');
            $expected = $booking->reference."\n".$local;

            return $request->url() === 'https://api.sms.ir/v1/send/bulk'
                && $request->hasHeader('X-API-KEY', 'test-api-key')
                && (int) $data['lineNumber'] === 30004505000017
                && $data['mobiles'] === ['09121234567']
                && $data['messageText'] === $expected
                && ! str_contains($data['messageText'], '@')
                && ! str_contains(strtolower($data['messageText']), 'visitor');
        });

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('accepted', $booking->owner_sms_status);
        $this->assertSame('881122', $booking->owner_sms_provider_message_id);
        $this->assertNull($booking->owner_sms_error);
    }

    public function test_accepted_sms_is_not_sent_twice_for_same_booking(): void
    {
        Http::fake([
            'api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageIds' => ['9911'], 'cost' => 1.0],
            ], 200),
        ]);

        $booking = $this->createBooking();
        Http::assertSentCount(1);
        $this->assertSame('accepted', $booking->fresh()->owner_sms_status);

        Http::fake([
            'api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageIds' => ['9912'], 'cost' => 1.0],
            ], 200),
        ]);

        $method = new ReflectionMethod(BookingService::class, 'maybeNotifyOwnerSms');
        $method->invoke(app(BookingService::class), $booking->fresh());

        Http::assertNothingSent();
        $this->assertSame('accepted', $booking->fresh()->owner_sms_status);
        $this->assertSame('9911', $booking->fresh()->owner_sms_provider_message_id);
    }

    public function test_provider_failure_keeps_booking_and_records_rejected(): void
    {
        Http::fake([
            'api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 10,
                'message' => 'اعتبار کافی نیست',
                'data' => null,
            ], 200),
        ]);

        $booking = $this->createBooking();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'owner_sms_status' => 'rejected',
        ]);
        $this->assertNotNull($booking->fresh()->owner_sms_error);
        Http::assertSentCount(1);
    }

    public function test_timeout_is_uncertain_and_is_not_retried(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $booking = $this->createBooking();
        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('uncertain', $booking->owner_sms_status);
        $this->assertNotNull($booking->owner_sms_error);

        // A later notify must not call SMS.ir again (avoid duplicate after uncertain timeout).
        Http::fake([
            'api.sms.ir/v1/send/bulk' => Http::response(['status' => 1, 'message' => 'موفق', 'data' => ['messageIds' => ['1']]], 200),
        ]);

        $method = new ReflectionMethod(BookingService::class, 'maybeNotifyOwnerSms');
        $method->invoke(app(BookingService::class), $booking->fresh());

        Http::assertNothingSent();
        $this->assertSame('uncertain', $booking->fresh()->owner_sms_status);
    }

    public function test_disabled_sms_skips_provider_entirely(): void
    {
        config(['smsir.enabled' => false]);
        Http::fake();

        $booking = $this->createBooking();

        Http::assertNothingSent();
        $this->assertNull($booking->fresh()->owner_sms_status);
        $this->assertSame('confirmed', $booking->status);
    }

    protected function createBooking(): Booking
    {
        $local = $this->nextOpenSlotLocal();

        return app(BookingService::class)->create([
            'name' => 'SMS Test Visitor',
            'email' => 'sms-test@example.com',
            'phone' => null,
            'topic' => 'SMS unit test',
            'message' => 'Do not notify visitor details by SMS',
            'starts_at' => $local->utc()->toIso8601String(),
            'timezone' => 'Asia/Tehran',
        ], '127.0.0.1');
    }

    protected function nextOpenSlotLocal(): CarbonImmutable
    {
        $day = CarbonImmutable::now('Asia/Tehran')->startOfDay()->addDays(3);
        for ($i = 0; $i < 14; $i++) {
            $candidate = $day->addDays($i);
            $weekday = (int) $candidate->format('w');
            if (in_array($weekday, [0, 1, 2, 3, 6], true)) {
                return $candidate->setTime(10, 0, 0);
            }
        }

        $this->fail('No open weekday found for test slot');
    }
}
