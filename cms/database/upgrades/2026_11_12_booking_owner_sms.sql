-- AZ Portfolio upgrade: Book a Call owner SMS.ir tracking (2026-11-12)
-- Import in phpMyAdmin on the EXISTING database. Safe to re-run.
-- Does NOT delete bookings or change visitor fields.

SET @db := DATABASE();

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'owner_sms_status'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `bookings` ADD COLUMN `owner_sms_status` varchar(16) DEFAULT NULL AFTER `ip_hash`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'owner_sms_provider_message_id'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `bookings` ADD COLUMN `owner_sms_provider_message_id` varchar(64) DEFAULT NULL AFTER `owner_sms_status`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'owner_sms_error'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `bookings` ADD COLUMN `owner_sms_error` varchar(255) DEFAULT NULL AFTER `owner_sms_provider_message_id`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'owner_sms_sent_at'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `bookings` ADD COLUMN `owner_sms_sent_at` timestamp NULL DEFAULT NULL AFTER `owner_sms_error`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional: keep Laravel's migrations table in sync if you also use artisan migrate locally.
INSERT IGNORE INTO `migrations` (`migration`, `batch`)
VALUES ('2026_11_12_140000_add_booking_owner_sms_columns', 99);
