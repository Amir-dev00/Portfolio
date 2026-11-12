<?php

namespace App\Http\Controllers;

use App\Services\PortfolioContent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function __construct(private PortfolioContent $content) {}

    public function home(Request $request, string $locale = 'en'): View
    {
        return $this->page($request, $locale, 'home');
    }

    public function work(Request $request, string $locale = 'en'): View
    {
        return $this->page($request, $locale, 'work');
    }

    public function about(Request $request, string $locale = 'en'): View
    {
        return $this->page($request, $locale, 'about');
    }

    public function ask(Request $request, string $locale = 'en'): View
    {
        return $this->page($request, $locale, 'ask');
    }

    public function contact(Request $request, string $locale = 'en'): View
    {
        return $this->page($request, $locale, 'contact');
    }

    public function project(Request $request, string $locale, string $slug): View
    {
        return $this->page($request, $locale, 'project', $slug);
    }

    public function projectEn(Request $request, string $slug): View
    {
        return $this->project($request, 'en', $slug);
    }

    public function sitemap(): Response
    {
        $locales = ['en', 'fa'];
        $pages = ['home', 'work', 'about', 'contact'];
        $projects = $this->content->projectsFor('en');
        $urls = [];
        foreach ($locales as $locale) {
            foreach ($pages as $page) {
                $urls[] = [
                    'loc' => $this->urlFor($locale, $page),
                    'changefreq' => 'weekly',
                    'priority' => $page === 'home' ? '1.0' : '0.8',
                ];
            }
            foreach ($projects as $project) {
                $urls[] = [
                    'loc' => $this->urlFor($locale, 'project', $project['slug']),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            }
        }

        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    protected function page(Request $request, string $locale, string $view, ?string $slug = null): View
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $preview = $request->boolean('preview') && $request->user()?->isOwner();
        $payload = $this->content->payload($preview);
        $strings = $this->content->strings($locale, $preview);
        $projects = $this->content->projectsFor($locale, $preview);
        $seo = $this->content->seo($locale, $view === 'project' ? 'project' : 'page', $view === 'project' ? $slug : $view, $preview);

        if ($view === 'ask') {
            $seo['title'] = $strings['title.ask'] ?? ($locale === 'fa'
                ? 'گفتگو — امیرحسین زمان‌زاده'
                : 'Ask — Amirhossein Zamanzade');
        }

        $project = null;
        if ($view === 'project') {
            $project = collect($projects)->firstWhere('slug', $slug);
            if (! $project) {
                abort(404);
            }
            if (! ($seo['title'] ?? null)) {
                $seo['title'] = $project['title'];
            }
        }

        $hreflang = [
            'en' => $this->urlFor('en', $view, $slug),
            'fa' => $this->urlFor('fa', $view, $slug),
        ];

        $cvUrl = $payload['resume']['url'] ?? null;
        $booking = $this->bookingConfig((array) ($payload['booking'] ?? $payload['settings']['booking'] ?? []));
        $bootstrap = [
            'lang' => $locale,
            'dir' => $locale === 'fa' ? 'rtl' : 'ltr',
            'strings' => $payload['strings'] ?? [],
            'projects' => array_map(function (array $p) use ($locale) {
                // Provide both locales for client switching without reload.
                return [
                    'slug' => $p['slug'] ?? null,
                    'title' => $p['title'] ?? null,
                    'category' => ($p[$locale]['category'] ?? $p['en']['category'] ?? $p['category'] ?? null),
                    'shortDescription' => ($p[$locale]['shortDescription'] ?? $p['en']['shortDescription'] ?? null),
                    'fullDescription' => ($p[$locale]['fullDescription'] ?? $p['en']['fullDescription'] ?? null),
                    'role' => ($p[$locale]['role'] ?? $p['en']['role'] ?? null),
                    'technologies' => $p['technologies'] ?? [],
                    'image' => isset($p['image']) ? ltrim($p['image'], '/') : null,
                    'liveUrl' => $p['liveUrl'] ?? null,
                    'githubUrl' => $p['githubUrl'] ?? null,
                    'date' => $p['date'] ?? null,
                    'is_featured' => (bool) ($p['is_featured'] ?? false),
                    'i18n' => [
                        'en' => $p['en'] ?? [],
                        'fa' => $p['fa'] ?? [],
                    ],
                ];
            }, $payload['projects'] ?? []),
            'featured_slugs' => array_values(array_filter((array) ($payload['featured_slugs'] ?? []))),
            'seo' => $payload['seo'] ?? [],
            'cvUrl' => $cvUrl,
            'social' => $payload['social'] ?? [],
            'profile' => $payload['profile'] ?? [],
            'booking' => $booking,
            'canonical' => $hreflang[$locale],
            'preview' => (bool) $preview,
        ];

        return view('site.app', [
            'locale' => $locale,
            'dir' => $locale === 'fa' ? 'rtl' : 'ltr',
            'viewName' => $view,
            'slug' => $slug,
            'project' => $project,
            'strings' => $strings,
            'projects' => $projects,
            'seo' => $seo,
            'bootstrap' => $bootstrap,
            'hreflang' => $hreflang,
            'canonical' => $hreflang[$locale],
            'cvUrl' => $cvUrl,
            'social' => $payload['social'] ?? [],
            'profile' => $payload['profile'] ?? [],
            'booking' => $booking,
            'bookingAdmin' => (bool) $request->user()?->isOwner(),
        ]);
    }

    /**
     * Native self-hosted booking settings (no external scheduling providers).
     */
    protected function bookingConfig(array $settings): array
    {
        $duration = (int) ($settings['duration'] ?? 30);

        return [
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'duration' => $duration > 0 ? $duration : 30,
            'timezone' => $settings['timezone'] ?? 'Asia/Tehran',
            'native' => true,
            'api' => url('/api/booking'),
        ];
    }

    protected function urlFor(string $locale, string $view, ?string $slug = null): string
    {
        $prefix = $locale === 'fa' ? '/fa' : '';

        return match ($view) {
            'work' => url($prefix.'/work'),
            'about' => url($prefix.'/about'),
            'ask' => url($prefix.'/ask'),
            'contact' => url($prefix.'/contact'),
            'project' => url($prefix.'/work/'.$slug),
            default => url($prefix ?: '/'),
        };
    }
}
