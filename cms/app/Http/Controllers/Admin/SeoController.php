<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SeoEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoController extends Controller
{
    public function edit(): View
    {
        $entries = SeoEntry::query()->orderBy('scope')->orderBy('scope_key')->orderBy('locale')->get();
        $projects = Project::query()->active()->orderBy('sort_order')->get(['id', 'slug', 'title']);

        return view('admin.seo.edit', compact('entries', 'projects'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seo' => ['array'],
            'seo.*.title' => ['nullable', 'string', 'max:190'],
            'seo.*.description' => ['nullable', 'string'],
            'seo.*.og_image' => ['nullable', 'string', 'max:255'],
            'seo.*.robots' => ['nullable', 'string', 'max:64'],
        ]);

        foreach ($data['seo'] ?? [] as $id => $values) {
            $entry = SeoEntry::query()->find($id);
            if (! $entry) {
                continue;
            }
            $entry->fill([
                'title' => $values['title'] ?? null,
                'description' => $values['description'] ?? null,
                'og_image' => $values['og_image'] ?? null,
                'robots' => $values['robots'] ?? 'index,follow',
                'is_dirty' => true,
            ])->save();
        }

        return back()->with('status', 'SEO draft saved.');
    }
}
