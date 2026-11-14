<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use App\Services\PortfolioContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function edit(Request $request, PortfolioContent $content): View
    {
        $defaults = $this->defaultStrings();
        $entries = ContentEntry::query()->get()->keyBy(fn (ContentEntry $entry) => $entry->group === 'ui' ? $entry->key : $entry->group.'.'.$entry->key);
        $groups = [];
        $keys = array_unique(array_merge(array_keys($defaults['en']), array_keys($defaults['fa']), $entries->keys()->all()));
        sort($keys);
        foreach ($keys as $key) {
            $group = match (strtok($key, '.')) {
                'nav', 'title' => 'Navigation',
                'home', 'preloader' => 'Home',
                'about' => 'About',
                'booking' => 'Booking',
                'contact' => 'Contact',
                'work', 'project', 'category' => 'Work',
                'assistant', 'guide' => 'Miro',
                default => 'General',
            };
            $entry = $entries->get($key);
            $groups[$group][] = [
                'key' => $key,
                'en' => $entry?->value_en ?? $defaults['en'][$key] ?? '',
                'fa' => $entry?->value_fa ?? $defaults['fa'][$key] ?? '',
                'default_en' => $defaults['en'][$key] ?? '',
                'default_fa' => $defaults['fa'][$key] ?? '',
            ];
        }
        ksort($groups);
        $group = $request->query('group');
        $group = is_string($group) && isset($groups[$group]) ? $group : null;

        return view('admin.content.edit', [
            'groups' => $groups,
            'group' => $group,
            'settings' => SiteSetting::query()->whereIn('key', ['profile', 'social', 'team', 'assistant_knowledge', 'featured_slugs', 'home_backgrounds', 'about_skills'])->pluck('value', 'key')->all(),
            'projects' => $group ? [] : $content->projectsFor('en', true),
            'imagePaths' => $group ? [] : MediaAsset::query()->where('mime', 'like', 'image/%')
                ->whereNotNull('public_path')->orderBy('original_name')->get(['public_path', 'original_name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $url = ['nullable', 'url:http,https', 'max:255'];
        $path = ['nullable', 'string', 'max:255', 'regex:~^/?(?:assets/|media/published/)(?!.*\.\.)[a-zA-Z0-9_./-]+$~D'];
        $data = $request->validate([
            'texts' => ['nullable', 'array', 'max:250'],
            'texts.*' => ['array:key,en,fa'],
            'texts.*.key' => ['required', 'string', 'max:128', 'distinct', 'regex:/^[a-zA-Z0-9_.-]+$/D'],
            'texts.*.en' => ['nullable', 'string', 'max:10000'],
            'texts.*.fa' => ['nullable', 'string', 'max:10000'],
            'profile' => ['array:photo,logo'],
            'profile.logo' => $path,
            'profile.photo' => $path,
            'social' => ['array:email,linkedin,github,telegram,telegram_handle'],
            'social.email' => ['nullable', 'email', 'max:255'],
            'social.linkedin' => $url,
            'social.github' => $url,
            'social.telegram' => $url,
            'social.telegram_handle' => ['nullable', 'string', 'max:80'],
            'team' => ['array:url'],
            'team.url' => $url,
            'assistant_knowledge' => ['array:en,fa'],
            'assistant_knowledge.*' => ['nullable', 'string', 'max:450'],
            'featured_slugs' => ['array', 'max:30'],
            'featured_slugs.*' => ['required', 'alpha_dash:ascii', 'distinct', 'max:190'],
            'home_backgrounds' => ['array', 'size:4'],
            'home_backgrounds.*' => ['array:desktop,mobile'],
            'home_backgrounds.*.desktop' => $path,
            'home_backgrounds.*.mobile' => $path,
            'about_skills' => ['array:frontend,languages,backend,other'],
            'about_skills.*' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->has('featured_slugs_present')) {
            $data['featured_slugs'] ??= [];
        }
        if (isset($data['about_skills'])) {
            $data['about_skills'] = array_map(
                fn (?string $value) => array_values(array_filter(array_map('trim', explode(',', $value ?? '')))),
                $data['about_skills'],
            );
        }

        DB::transaction(function () use ($data, $request) {
            if (! empty($data['texts'])) {
                $defaults = $this->defaultStrings();
                $entries = ContentEntry::query()->get()->keyBy(fn (ContentEntry $entry) => $entry->group === 'ui' ? $entry->key : $entry->group.'.'.$entry->key);
                foreach ($data['texts'] as $copy) {
                    $key = $copy['key'];
                    $entry = $entries->get($key);
                    if (! $entry && ! isset($defaults['en'][$key]) && ! isset($defaults['fa'][$key])) {
                        continue;
                    }
                    $entry ??= new ContentEntry(['key' => $key, 'group' => 'ui']);
                    $before = $entry->only(['value_en', 'value_fa']);
                    $after = [
                        'value_en' => $copy['en'] ?? $defaults['en'][$copy['key']] ?? null,
                        'value_fa' => $copy['fa'] ?? $defaults['fa'][$copy['key']] ?? null,
                    ];
                    $effective = [
                        'value_en' => $before['value_en'] ?? $defaults['en'][$copy['key']] ?? null,
                        'value_fa' => $before['value_fa'] ?? $defaults['fa'][$copy['key']] ?? null,
                    ];
                    if ($effective === $after) {
                        continue;
                    }
                    $entry->fill($after + ['is_dirty' => true])->save();
                    $this->revision('content_entry', $entry->id, $before, $after, $request);
                }
            }

            unset($data['texts']);
            foreach ($data as $key => $value) {
                $setting = SiteSetting::query()->firstOrNew(['key' => $key]);
                $before = $setting->value ?? [];
                $after = in_array($key, ['profile', 'social', 'team'], true) ? array_replace($before, $value) : $value;
                if ($before !== $after) {
                    $setting->value = $after;
                    $setting->save();
                    $this->revision('site_setting', $setting->id, $before, $after, $request);
                }
            }
        });

        return back()->with('status', __('Draft saved. Preview it or publish to update the public site.'));
    }

    private function defaultStrings(): array
    {
        return json_decode(file_get_contents(config_path('portfolio-strings.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    private function revision(string $entity, int $id, array $before, array $after, Request $request): void
    {
        ContentRevision::query()->create([
            'entity_type' => $entity, 'entity_id' => $id, 'action' => 'update',
            'before' => $before, 'after' => $after, 'user_id' => $request->user()->id,
        ]);
    }
}
