-- AZ Portfolio upgrade: Persian Contact page name «ارتباط» (2026-11-12)
-- phpMyAdmin → your database → SQL → Go.
-- Updates only the listed ui.* keys. Then Admin → Site content → Publish live.

UPDATE `content_entries`
SET `value_fa` = 'ارتباط',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` IN ('nav.contact', 'nav.tooltip.contact', 'contact.aria');

UPDATE `content_entries`
SET `value_fa` = 'ارتباط — امیرحسین زمان‌زاده | توسعه‌دهنده',
    `is_dirty` = 1,
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'title.contact';
