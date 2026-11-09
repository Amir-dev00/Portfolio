<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configured = env('PUBLIC_PATH');
        if ($configured && is_dir($configured)) {
            $this->app->usePublicPath($configured);
            return;
        }

        // cPanel layout: /home/USER/portfolio-app + /home/USER/public_html
        $sibling = dirname(base_path()).DIRECTORY_SEPARATOR.'public_html';
        if (is_dir($sibling) && is_file($sibling.DIRECTORY_SEPARATOR.'index.php')) {
            $this->app->usePublicPath($sibling);
        }
    }

    public function boot(): void
    {
        //
    }
}
