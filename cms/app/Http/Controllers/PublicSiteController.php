<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\PortfolioContent;
use App\Support\ProfilePortrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        $payload = $this->content->payload();
        $portrait = $this->assetUrl(ProfilePortrait::path($payload['profile']['photo'] ?? null));
        $urls = [];

        foreach (['en', 'fa'] as $locale) {
            foreach (['home', 'work', 'about', 'ask', 'contact'] as $page) {
                if ($this->isIndexable($this->seoFor($payload, $locale, 'page', $page))) {
                    $urls[] = [
                        'loc' => $this->urlFor($locale, $page),
                        'images' => $page === 'about' ? [$portrait] : [],
                    ];
                }
            }

            foreach ($payload['projects'] ?? [] as $project) {
                if ($this->isIndexable($this->seoFor($payload, $locale, 'project', $project['slug']))) {
                    $images = array_filter([$project['image'] ?? null, ...($project['gallery'] ?? [])]);
                    $urls[] = [
                        'loc' => $this->urlFor($locale, 'project', $project['slug']),
                        'images' => array_unique(array_map(fn (string $path) => $this->assetUrl($path), $images)),
                    ];
                }
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
        $seo = $this->seoFor($payload, $locale, $scope, $scopeKey);

        $projects = match ($view) {
            'work' => $payload['projects'] ?? [],
            'project' => array_values(array_filter($payload['projects'] ?? [], fn (array $item) => $item['slug'] === $slug)),
            default => [],
        };
        $projects = array_map(function (array $item) use ($locale, $view) {
            $copy = $item[$locale] ?? $item['en'] ?? [];

            return [
                'slug' => $item['slug'],
                'title' => $copy['title'] ?? $item['title'],
                'category' => $copy['category'] ?? $item['category'] ?? '',
                'shortDescription' => $copy['shortDescription'] ?? '',
                'fullDescription' => $view === 'project' ? ($copy['fullDescription'] ?? '') : '',
                'caseStudy' => $view === 'project' ? ($copy['caseStudy'] ?? []) : [],
                'role' => $view === 'project' ? ($copy['role'] ?? '') : '',
                'technologies' => $item['technologies'] ?? [],
                'image' => empty($item['image']) ? null : $this->assetUrl($item['image']),
                'gallery' => $view === 'project' ? array_map(fn (string $path) => $this->assetUrl($path), $item['gallery'] ?? []) : [],
                'sourceUrl' => $view === 'project' ? ($item['sourceUrl'] ?? null) : null,
                'liveUrl' => $item['liveUrl'] ?? null,
                'websiteUrl' => $view === 'project' ? ($item['websiteUrl'] ?? null) : null,
                'githubUrl' => $item['githubUrl'] ?? null,
                'date' => $item['date'] ?? null,
            ];
        }, $projects);
        $project = $projects[0] ?? null;

        if ($view === 'project') {
            abort_unless($project, 404);
        }

        $description = match ($view) {
            'project' => $project['shortDescription'] ?: $project['fullDescription'],
            'work' => $strings['work.lead'] ?? '',
            'about' => $strings['about.p1'] ?? '',
            'ask' => $strings['assistant.title'] ?? '',
            'contact' => $strings['contact.intro'] ?? '',
            default => '',
        };
        $seo['title'] = ($seo['title'] ?? '') ?: ($project
            ? str_replace('{name}', $project['title'], $strings['title.project'] ?? '{name}')
            : ($strings['title.'.$view] ?? $strings['title.base'] ?? 'Portfolio'));
        $seo['description'] = ($seo['description'] ?? '') ?: Str::limit(
            (string) Str::squish(strip_tags($description ?: ($strings['meta.description'] ?? ''))),
            160,
            '…'
        );
        $seo['robots'] = ($seo['robots'] ?? '') ?: 'index,follow';
        $previewRequested = $request->boolean('preview');
        if ($previewRequested) {
            $seo['robots'] = 'noindex,nofollow';
        } elseif ($this->isIndexable($seo) && ! str_contains(strtolower($seo['robots']), 'max-image-preview')) {
            $seo['robots'] .= ',max-image-preview:large';
        }

        $hreflang = [
            'en' => $this->urlFor('en', $view, $slug),
            'fa' => $this->urlFor('fa', $view, $slug),
        ];

        $cvHref = $this->assetUrl($payload['resume']['url'] ?? '', 'assets/cv/Amirhossein-Zamanzade-CV.pdf');
        $profile = $payload['profile'] ?? [];
        $profilePhoto = $this->assetUrl(ProfilePortrait::path($profile['photo'] ?? null));
        $social = array_merge([
            'email' => 'itsamirzamani@gmail.com',
            'linkedin' => 'https://linkedin.com/in/amirhossein-zamanzade',
            'github' => 'https://github.com/Amir-dev00',
            ...config('portfolio.contact'),
        ], array_filter((array) ($payload['social'] ?? []), fn ($value) => is_string($value) && trim($value) !== ''));
        $booking = $this->bookingConfig((array) ($payload['booking'] ?? $payload['settings']['booking'] ?? []));
        $ogImage = $this->assetUrl(
            $seo['og_image'] ?? '',
            $project['image'] ?? (in_array($view, ['home', 'about'], true) ? $profilePhoto : 'assets/img/amir/az-monogram-blue.webp')
        );
        $profileSchema = $view === 'about' && $this->isIndexable($seo)
            ? $this->profileSchema($hreflang, $locale, $payload['strings'], $profilePhoto, $social)
            : null;
        $routeData = [
            'projects' => $projects,
            'seo' => $seo,
            'hreflang' => $hreflang,
            'ogImage' => $ogImage,
            'profileSchema' => $profileSchema,
        ];

        $assetV = static function (string $path): string {
            $file = public_path($path);

            return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
        };

        $bundledProfile = ProfilePortrait::DEFAULT_PATH;
        $usesBundledProfile = $profilePhoto === asset($bundledProfile);

        $pages = ['home', 'work', 'about', 'ask', 'contact'];
        $viewData = [
            'locale' => $locale,
            'dir' => $locale === 'fa' ? 'rtl' : 'ltr',
            'viewName' => $view,
            'activeView' => $view,
            'slug' => $slug,
            'project' => $view === 'project' ? $project : null,
            'workProjects' => $view === 'work' ? $projects : [],
            'projectUrl' => fn (string $projectSlug) => $this->urlFor($locale, 'project', $projectSlug).($preview ? '?preview=1' : ''),
            'profileSchema' => $profileSchema,
            'ogImage' => $ogImage,
            'experience' => $view === 'work' ? $this->workExperience($payload['experience'] ?? [], $locale, (bool) $preview) : [],
            'strings' => $strings,
            'seo' => $seo,
            'hreflang' => $hreflang,
            'canonical' => $hreflang[$locale],
            'cvHref' => $cvHref,
            'assetV' => $assetV,
            'navPaths' => array_combine($pages, array_map(fn (string $page) => $this->urlFor($locale, $page).($preview ? '?preview=1' : ''), $pages)),
            'social' => $social,
            'profile' => $profile,
            'homeBackgrounds' => array_map(function (int $slide) use ($payload) {
                $paths = $payload['settings']['home_backgrounds'][$slide] ?? [];

                return [
                    'desktop' => $this->assetUrl($paths['desktop'] ?? '', sprintf('assets/img/amir/home/home-bg-%02d.webp', $slide)),
                    'mobile' => $this->assetUrl($paths['mobile'] ?? '', sprintf('assets/img/amir/home/home-mobile-bg-%02d.webp', $slide)),
                    'customMobile' => ! empty($paths['mobile']),
                ];
            }, range(1, 4)),
            'aboutSkills' => array_replace(config('portfolio.about_skills'), $payload['settings']['about_skills'] ?? []),
            'teamUrl' => $payload['settings']['team']['url'] ?? config('novin.url'),
            'indexablePortrait' => $profilePhoto,
            'profilePhoto' => $usesBundledProfile ? $assetV($bundledProfile) : $profilePhoto,
            'homePortrait' => $usesBundledProfile ? $assetV('assets/img/amir/profile-home.webp') : $profilePhoto,
            'booking' => $booking,
            'bookingAdmin' => (bool) $request->user()?->isOwner(),
        ];

        $headers = ['Vary' => 'X-AZ-Fragment', 'Cache-Control' => 'private, no-store'];
        if ($previewRequested) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }
        if ($request->header('X-AZ-Fragment') === '1') {
            $headers['X-Robots-Tag'] ??= 'noindex';
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

    private function seoFor(array $payload, string $locale, string $scope, string $key): array
    {
        return $payload['seo'][$locale][$scope][$key] ?? $payload['seo']['en'][$scope][$key] ?? [];
    }

    private function isIndexable(array $seo): bool
    {
        return ! preg_match('/\b(?:noindex|none)\b/i', $seo['robots'] ?? '');
    }

    private function profileSchema(array $urls, string $locale, array $strings, string $portrait, array $social): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            '@id' => $urls[$locale].'#profile',
            'url' => $urls[$locale],
            'inLanguage' => $locale,
            'mainEntity' => [
                '@type' => 'Person',
                '@id' => $urls['en'].'#person',
                'name' => $strings['en']['home.profile.name'] ?? 'Amirhossein Zamanzade',
                'alternateName' => $strings['fa']['home.profile.name'] ?? 'امیرحسین زمان زاده',
                'jobTitle' => $strings[$locale]['home.profile.role'] ?? 'Software Engineer',
                'image' => $portrait,
                'url' => $urls['en'],
                'sameAs' => array_values(array_filter(
                    [$social['linkedin'], $social['github']],
                    fn (string $link) => str_starts_with($link, 'https://')
                )),
            ],
        ];
    }

    private function workExperience(array $entries, string $locale, bool $preview): array
    {
        $monthIndex = static fn (string $month): int => (int) substr($month, 0, 4) * 12 + (int) substr($month, 5, 2);
        $currentMonth = $monthIndex(now()->format('Y-m'));

        return array_map(function (array $entry) use ($locale, $preview, $monthIndex, $currentMonth) {
            $end = $entry['end_month'] ?? null;
            $start = $entry['start_month'];
            $months = max(0, ($end ? $monthIndex($end) : $currentMonth) - $monthIndex($start));
            $years = intdiv($months, 12);
            $remaining = $months % 12;
            $duration = array_filter([
                $years ? $years.($locale === 'fa' ? ' سال' : ($years === 1 ? ' yr' : ' yrs')) : null,
                $remaining ? $remaining.($locale === 'fa' ? ' ماه' : ($remaining === 1 ? ' mo' : ' mos')) : null,
            ]);

            return $entry + [
                'company' => $entry['company_'.$locale],
                'role' => $entry['role_'.$locale],
                'description' => $entry['description_'.$locale] ?? '',
                'type_label' => SiteSetting::EXPERIENCE_TYPES[$entry['type'] ?? ''][$locale] ?? '',
                'start_label' => $entry['start_label_'.$locale] ?? $this->workMonthLabel($start, $locale),
                'end_label' => $end ? $this->workMonthLabel($end, $locale) : ($locale === 'fa' ? 'تا امروز' : 'Present'),
                'duration' => implode(' · ', $duration),
                'logo_url' => $preview && !empty($entry['logo_id'])
                    ? route('admin.media.raw', $entry['logo_id'])
                    : (!empty($entry['logo_path']) ? $this->assetUrl($entry['logo_path']) : null),
            ];
        }, $entries);
    }

    private function workMonthLabel(string $month, string $locale): string
    {
        return Carbon::createFromFormat('!Y-m', $month)->locale($locale)->translatedFormat('M Y');
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
