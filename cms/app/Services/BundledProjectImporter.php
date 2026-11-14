<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

class BundledProjectImporter
{
    public function __construct(private PortfolioContent $content) {}

    public function import(): void
    {
        DB::transaction(function () {
            $order = (int) Project::query()->max('sort_order');

            $managed = SiteSetting::query()->where('key', 'like', SiteSetting::PROJECT_PREFIX.'%')
                ->get()->pluck('value.bundled_slug')->filter()->all();
            foreach ($this->content->bundledProjects() as $entry) {
                if (in_array($entry['slug'], $managed, true)) {
                    continue;
                }
                $project = Project::query()->firstOrCreate(
                    ['slug' => $entry['slug']],
                    ['title' => $entry['title'], 'category' => $entry['en']['category'], 'sort_order' => ++$order, 'is_dirty' => true],
                );

                if (! $project->wasRecentlyCreated) {
                    continue;
                }

                $details = [
                    'bundled_slug' => $entry['slug'],
                    'cover' => $entry['image'],
                    'gallery' => $entry['gallery'],
                    'source' => $entry['sourceUrl'],
                ];

                foreach (['en', 'fa'] as $locale) {
                    $copy = $entry[$locale];
                    $project->translations()->create([
                        'locale' => $locale,
                        'short_description' => $copy['shortDescription'],
                        'full_description' => $copy['fullDescription'],
                        'category_label' => $copy['category'],
                        'role' => $copy['role'],
                    ]);
                    $details[$locale] = ['title' => $copy['title']] + $copy['caseStudy'];
                }

                foreach ($entry['technologies'] as $index => $technology) {
                    $project->technologies()->create(['name' => $technology, 'sort_order' => $index]);
                }

                SiteSetting::query()->create([
                    'key' => SiteSetting::PROJECT_PREFIX.$project->id,
                    'value' => $details,
                ]);
            }
        });
    }
}
