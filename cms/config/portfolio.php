<?php

return [
    /*
    | Absolute path for published media on the web root (cPanel public_html).
    | Leave empty to use public_path('media/published').
    */
    'published_media_path' => env('PUBLISHED_MEDIA_PATH') ?: null,

    'locales' => ['en', 'fa'],
    'default_locale' => 'en',
    'contact' => [
        'phone' => '09124778210',
        'telegram' => 'https://t.me/+989124778210',
        'telegram_handle' => '1189935017',
    ],
    'about_skills' => [
        'frontend' => ['HTML', 'CSS', 'JavaScript', 'React', 'Bootstrap', 'Tailwind', 'Sass'],
        'languages' => ['Python', 'Java', 'PHP', 'C', 'C#'],
        'backend' => ['Node.js', 'Laravel', '.NET', 'MySQL', 'PostgreSQL', 'MongoDB'],
        'other' => ['JavaFX', 'Scrum'],
    ],
];
