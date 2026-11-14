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
            $payload = $this->publications->buildDraftPayload();
            if (! $preview) {
                unset($payload['experience']);
            }
        }

        $projects = $payload['projects'] ?? [];
        $existingSlugs = array_fill_keys(array_column($projects, 'slug'), true);
        $categories = [
            'website' => ['en' => 'Website', 'fa' => 'طراحی سایت'],
            'design' => ['en' => 'UI/UX Design', 'fa' => 'طراحی رابط و تجربهٔ کاربری'],
            'app' => ['en' => 'Mobile App', 'fa' => 'اپلیکیشن موبایل'],
        ];

        foreach (config('novin.projects', []) as $sourceSlug => $entry) {
            $slug = 'novin-'.$sourceSlug;
            if (isset($existingSlugs[$slug])) {
                continue;
            }

            $directory = 'assets/img/novin-coders/'.$sourceSlug;
            $project = [
                'slug' => $slug,
                'title' => $entry['title'],
                'image' => '/'.$directory.'/cover.webp',
                'gallery' => array_map(fn (int $index) => sprintf('/%s/gallery-%02d.webp', $directory, $index), range(1, $entry['gallery'])),
                'sourceUrl' => config('novin.url').'projects/show/?'.http_build_query(['slug' => $sourceSlug]),
                'technologies' => $entry['technologies'],
            ];

            foreach (['en', 'fa'] as $locale) {
                [$shortDescription, $fullDescription] = $entry[$locale];
                $project[$locale] = [
                    'title' => $locale === 'fa' ? $entry['title_fa'] : $entry['title'],
                    'shortDescription' => $shortDescription,
                    'fullDescription' => $fullDescription,
                    'category' => $categories[$entry['category']][$locale],
                    'role' => $locale === 'fa' ? 'پروژهٔ تیم نوین کدرز' : 'Novin Coders team project',
                ];
            }
            $projects[] = $project;
        }
        $payload['projects'] = $projects;

        return $payload;
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
                'image' => ltrim((string) ($project['image'] ?? ''), '/') ?: null,
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
