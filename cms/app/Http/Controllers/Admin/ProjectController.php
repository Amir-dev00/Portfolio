<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\ProjectTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('admin.projects.index', [
            'projects' => Project::query()->with('translations')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.edit', [
            'project' => new Project(['sort_order' => Project::query()->max('sort_order') + 1]),
            'media' => MediaAsset::query()->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = new Project;
        $this->persist($request, $project);

        return redirect()->route('admin.projects.edit', $project)->with('status', 'Project created (draft).');
    }

    public function edit(Project $project): View
    {
        $project->load(['translations', 'technologies', 'cover']);

        return view('admin.projects.edit', [
            'project' => $project,
            'media' => MediaAsset::query()->latest()->limit(100)->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->persist($request, $project);

        return back()->with('status', 'Project saved (draft). Publish to go live.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:projects,id'],
        ]);
        foreach ($data['order'] as $i => $id) {
            Project::query()->where('id', $id)->update(['sort_order' => $i, 'is_dirty' => true]);
        }

        return back()->with('status', 'Order updated (draft).');
    }

    public function archive(Project $project): RedirectResponse
    {
        $project->archived_at = now();
        $project->is_dirty = true;
        $project->save();

        return back()->with('status', 'Project archived (draft).');
    }

    public function restore(Project $project): RedirectResponse
    {
        $project->archived_at = null;
        $project->is_dirty = true;
        $project->save();

        return back()->with('status', 'Project restored (draft).');
    }

    protected function persist(Request $request, Project $project): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'category' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'string', 'max:16'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'cover_media_id' => ['nullable', 'exists:media_assets,id'],
            'technologies' => ['nullable', 'string'],
            'en_short' => ['nullable', 'string'],
            'en_full' => ['nullable', 'string'],
            'en_role' => ['nullable', 'string'],
            'fa_short' => ['nullable', 'string'],
            'fa_full' => ['nullable', 'string'],
            'fa_role' => ['nullable', 'string'],
            'fa_category' => ['nullable', 'string'],
        ]);

        $before = $project->exists ? $project->toArray() : null;
        $slug = $data['slug'] ?: Str::slug($data['title']);
        $project->fill([
            'title' => $data['title'],
            'slug' => $slug,
            'category' => $data['category'] ?? null,
            'year' => $data['year'] ?? null,
            'live_url' => $data['live_url'] ?? null,
            'github_url' => $data['github_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
            'cover_media_id' => $data['cover_media_id'] ?? null,
            'is_dirty' => true,
        ])->save();

        ProjectTranslation::query()->updateOrCreate(
            ['project_id' => $project->id, 'locale' => 'en'],
            [
                'short_description' => $data['en_short'] ?? null,
                'full_description' => $data['en_full'] ?? null,
                'role' => $data['en_role'] ?? null,
                'category_label' => $data['category'] ?? null,
            ]
        );
        ProjectTranslation::query()->updateOrCreate(
            ['project_id' => $project->id, 'locale' => 'fa'],
            [
                'short_description' => $data['fa_short'] ?? null,
                'full_description' => $data['fa_full'] ?? null,
                'role' => $data['fa_role'] ?? null,
                'category_label' => $data['fa_category'] ?? null,
            ]
        );

        $project->technologies()->delete();
        $techs = array_values(array_filter(array_map('trim', explode(',', (string) ($data['technologies'] ?? '')))));
        foreach ($techs as $i => $name) {
            ProjectTechnology::query()->create([
                'project_id' => $project->id,
                'name' => $name,
                'sort_order' => $i,
            ]);
        }

        ContentRevision::query()->create([
            'entity_type' => 'project',
            'entity_id' => $project->id,
            'action' => $before ? 'update' : 'create',
            'before' => $before,
            'after' => $project->fresh()->toArray(),
            'user_id' => $request->user()->id,
        ]);
    }
}
