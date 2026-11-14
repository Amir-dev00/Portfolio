<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $previous = app()->getLocale();
        $locale = $request->session()->get('admin_locale', 'en');
        app()->setLocale($locale === 'fa' ? 'fa' : 'en');

        try {
            return $next($request);
        } finally {
            app()->setLocale($previous);
        }
    }
}
