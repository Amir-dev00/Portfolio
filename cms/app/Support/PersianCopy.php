<?php

namespace App\Support;

final class PersianCopy
{
    public static function simplify(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(self::simplify(...), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = strtr($value, [
            'کوئرا' => 'Quera',
            'مسئل' => 'مسال',
            'مسئول' => 'مسوول',
            'ئ' => 'ی',
            'ؤ' => 'و',
            'أ' => 'ا',
            'إ' => 'ا',
            'ۀ' => 'ه',
            'ء' => '',
        ]);

        return preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value) ?? $value;
    }
}
