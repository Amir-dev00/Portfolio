<?php

namespace App\Support;

final class ProfilePortrait
{
    public const DEFAULT_PATH = 'assets/img/amir/profile.webp';

    public static function path(?string $path): string
    {
        $path = trim((string) $path);
        $bundledAliases = ['', 'media/library/profile.jpg', 'media/published/img/profile.jpg', 'media/published/img/profile.webp'];

        return in_array(ltrim($path, '/'), $bundledAliases, true) ? self::DEFAULT_PATH : $path;
    }
}
