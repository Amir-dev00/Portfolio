<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-only: adds the "Book a Call" strings and booking setting.
 * insertOrIgnore keeps any existing (admin-edited) rows untouched.
 * cPanel installs without a terminal run database/upgrades/2026_11_10_booking.sql instead.
 */
return new class extends Migration
{
    public const STRINGS = [
        'contact.bookCall' => ['Book a Call', 'رزرو جلسه'],
        'booking.eyebrow' => ['Book a call', 'رزرو جلسه'],
        'booking.title' => ['Let’s Talk', 'گفت‌وگو کنیم'],
        'booking.intro' => [
            'A relaxed video call to talk through your project, role or idea. Pick a time that suits you — you’ll get a calendar invite right away.',
            'یک تماس تصویری کوتاه و صمیمی برای صحبت درباره‌ی پروژه، موقعیت شغلی یا ایده‌ی شما. زمانی را که برایتان مناسب است انتخاب کنید؛ دعوت‌نامه‌ی تقویم بلافاصله برایتان ارسال می‌شود.',
        ],
        'booking.duration' => ['{min} min', '{min} دقیقه'],
        'booking.timezone' => ['Your time · {tz}', 'به وقت شما · {tz}'],
        'booking.loading' => ['Loading live availability…', 'در حال دریافت زمان‌های آزاد…'],
        'booking.back' => ['Back to calendar', 'بازگشت به تقویم'],
        'booking.close' => ['Close', 'بستن'],
        'booking.preferEmail' => ['Prefer email?', 'ایمیل را ترجیح می‌دهید؟'],
        'booking.confirmed' => [
            'You’re booked. Check your inbox for the confirmation and calendar invite.',
            'جلسه رزرو شد. تأیید و دعوت‌نامه‌ی تقویم را در ایمیل خود ببینید.',
        ],
        'booking.emailInstead' => ['Email me instead', 'به جای آن ایمیل بزنید'],
        'booking.openExternal' => ['Open scheduling page', 'باز کردن صفحه‌ی رزرو'],
        'booking.setup.title' => ['Online booking is almost ready', 'رزرو آنلاین به‌زودی فعال می‌شود'],
        'booking.setup.body' => [
            'Scheduling isn’t open yet. Send me an email and we’ll find a time that works for both of us.',
            'فعلاً امکان رزرو آنلاین باز نیست. ایمیل بزنید تا زمانی مناسب برای هر دو پیدا کنیم.',
        ],
        'booking.setup.admin' => [
            'Admin: add your Cal.com or Calendly link in Admin → Site content → Booking, then click Publish live.',
            'مدیر: لینک Cal.com یا Calendly را در Admin → Site content → Booking وارد کنید و سپس Publish live را بزنید.',
        ],
        'booking.error.title' => ['The scheduler didn’t load', 'تقویم رزرو بارگذاری نشد'],
        'booking.error.body' => [
            'Your connection or a content blocker may have stopped it. You can open the scheduling page directly or email me.',
            'ممکن است اتصال یا افزونه‌ی مسدودکننده مانع آن شده باشد. می‌توانید صفحه‌ی رزرو را مستقیم باز کنید یا ایمیل بزنید.',
        ],
    ];

    public const BOOKING_SETTING = ['enabled' => true, 'url' => null, 'provider' => 'auto', 'duration' => 30];

    public function up(): void
    {
        $now = now();

        foreach (self::STRINGS as $key => [$en, $fa]) {
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

        DB::table('site_settings')->insertOrIgnore([
            'key' => 'booking',
            'value' => json_encode(self::BOOKING_SETTING),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('content_entries')->where('group', 'ui')->whereIn('key', array_keys(self::STRINGS))->delete();
        DB::table('site_settings')->where('key', 'booking')->delete();
    }
};
