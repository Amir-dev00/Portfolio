-- AZ Portfolio upgrade: Contact + booking confirmation copy (2026-11-12)
-- phpMyAdmin → your database → SQL → Go. Touches only the listed ui.* keys.
-- After import: Admin → Site content → review Contact / Booking strings → Publish live.
-- Custom edits to other keys are left alone.

UPDATE `content_entries`
SET `value_en` = 'Let’s work together',
    `value_fa` = 'بیایید همکاری کنیم',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'contact.title';

UPDATE `content_entries`
SET `value_en` = 'Have a project or role in mind? Book a call or send me an email.',
    `value_fa` = 'پروژه یا فرصت همکاری در نظر دارید؟ یک تماس رزرو کنید یا ایمیل بزنید.',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'contact.intro';

UPDATE `content_entries`
SET `value_en` = 'Book a call',
    `value_fa` = 'رزرو تماس',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'contact.bookCall';

UPDATE `content_entries`
SET `value_en` = 'Professional profile',
    `value_fa` = 'ارتباط حرفه‌ای',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'contact.linkedin.sub';

UPDATE `content_entries`
SET `value_en` = 'Projects and code',
    `value_fa` = 'پروژه‌ها و کد',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'contact.github.sub';

UPDATE `content_entries`
SET `value_en` = 'Send a message',
    `value_fa` = 'ارسال پیام',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'contact.telegram.sub';

UPDATE `content_entries`
SET `value_en` = 'Book a call',
    `value_fa` = 'رزرو تماس',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'booking.eyebrow';

UPDATE `content_entries`
SET `value_en` = 'Your call is confirmed. You can download the calendar file below.',
    `value_fa` = 'تماس شما تأیید شد. می‌توانید فایل تقویم را از پایین دانلود کنید.',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'booking.confirmed';

INSERT IGNORE INTO `content_entries` (`group`,`key`,`value_en`,`value_fa`,`is_dirty`,`created_at`,`updated_at`)
VALUES (
  'ui',
  'booking.confirmed.email',
  'Your call is confirmed. A confirmation email is on its way, and you can download the calendar file below.',
  'تماس شما تأیید شد. ایمیل تأیید هم ارسال شد و می‌توانید فایل تقویم را از پایین دانلود کنید.',
  1,
  NOW(),
  NOW()
);
