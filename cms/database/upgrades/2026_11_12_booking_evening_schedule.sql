-- AZ Portfolio upgrade: Book a Call evening schedule (2026-11-12)
-- phpMyAdmin → your database → SQL → Go.
-- Replaces weekly availability windows only. Does NOT delete or alter `bookings`
-- or `blocked_slots`. Existing reservations stay intact and continue to block
-- the same UTC times when those slots come up again.
--
-- Schedule (Asia/Tehran): every day 19:00–22:00
-- Slots: 19:00, 19:30, 20:00, 20:30, 21:00, 21:30 (each 30 min; last ends 22:00)
-- weekday: 0=Sunday … 6=Saturday (PHP / Carbon `w`)

UPDATE `site_settings`
SET `value` = JSON_SET(
  COALESCE(`value`, JSON_OBJECT()),
  '$.enabled', true,
  '$.duration', 30,
  '$.buffer', 0,
  '$.timezone', 'Asia/Tehran',
  '$.slot_interval', 30
),
`updated_at` = NOW()
WHERE `key` = 'booking';

INSERT INTO `site_settings` (`key`, `value`, `created_at`, `updated_at`)
SELECT
  'booking',
  '{"enabled":true,"duration":30,"buffer":0,"min_notice":120,"max_days":45,"timezone":"Asia\\/Tehran","slot_interval":30,"owner_notify":true,"visitor_notify":true}',
  NOW(),
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM `site_settings` WHERE `key` = 'booking');

-- Replace windows only (bookings table untouched).
DELETE FROM `availability_rules`;

INSERT INTO `availability_rules` (`weekday`, `start_time`, `end_time`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(0, '19:00:00', '22:00:00', 1, 0, NOW(), NOW()),
(1, '19:00:00', '22:00:00', 1, 1, NOW(), NOW()),
(2, '19:00:00', '22:00:00', 1, 2, NOW(), NOW()),
(3, '19:00:00', '22:00:00', 1, 3, NOW(), NOW()),
(4, '19:00:00', '22:00:00', 1, 4, NOW(), NOW()),
(5, '19:00:00', '22:00:00', 1, 5, NOW(), NOW()),
(6, '19:00:00', '22:00:00', 1, 6, NOW(), NOW());
