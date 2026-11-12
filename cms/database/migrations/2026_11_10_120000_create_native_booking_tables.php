<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Self-hosted Book a Call tables (no Cal.com / Calendly).
 * cPanel: also run database/upgrades/2026_11_10_native_booking.sql via phpMyAdmin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            // PHP date('w'): 0=Sunday … 6=Saturday (owner timezone Asia/Tehran).
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['weekday', 'is_active']);
        });

        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            // dateTime avoids MySQL "invalid default value" on non-nullable timestamp pairs.
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('visitor_name');
            $table->string('visitor_email');
            $table->string('visitor_phone', 64)->nullable();
            $table->string('topic')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->string('status', 24)->default('confirmed'); // confirmed|cancelled|completed
            $table->string('cancel_token', 64)->unique();
            $table->string('ip_hash', 64)->nullable();
            // Owner SMS.ir outcome: null|pending|accepted|rejected|uncertain
            $table->string('owner_sms_status', 16)->nullable();
            $table->string('owner_sms_provider_message_id', 64)->nullable();
            $table->string('owner_sms_error', 255)->nullable();
            $table->timestamp('owner_sms_sent_at')->nullable();
            $table->timestamps();
            $table->index(['starts_at', 'ends_at', 'status']);
            $table->index(['visitor_email', 'created_at']);
        });

        // Replace external-provider booking setting with native defaults (preserve enabled flag).
        $existing = DB::table('site_settings')->where('key', 'booking')->value('value');
        $decoded = is_string($existing) ? (json_decode($existing, true) ?: []) : (array) $existing;
        $native = [
            'enabled' => array_key_exists('enabled', $decoded) ? (bool) $decoded['enabled'] : true,
            'duration' => (int) ($decoded['duration'] ?? 30),
            'buffer' => 15,
            'min_notice' => 120,
            'max_days' => 45,
            'timezone' => 'Asia/Tehran',
            'slot_interval' => (int) ($decoded['duration'] ?? 30),
            'owner_notify' => true,
            'visitor_notify' => true,
            // Drop obsolete external fields if present.
        ];
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'booking'],
            ['value' => json_encode($native), 'updated_at' => now(), 'created_at' => now()]
        );

        // Seed sensible Asia/Tehran weekdays (Sat–Wed mornings + afternoons). Admin can edit.
        $now = now();
        $windows = [
            // Saturday(6), Sunday(0), Monday(1), Tuesday(2), Wednesday(3)
            [6, '10:00:00', '13:00:00'], [6, '15:00:00', '18:00:00'],
            [0, '10:00:00', '13:00:00'], [0, '15:00:00', '18:00:00'],
            [1, '10:00:00', '13:00:00'], [1, '15:00:00', '18:00:00'],
            [2, '10:00:00', '13:00:00'], [2, '15:00:00', '18:00:00'],
            [3, '10:00:00', '13:00:00'], [3, '15:00:00', '18:00:00'],
        ];
        foreach ($windows as $i => [$weekday, $start, $end]) {
            DB::table('availability_rules')->insert([
                'weekday' => $weekday,
                'start_time' => $start,
                'end_time' => $end,
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // UI copy for the native multi-step flow (insertOrIgnore keeps admin edits).
        $strings = [
            'booking.step.date' => ['Choose a date', 'انتخاب تاریخ'],
            'booking.step.time' => ['Choose a time', 'انتخاب ساعت'],
            'booking.step.details' => ['Your details', 'اطلاعات شما'],
            'booking.step.done' => ['You’re booked', 'رزرو شد'],
            'booking.continue' => ['Continue', 'ادامه'],
            'booking.confirm' => ['Confirm booking', 'تأیید رزرو'],
            'booking.name' => ['Full name', 'نام و نام خانوادگی'],
            'booking.email' => ['Email', 'ایمیل'],
            'booking.phone' => ['Phone (optional)', 'تلفن (اختیاری)'],
            'booking.topic' => ['Meeting topic (optional)', 'موضوع جلسه (اختیاری)'],
            'booking.message' => ['Message (optional)', 'پیام (اختیاری)'],
            'booking.privacy' => [
                'Your details are stored on this site only and used to schedule our call.',
                'اطلاعات شما فقط روی همین سایت ذخیره می‌شود و برای هماهنگی جلسه استفاده خواهد شد.',
            ],
            'booking.noSlots' => ['No times left on this day. Pick another date.', 'برای این روز زمانی باقی نمانده. روز دیگری انتخاب کنید.'],
            'booking.downloadIcs' => ['Add to calendar', 'افزودن به تقویم'],
            'booking.reference' => ['Reference', 'کد پیگیری'],
            'booking.unavailable' => ['That time was just taken. Please choose another slot.', 'این زمان همین حالا رزرو شد. لطفاً زمان دیگری انتخاب کنید.'],
            'booking.disabled' => ['Online booking is paused. Email me and we’ll find a time.', 'رزرو آنلاین موقتاً غیرفعال است. ایمیل بزنید تا زمانی پیدا کنیم.'],
            'booking.role' => ['Software Engineer', 'مهندس نرم‌افزار'],
            'booking.setup.admin' => [
                'Admin: set your weekly hours under Admin → Bookings.',
                'مدیر: ساعات هفتگی را در Admin → رزروها تنظیم کنید.',
            ],
        ];
        foreach ($strings as $key => [$en, $fa]) {
            DB::table('content_entries')->insertOrIgnore([
                'group' => 'ui',
                'key' => $key,
                'value_en' => $en,
                'value_fa' => $fa,
                'is_dirty' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Refresh intro/setup copy for the native system.
        DB::table('content_entries')->where('group', 'ui')->where('key', 'booking.intro')->update([
            'value_en' => 'A relaxed video call about your project, role, or idea. Pick a time that works — confirmation is instant.',
            'value_fa' => 'یک تماس تصویری کوتاه درباره‌ی پروژه، موقعیت شغلی یا ایده‌ی شما. زمانی مناسب انتخاب کنید؛ تأیید فوری است.',
            'is_dirty' => true,
            'updated_at' => $now,
        ]);
        DB::table('content_entries')->where('group', 'ui')->where('key', 'booking.setup.body')->update([
            'value_en' => 'Scheduling isn’t open yet. Send me an email and we’ll find a time that works for both of us.',
            'value_fa' => 'فعلاً امکان رزرو آنلاین باز نیست. ایمیل بزنید تا زمانی مناسب برای هر دو پیدا کنیم.',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('blocked_slots');
        Schema::dropIfExists('availability_rules');
    }
};
