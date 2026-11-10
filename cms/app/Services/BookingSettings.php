<?php

namespace App\Services;

use App\Models\SiteSetting;

final class BookingSettings
{
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'duration' => 30,
            'buffer' => 15,
            'min_notice' => 120,
            'max_days' => 45,
            'timezone' => 'Asia/Tehran',
            'slot_interval' => 30,
            'owner_notify' => true,
            'visitor_notify' => true,
        ];
    }

    public static function get(): array
    {
        $stored = (array) (SiteSetting::query()->where('key', 'booking')->value('value') ?? []);

        return array_merge(self::defaults(), $stored);
    }

    public static function put(array $data): array
    {
        $merged = array_merge(self::get(), $data);
        $clean = [
            'enabled' => (bool) ($merged['enabled'] ?? true),
            'duration' => max(5, min(480, (int) ($merged['duration'] ?? 30))),
            'buffer' => max(0, min(240, (int) ($merged['buffer'] ?? 15))),
            'min_notice' => max(0, min(10080, (int) ($merged['min_notice'] ?? 120))),
            'max_days' => max(1, min(365, (int) ($merged['max_days'] ?? 45))),
            'timezone' => $merged['timezone'] ?: 'Asia/Tehran',
            'slot_interval' => max(5, min(240, (int) ($merged['slot_interval'] ?? ($merged['duration'] ?? 30)))),
            'owner_notify' => (bool) ($merged['owner_notify'] ?? true),
            'visitor_notify' => (bool) ($merged['visitor_notify'] ?? true),
        ];
        SiteSetting::query()->updateOrCreate(['key' => 'booking'], ['value' => $clean]);

        return $clean;
    }
}
