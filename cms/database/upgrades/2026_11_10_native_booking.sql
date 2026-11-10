-- AZ Portfolio upgrade: self-hosted Book a Call (2026-11-10)
-- Import in phpMyAdmin on the EXISTING database. Idempotent where possible.
-- Does NOT delete users, projects, or content.

CREATE TABLE IF NOT EXISTS `availability_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `weekday` tinyint unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `availability_rules_weekday_is_active_index` (`weekday`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blocked_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blocked_slots_starts_at_ends_at_index` (`starts_at`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(32) NOT NULL,
  `visitor_name` varchar(255) NOT NULL,
  `visitor_email` varchar(255) NOT NULL,
  `visitor_phone` varchar(64) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `message` text,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Tehran',
  `status` varchar(24) NOT NULL DEFAULT 'confirmed',
  `cancel_token` varchar(64) NOT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_reference_unique` (`reference`),
  UNIQUE KEY `bookings_cancel_token_unique` (`cancel_token`),
  KEY `bookings_starts_at_ends_at_status_index` (`starts_at`,`ends_at`,`status`),
  KEY `bookings_visitor_email_created_at_index` (`visitor_email`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`key`, `value`, `created_at`, `updated_at`)
VALUES (
  'booking',
  '{"enabled":true,"duration":30,"buffer":15,"min_notice":120,"max_days":45,"timezone":"Asia\\/Tehran","slot_interval":30,"owner_notify":true,"visitor_notify":true}',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `value` = VALUES(`value`),
  `updated_at` = NOW();

INSERT INTO `availability_rules` (`weekday`,`start_time`,`end_time`,`is_active`,`sort_order`,`created_at`,`updated_at`)
SELECT * FROM (
  SELECT 6 AS weekday, '10:00:00' AS start_time, '13:00:00' AS end_time, 1 AS is_active, 0 AS sort_order, NOW() AS created_at, NOW() AS updated_at
  UNION ALL SELECT 6,'15:00:00','18:00:00',1,1,NOW(),NOW()
  UNION ALL SELECT 0,'10:00:00','13:00:00',1,2,NOW(),NOW()
  UNION ALL SELECT 0,'15:00:00','18:00:00',1,3,NOW(),NOW()
  UNION ALL SELECT 1,'10:00:00','13:00:00',1,4,NOW(),NOW()
  UNION ALL SELECT 1,'15:00:00','18:00:00',1,5,NOW(),NOW()
  UNION ALL SELECT 2,'10:00:00','13:00:00',1,6,NOW(),NOW()
  UNION ALL SELECT 2,'15:00:00','18:00:00',1,7,NOW(),NOW()
  UNION ALL SELECT 3,'10:00:00','13:00:00',1,8,NOW(),NOW()
  UNION ALL SELECT 3,'15:00:00','18:00:00',1,9,NOW(),NOW()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `availability_rules` LIMIT 1);

INSERT IGNORE INTO `content_entries` (`group`,`key`,`value_en`,`value_fa`,`is_dirty`,`created_at`,`updated_at`) VALUES
('ui','booking.step.date','Choose a date','انتخاب تاریخ',1,NOW(),NOW()),
('ui','booking.step.time','Choose a time','انتخاب ساعت',1,NOW(),NOW()),
('ui','booking.step.details','Your details','اطلاعات شما',1,NOW(),NOW()),
('ui','booking.step.done','You’re booked','رزرو شد',1,NOW(),NOW()),
('ui','booking.continue','Continue','ادامه',1,NOW(),NOW()),
('ui','booking.confirm','Confirm booking','تأیید رزرو',1,NOW(),NOW()),
('ui','booking.name','Full name','نام و نام خانوادگی',1,NOW(),NOW()),
('ui','booking.email','Email','ایمیل',1,NOW(),NOW()),
('ui','booking.phone','Phone (optional)','تلفن (اختیاری)',1,NOW(),NOW()),
('ui','booking.topic','Meeting topic (optional)','موضوع جلسه (اختیاری)',1,NOW(),NOW()),
('ui','booking.message','Message (optional)','پیام (اختیاری)',1,NOW(),NOW()),
('ui','booking.privacy','Your details are stored on this site only and used to schedule our call.','اطلاعات شما فقط روی همین سایت ذخیره می‌شود و برای هماهنگی جلسه استفاده خواهد شد.',1,NOW(),NOW()),
('ui','booking.noSlots','No times left on this day. Pick another date.','برای این روز زمانی باقی نمانده. روز دیگری انتخاب کنید.',1,NOW(),NOW()),
('ui','booking.downloadIcs','Add to calendar','افزودن به تقویم',1,NOW(),NOW()),
('ui','booking.reference','Reference','کد پیگیری',1,NOW(),NOW()),
('ui','booking.unavailable','That time was just taken. Please choose another slot.','این زمان همین حالا رزرو شد. لطفاً زمان دیگری انتخاب کنید.',1,NOW(),NOW()),
('ui','booking.disabled','Online booking is paused. Email me and we’ll find a time.','رزرو آنلاین موقتاً غیرفعال است. ایمیل بزنید تا زمانی پیدا کنیم.',1,NOW(),NOW()),
('ui','booking.role','Software Engineer','مهندس نرم‌افزار',1,NOW(),NOW());

UPDATE `content_entries`
SET `value_en` = 'Admin: set your weekly hours under Admin → Bookings.',
    `value_fa` = 'مدیر: ساعات هفتگی را در Admin → رزروها تنظیم کنید.',
    `updated_at` = NOW()
WHERE `group` = 'ui' AND `key` = 'booking.setup.admin';
