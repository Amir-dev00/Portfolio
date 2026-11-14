<?php

namespace App\Services;

use App\Models\SiteState;

class PortfolioContent
{
    public function __construct(private PublicationService $publications) {}

    public function payload(?bool $preview = false): array
    {
        $payload = $preview ? null : $this->publications->currentPayload();
        if ($payload === null) {
            $payload = $this->publications->buildDraftPayload((bool) $preview);
            if (! $preview) {
                unset($payload['experience']);
            }
        }

        $projects = $payload['projects'] ?? [];
        $existingSlugs = array_fill_keys(array_column($projects, 'slug'), true);
        foreach ($this->bundledProjects() as $project) {
            if (! isset($existingSlugs[$project['slug']]) &&
                ! in_array($project['slug'], $payload['managed_bundled_projects'] ?? [], true)) {
                $projects[] = $project;
            }
        }
        $payload['projects'] = $projects;

        return $payload;
    }

    public function bundledProjects(): array
    {
        $projects = [];
        $categories = [
            'website' => ['en' => 'Website', 'fa' => 'طراحی سایت'],
            'design' => ['en' => 'UI/UX Design', 'fa' => 'طراحی رابط و تجربهٔ کاربری'],
            'app' => ['en' => 'Mobile App', 'fa' => 'اپلیکیشن موبایل'],
            'pwa' => ['en' => 'Progressive Web App', 'fa' => 'وب‌اپ پیش‌رونده'],
        ];

        $caseStudies = config('novin-case-studies', []);

        foreach (config('novin.projects', []) as $sourceSlug => $entry) {
            $slug = 'novin-'.$sourceSlug;
            $directory = 'assets/img/novin-coders/'.$sourceSlug;
            $project = [
                'slug' => $slug,
                'title' => $entry['title'],
                'image' => '/'.$directory.'/cover.webp',
                'gallery' => array_map(fn (int $index) => sprintf('/%s/gallery-%02d.webp', $directory, $index), range(1, $entry['gallery'])),
                'liveUrl' => $entry['live_url'] ?? null,
                'sourceUrl' => isset($entry['live_url'])
                    ? null
                    : config('novin.url').'projects/show/?'.http_build_query(['slug' => $sourceSlug]),
                'technologies' => $entry['technologies'],
            ];

            foreach (['en', 'fa'] as $locale) {
                [$shortDescription, $fullDescription] = $entry[$locale];
                $project[$locale] = [
                    'title' => $locale === 'fa' ? $entry['title_fa'] : $entry['title'],
                    'shortDescription' => $shortDescription,
                    'fullDescription' => $fullDescription,
                    'caseStudy' => $caseStudies[$sourceSlug][$locale] ?? [],
                    'category' => $categories[$entry['category']][$locale],
                    'role' => $locale === 'fa' ? 'امیرحسین زمان‌زاده (توسعه‌دهنده)' : 'Amirhossein Zamanzade (developer)',
                ];
            }
            $projects[] = $project;
        }

        return $projects;
    }

    public function strings(string $locale, ?bool $preview = false): array
    {
        $payload = $this->payload($preview);

        return $payload['strings'][$locale] ?? $payload['strings']['en'] ?? [];
    }

    public function projectsFor(string $locale, ?bool $preview = false): array
    {
        $payload = $this->payload($preview);
        $projects = $payload['projects'] ?? [];

        return array_map(function (array $project) use ($locale) {
            $copy = $project[$locale] ?? $project['en'] ?? [];

            return [
                'slug' => $project['slug'],
                'title' => $copy['title'] ?? $project['title'],
                'shortDescription' => $copy['shortDescription'] ?? '',
                'fullDescription' => $copy['fullDescription'] ?? '',
                'category' => $copy['category'] ?? $project['category'] ?? '',
                'technologies' => $project['technologies'] ?? [],
                'image' => $project['image'] ?? null,
                'liveUrl' => $project['liveUrl'] ?? null,
                'githubUrl' => $project['githubUrl'] ?? null,
                'date' => $project['date'] ?? null,
                'role' => $copy['role'] ?? '',
            ];
        }, $projects);
    }

    public function seo(string $locale, string $scope, ?string $scopeKey = null, ?bool $preview = false): array
    {
        $payload = $this->payload($preview);
        $bucket = $payload['seo'][$locale][$scope][$scopeKey ?: '_']
            ?? $payload['seo']['en'][$scope][$scopeKey ?: '_']
            ?? [];

        return [
            'title' => $bucket['title'] ?? null,
            'description' => $bucket['description'] ?? null,
            'og_image' => $bucket['og_image'] ?? null,
            'robots' => $bucket['robots'] ?? 'index,follow',
        ];
    }

    public function currentPublicationId(): ?int
    {
        return SiteState::current()->current_publication_id;
    }
}
