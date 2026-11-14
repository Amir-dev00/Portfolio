<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoEntry;
use App\Services\PortfolioContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeoController extends Controller
{
    private const PAGES = ['home', 'work', 'about', 'ask', 'contact'];

    public function edit(Request $request, PortfolioContent $content): View
    {
        $projects = array_column($content->projectsFor('en', true), 'title', 'slug');
        $target = $request->query('target', 'home');
        $target = is_string($target) && in_array($target, array_merge(self::PAGES, $this->projectTargets(array_keys($projects))), true) ? $target : 'home';
        [$scope, $key] = $this->scopeAndKey($target);
        $entries = SeoEntry::query()->where('scope', $scope)->where('scope_key', $key)->get()->keyBy('locale');

        return view('admin.seo.edit', [
            'pages' => self::PAGES,
            'projects' => $projects,
            'target' => $target,
            'label' => $projects[$key] ?? ucfirst($key),
            'entries' => $entries,
            'paths' => [
                'en' => route($scope === 'project' ? 'project' : $key, $scope === 'project' ? ['slug' => $key] : []),
                'fa' => route($scope === 'project' ? 'fa.project' : 'fa.'.$key, $scope === 'project' ? ['slug' => $key] : []),
            ],
        ]);
    }

    public function update(Request $request, PortfolioContent $content): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', 'string', Rule::in(array_merge(self::PAGES, $this->projectTargets(array_column($content->projectsFor('en', true), 'slug'))))],
            'seo' => ['required', 'array:en,fa'],
            'seo.*' => ['array:title,description,og_image,robots'],
            'seo.*.title' => ['nullable', 'string', 'max:190'],
            'seo.*.description' => ['nullable', 'string', 'max:2000'],
            'seo.*.og_image' => ['nullable', 'string', 'max:255', 'regex:~^(?:https?://|/?(?:assets/|media/published/))[^\s]+$~D'],
            'seo.*.robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow', 'index,nofollow'])],
        ]);
        [$scope, $key] = $this->scopeAndKey($data['target']);

        foreach ($data['seo'] as $locale => $values) {
            $entry = SeoEntry::query()->firstOrNew(['scope' => $scope, 'scope_key' => $key, 'locale' => $locale]);
            $entry->fill($values + ['robots' => 'index,follow']);
            $entry->robots ??= 'index,follow';
            if ($entry->isDirty()) {
                $entry->is_dirty = true;
                $entry->save();
            }
        }

        return back()->with('status', __('SEO draft saved. Publish when ready.'));
    }
    private function projectTargets(array $slugs): array
    {
        return array_map(fn (string $slug) => 'project:'.$slug, $slugs);
    }

    private function scopeAndKey(string $target): array
    {
        return str_starts_with($target, 'project:')
            ? ['project', substr($target, strlen('project:'))]
            : ['page', $target];
    }
}
