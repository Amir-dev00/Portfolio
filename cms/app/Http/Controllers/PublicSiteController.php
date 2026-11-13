<?php

namespace App\Http\Controllers;

use App\Services\PortfolioContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicSiteController extends Controller
{
    public function __construct(private PortfolioContent $content) {}

    public function home(Request $request, string $locale = 'en'): Response|JsonResponse
    {
        return $this->page($request, $locale, 'home');
    }

    public function work(Request $request, string $locale = 'en'): Response|JsonResponse
    {
        return $this->page($request, $locale, 'work');
    }

    public function about(Request $request, string $locale = 'en'): Response|JsonResponse
    {
        return $this->page($request, $locale, 'about');
    }

    public function ask(Request $request, string $locale = 'en'): Response|JsonResponse
    {
        return $this->page($request, $locale, 'ask');
    }

    public function contact(Request $request, string $locale = 'en'): Response|JsonResponse
    {
        return $this->page($request, $locale, 'contact');
    }

    public function project(Request $request, string $locale, string $slug): Response|JsonResponse
    {
        return $this->page($request, $locale, 'project', $slug);
    }

    public function projectEn(Request $request, string $slug): Response|JsonResponse
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

    protected function page(Request $request, string $locale, string $view, ?string $slug = null): Response|JsonResponse
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $preview = $request->boolean('preview') && $request->user()?->isOwner();
        $payload = $this->content->payload($preview);
        $strings = $payload['strings'][$locale] ?? $payload['strings']['en'] ?? [];
        $scope = $view === 'project' ? 'project' : 'page';
        $scopeKey = $view === 'project' ? $slug : $view;
        $seo = $payload['seo'][$locale][$scope][$scopeKey]
            ?? $payload['seo']['en'][$scope][$scopeKey]
            ?? [];

        $projects = match ($view) {
            'work' => $payload['projects'] ?? [],
            'project' => array_values(array_filter($payload['projects'] ?? [], fn (array $item) => $item['slug'] === $slug)),
            default => [],
        };
        $projects = array_map(function (array $item) use ($locale, $view) {
            $copy = $item[$locale] ?? $item['en'] ?? [];

            return [
                'slug' => $item['slug'],
                'title' => $item['title'],
                'category' => $copy['category'] ?? $item['category'] ?? '',
                'shortDescription' => $copy['shortDescription'] ?? '',
                'fullDescription' => $view === 'project' ? ($copy['fullDescription'] ?? '') : '',
                'role' => $view === 'project' ? ($copy['role'] ?? '') : '',
                'technologies' => $item['technologies'] ?? [],
                'image' => empty($item['image']) ? null : $this->assetUrl($item['image']),
                'liveUrl' => $item['liveUrl'] ?? null,
                'githubUrl' => $item['githubUrl'] ?? null,
                'date' => $item['date'] ?? null,
            ];
        }, $projects);
        $project = $projects[0] ?? null;

        if ($view === 'project') {
            abort_unless($project, 404);
            $seo['title'] = $seo['title'] ?? $project['title'];
        } elseif ($view === 'ask') {
            $seo['title'] = $strings['title.ask'] ?? ($locale === 'fa'
                ? 'گفتگو — امیرحسین زمان‌زاده'
                : 'Ask — Amirhossein Zamanzade');
        }
        $seo['title'] ??= $strings['title.'.$view] ?? $strings['title.base'] ?? 'Portfolio';

        $hreflang = [
            'en' => $this->urlFor('en', $view, $slug),
            'fa' => $this->urlFor('fa', $view, $slug),
        ];

        $cvHref = $this->assetUrl($payload['resume']['url'] ?? '', 'assets/cv/Amirhossein-Zamanzade-CV.pdf');
        $profile = $payload['profile'] ?? [];
        $booking = $this->bookingConfig((array) ($payload['booking'] ?? $payload['settings']['booking'] ?? []));
        $routeData = [
            'projects' => $projects,
            'seo' => $seo,
            'hreflang' => $hreflang,
            'ogImage' => $this->assetUrl($seo['og_image'] ?? '', 'assets/img/amir/az-monogram-blue.webp'),
        ];

        $assetV = static function (string $path): string {
            $file = public_path($path);

            return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
        };

        $pages = ['home', 'work', 'about', 'ask', 'contact'];
        $viewData = [
            'locale' => $locale,
            'dir' => $locale === 'fa' ? 'rtl' : 'ltr',
            'viewName' => $view,
            'activeView' => $view,
            'slug' => $slug,
            'project' => $view === 'project' ? $project : null,
            'strings' => $strings,
            'seo' => $seo,
            'hreflang' => $hreflang,
            'canonical' => $hreflang[$locale],
            'cvHref' => $cvHref,
            'assetV' => $assetV,
            'navPaths' => array_combine($pages, array_map(fn (string $page) => $this->urlFor($locale, $page), $pages)),
            'social' => array_merge([
                'email' => 'itsamirzamani@gmail.com',
                'linkedin' => 'https://linkedin.com/in/amirhossein-zamanzade',
                'github' => 'https://github.com/Amir-dev00',
                'telegram' => 'https://t.me/Amir_dev00',
                'telegram_handle' => '@Amir_dev00',
            ], array_filter((array) ($payload['social'] ?? []), fn ($value) => is_string($value) && trim($value) !== '')),
            'profile' => $profile,
            'profilePhoto' => $this->assetUrl($profile['photo'] ?? '', 'assets/img/amir/profile.webp'),
            'booking' => $booking,
            'bookingAdmin' => (bool) $request->user()?->isOwner(),
        ];

        $headers = ['Vary' => 'X-AZ-Fragment', 'Cache-Control' => 'private, no-store'];
        if ($request->header('X-AZ-Fragment') === '1') {
            return response()->json($routeData + [
                'html' => view('pages.'.$view, $viewData)->render(),
            ], 200, $headers);
        }

        $viewData['bootstrap'] = [
            'lang' => $locale,
            'strings' => $payload['strings'] ?? [],
            'canonical' => $hreflang[$locale],
            'cvUrl' => $cvHref,
            'preview' => (bool) $preview,
            'routeData' => $routeData,
        ];

        return response()->view('site.app', $viewData, 200, $headers);
    }

    protected function assetUrl(?string $path, string $fallback = ''): string
    {
        $path = trim((string) $path) ?: $fallback;

        return preg_match('#^https?://#i', $path) ? $path : asset(ltrim($path, '/'));
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
