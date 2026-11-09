<?php

return [
    /*
    | Absolute path for published media on the web root (cPanel public_html).
    | Leave empty to use public_path('media/published').
    */
    'published_media_path' => env('PUBLISHED_MEDIA_PATH') ?: null,

    'locales' => ['en', 'fa'],
    'default_locale' => 'en',
];
