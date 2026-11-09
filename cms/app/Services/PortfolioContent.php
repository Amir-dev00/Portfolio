<?php

namespace App\Services;

use App\Models\SiteState;

class PortfolioContent
{
    public function __construct(private PublicationService $publications) {}

    public function payload(?bool $preview = false): array
    {
        if ($preview) {
            return $this->publications->buildDraftPayload();
        }

        return $this->publications->currentPayload()
            ?? $this->publications->buildDraftPayload();
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
                'title' => $project['title'],
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
