<?php

return [
    /*
    | Absolute path for published media on the web root (cPanel public_html).
    | Leave empty to use public_path('media/published').
    */
    'published_media_path' => env('PUBLISHED_MEDIA_PATH') ?: null,

    /*
    | Fallback scheduling link (Cal.com / Calendly, https only) used when the
    | admin "Booking" setting has no URL. The admin value always wins.
    */
    'booking_url' => env('BOOKING_URL') ?: null,

    'locales' => ['en', 'fa'],
    'default_locale' => 'en',
];
