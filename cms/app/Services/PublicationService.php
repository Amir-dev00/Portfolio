<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\Publication;
use App\Models\ResumeVersion;
use App\Models\SeoEntry;
use App\Models\SiteSetting;
use App\Models\SiteState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PublicationService
{
    public function buildDraftPayload(): array
    {
        $strings = ['en' => [], 'fa' => []];
        foreach (ContentEntry::query()->orderBy('group')->orderBy('key')->get() as $entry) {
            $key = $entry->group === 'ui' ? $entry->key : $entry->group.'.'.$entry->key;
            if ($entry->group === 'ui') {
                $strings['en'][$entry->key] = $entry->value_en;
                $strings['fa'][$entry->key] = $entry->value_fa;
            } else {
                $strings['en'][$key] = $entry->value_en;
                $strings['fa'][$key] = $entry->value_fa;
            }
        }

        $projects = Project::query()
            ->active()
            ->with(['translations', 'technologies', 'cover'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Project $project) => $this->serializeProject($project))
            ->values()
            ->all();

        $seo = [];
        foreach (SeoEntry::query()->get() as $entry) {
            $seo[$entry->locale][$entry->scope][$entry->scope_key ?: '_'] = [
                'title' => $entry->title,
                'description' => $entry->description,
                'og_image' => $entry->og_image,
                'robots' => $entry->robots,
            ];
        }

        $settings = SiteSetting::query()->pluck('value', 'key')->all();
        $experience = collect($settings)
            ->filter(fn ($value, $key) => str_starts_with($key, SiteSetting::EXPERIENCE_PREFIX))
            ->sortByDesc('start_month');
        $logos = MediaAsset::query()->whereIn('id', $experience->pluck('logo_id')->filter()->unique())->get()->keyBy('id');
        $settings = array_diff_key($settings, $experience->all());
        $resume = ResumeVersion::query()->where('is_current', true)->with('pdf')->first();

        return [
            'version' => 1,
            'built_at' => now()->toIso8601String(),
            'strings' => $strings,
            'projects' => $projects,
            'experience' => $experience->map(function (array $entry) use ($logos) {
                $entry['logo_path'] = $logos->get($entry['logo_id'] ?? 0)?->public_path;

                return $entry;
            })->values()->all(),
            'seo' => $seo,
            'settings' => $settings,
            'resume' => $resume ? [
                'id' => $resume->id,
                'label' => $resume->label,
                'url' => $resume->pdf?->public_path ? '/'.$resume->pdf->public_path : null,
                'fields_en' => $resume->fields_en,
                'fields_fa' => $resume->fields_fa,
            ] : null,
            'social' => $settings['social'] ?? [],
            'profile' => $settings['profile'] ?? [],
            'featured_slugs' => $settings['featured_slugs'] ?? [],
            'booking' => $settings['booking'] ?? [],
        ];
    }

    public function serializeProject(Project $project): array
    {
        $en = $project->translation('en');
        $fa = $project->translation('fa');
        $coverPath = $project->cover?->public_path
            ?: ($project->cover?->path ? 'assets/img/amir/'.basename($project->cover->path) : null);

        return [
            'slug' => $project->slug,
            'title' => $project->title,
            'category' => $project->category,
            'date' => $project->year,
            'liveUrl' => $project->live_url,
            'githubUrl' => $project->github_url,
            'is_featured' => $project->is_featured,
            'sort_order' => $project->sort_order,
            'image' => $coverPath ? '/'.ltrim($coverPath, '/') : null,
            'technologies' => $project->technologies->pluck('name')->values()->all(),
            'en' => [
                'shortDescription' => $en?->short_description,
                'fullDescription' => $en?->full_description,
                'role' => $en?->role,
                'category' => $en?->category_label ?: $project->category,
            ],
            'fa' => [
                'shortDescription' => $fa?->short_description,
                'fullDescription' => $fa?->full_description,
                'role' => $fa?->role,
                'category' => $fa?->category_label ?: $project->category,
            ],
        ];
    }

    public function publish(?User $user = null, ?string $label = null): Publication
    {
        return DB::transaction(function () use ($user, $label) {
            $payload = $this->buildDraftPayload();
            $this->syncPublishedMedia($payload);

            // Refresh resume/project image paths after media sync.
            $payload = $this->buildDraftPayload();

            $publication = Publication::query()->create([
                'label' => $label ?: ('Publish '.now()->format('Y-m-d H:i')),
                'payload' => $payload,
                'published_by' => $user?->id,
                'published_at' => now(),
            ]);

            $state = SiteState::current();
            $state->current_publication_id = $publication->id;
            $state->save();

            ContentEntry::query()->update(['is_dirty' => false]);
            Project::query()->update(['is_dirty' => false]);
            SeoEntry::query()->update(['is_dirty' => false]);

            ContentRevision::query()->create([
                'entity_type' => 'publication',
                'entity_id' => $publication->id,
                'action' => 'publish',
                'after' => ['label' => $publication->label],
                'user_id' => $user?->id,
            ]);

            return $publication;
        });
    }

    public function rollback(Publication $publication, ?User $user = null): Publication
    {
        return DB::transaction(function () use ($publication, $user) {
            $state = SiteState::current();
            $state->current_publication_id = $publication->id;
            $state->save();

            ContentRevision::query()->create([
                'entity_type' => 'publication',
                'entity_id' => $publication->id,
                'action' => 'rollback',
                'after' => ['label' => $publication->label],
                'user_id' => $user?->id,
            ]);

            return $publication;
        });
    }

    public function currentPayload(): ?array
    {
        $state = SiteState::current();
        $publication = $state->publication;

        return $publication?->payload;
    }

    protected function syncPublishedMedia(array &$payload): void
    {
        $publicRoot = $this->publishedMediaRoot();
        File::ensureDirectoryExists($publicRoot.'/img');
        File::ensureDirectoryExists($publicRoot.'/cv');

        foreach (MediaAsset::query()->get() as $asset) {
            $src = $asset->absolutePath();
            if (! is_file($src)) {
                continue;
            }
            $basename = basename($asset->path);
            $isPdf = str_contains((string) $asset->mime, 'pdf') || str_ends_with(strtolower($basename), '.pdf');
            $sub = $isPdf ? 'cv/' : 'img/';
            $relative = 'media/published/'.$sub.$basename;
            $dest = $publicRoot.'/'.$sub.$basename;
            File::ensureDirectoryExists(dirname($dest));
            if (! is_file($dest) || filemtime($src) > filemtime($dest)) {
                File::copy($src, $dest);
            }
            $asset->public_path = $relative;
            $asset->is_published = true;
            $asset->save();
        }
    }

    public function publishedMediaRoot(): string
    {
        $configured = config('portfolio.published_media_path');
        if ($configured) {
            return $configured;
        }

        return public_path('media/published');
    }
}
