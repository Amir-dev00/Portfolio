<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\ProjectTranslation;
use App\Models\Publication;
use App\Models\SeoEntry;
use App\Models\SiteSetting;
use App\Models\SiteState;
use App\Services\BundledProjectImporter;
use App\Services\PortfolioContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request, PortfolioContent $content): View
    {
        $search = $request->query('q');
        $search = is_string($search) ? trim($search) : '';
        $status = $request->query('status');
        $projects = Project::query()->with('cover')->orderBy('sort_order')->orderBy('id');
        if ($search !== '') {
            $projects->where(fn ($query) => $query->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'));
        }
        if ($status === 'active') {
            $projects->active();
        } elseif ($status === 'archived') {
            $projects->whereNotNull('archived_at');
        }

        return view('admin.projects.index', [
            'projects' => $projects->get(),
            'unmanaged' => array_diff(array_column($content->bundledProjects(), 'slug'), Project::query()->pluck('slug')->all(),
                SiteSetting::query()->where('key', 'like', SiteSetting::PROJECT_PREFIX.'%')->get()->pluck('value.bundled_slug')->filter()->all()),
        ]);
    }

    public function import(BundledProjectImporter $importer): RedirectResponse
    {
        $importer->import();

        return back()->with('status', __('Bundled projects are ready to edit. Publish when ready.'));
    }

    public function create(): View
    {
        return view('admin.projects.edit', [
            'project' => new Project(['sort_order' => Project::query()->max('sort_order') + 1]),
            'media' => $this->imageLibrary(),
            'details' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = new Project;
        $this->persist($request, $project);

        return redirect()->route('admin.projects.edit', $project)->with('status', __('Project created (draft).'));
    }

    public function edit(Project $project): View
    {
        $project->load(['translations', 'technologies', 'cover', 'media']);

        return view('admin.projects.edit', [
            'project' => $project,
            'media' => $this->imageLibrary(),
            'details' => SiteSetting::query()->where('key', SiteSetting::PROJECT_PREFIX.$project->id)->first()?->value ?? [],
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->persist($request, $project);

        return back()->with('status', __('Project saved (draft). Publish to go live.'));
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

        return back()->with('status', __('Order updated (draft).'));
    }

    public function archive(Project $project): RedirectResponse
    {
        $project->archived_at = now();
        $project->is_dirty = true;
        $project->save();

        return back()->with('status', __('Project archived (draft).'));
    }

    public function restore(Project $project): RedirectResponse
    {
        $project->archived_at = null;
        $project->is_dirty = true;
        $project->save();

        return back()->with('status', __('Project restored (draft).'));
    }

    public function destroy(Request $request, Project $project, PortfolioContent $content): RedirectResponse
    {
        $request->validate(['confirm_delete' => ['required', 'accepted']]);

        DB::transaction(function () use ($project, $request, $content) {
            $state = SiteState::query()->lockForUpdate()->findOrFail(SiteState::current()->id);
            if (! $state->current_publication_id) {
                $snapshot = Publication::query()->create([
                    'label' => __('Site before first project deletion'),
                    'payload' => $content->payload(),
                    'published_at' => now(),
                ]);
                $state->publication()->associate($snapshot)->save();
            }

            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            $setting = SiteSetting::query()->firstOrNew(['key' => SiteSetting::PROJECT_PREFIX.$project->id]);
            $bundled = $setting->value['bundled_slug'] ?? null;
            if (! $bundled && in_array($project->slug, array_column($content->bundledProjects(), 'slug'), true)) {
                $bundled = $project->slug;
            }

            // Preserve the import marker so an intentionally deleted project cannot be re-imported.
            if ($bundled) {
                $setting->value = ['bundled_slug' => $bundled];
                $setting->save();
            } elseif ($setting->exists) {
                $setting->delete();
            }

            SeoEntry::query()->where('scope', 'project')->where('scope_key', $project->slug)->delete();
            $featured = SiteSetting::query()->where('key', 'featured_slugs')->lockForUpdate()->first();
            if ($featured) {
                $featured->update(['value' => array_values(array_diff($featured->value ?? [], [$project->slug]))]);
            }

            ContentRevision::query()->create([
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'action' => 'delete',
                'before' => $project->toArray(),
                'user_id' => $request->user()->id,
            ]);
            $project->delete();
        });

        return redirect()->route('admin.projects.index')->with('status', __('Project deleted from the draft. Publish live to remove it from the site.'));
    }

    private function imageLibrary()
    {
        return MediaAsset::query()->where('mime', 'like', 'image/%')->orderBy('original_name')->get();
    }

    protected function persist(Request $request, Project $project): void
    {
        $title = $request->input('title');
        if (! $request->filled('slug') && is_string($title)) {
            $request->merge(['slug' => Str::slug($title)]);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'alpha_dash:ascii', 'lowercase', 'max:190', Rule::unique('projects', 'slug')->ignore($project)],
            'category' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'string', 'max:16'],
            'live_url' => ['nullable', 'url:http,https', 'max:255'],
            'github_url' => ['nullable', 'url:http,https', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'cover_media_id' => ['nullable', Rule::exists('media_assets', 'id')->where(fn ($query) => $query->where('mime', 'like', 'image/%'))],
            'gallery_media' => ['nullable', 'array', 'max:40'],
            'gallery_media.*' => ['integer', 'distinct', Rule::exists('media_assets', 'id')->where(fn ($query) => $query->where('mime', 'like', 'image/%'))],
            'gallery' => ['nullable', 'string', 'max:12000'],
            'source' => ['nullable', 'url:http,https', 'max:255'],
            'cover' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'array:en,fa'],
            'details.*' => ['array:title,overview,features,approach'],
            'details.*.title' => ['nullable', 'string', 'max:190'],
            'details.*.overview' => ['nullable', 'string', 'max:5000'],
            'details.*.features' => ['nullable', 'string', 'max:5000'],
            'details.*.approach' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['nullable', 'string', 'max:3000'],
            'en_full' => ['nullable', 'string', 'max:20000'],
            'fa_full' => ['nullable', 'string', 'max:20000'],
            'en_short' => ['nullable', 'string', 'max:255'],
            'en_role' => ['nullable', 'string', 'max:255'],
            'fa_short' => ['nullable', 'string', 'max:255'],
            'fa_role' => ['nullable', 'string', 'max:255'],
            'fa_category' => ['nullable', 'string', 'max:255'],
        ]);

        $techs = array_values(array_unique(array_filter(array_map('trim', explode(',', $data['technologies'] ?? '')))));
        foreach ($techs as $tech) {
            if (mb_strlen($tech) > 255) {
                throw ValidationException::withMessages(['technologies' => __('Each technology must be at most 255 characters.')]);
            }
        }

        foreach (array_filter(array_merge([$data['cover'] ?? null], $this->lines($data['gallery'] ?? null))) as $path) {
            if (! preg_match('~^/?(?:assets/|media/published/)[a-zA-Z0-9_./-]+$~D', $path) || str_contains($path, '..')) {
                throw ValidationException::withMessages(['gallery' => __('Use a local asset or published-media path for each image.')]);
            }
        }

        DB::transaction(function () use ($data, $request, $project, $techs) {
            $before = $project->exists ? $project->toArray() : null;
            $previousSlug = $project->slug;
            $project->fill([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'category' => $data['category'] ?? null,
                'year' => $data['year'] ?? null,
                'live_url' => $data['live_url'] ?? null,
                'github_url' => $data['github_url'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_featured' => $request->boolean('is_featured'),
                'cover_media_id' => $data['cover_media_id'] ?? null,
                'is_dirty' => true,
            ])->save();

            if ($previousSlug && $previousSlug !== $project->slug) {
                foreach (SeoEntry::query()->where('scope', 'project')->where('scope_key', $previousSlug)->get() as $seo) {
                    SeoEntry::query()->firstOrCreate(
                        ['scope' => 'project', 'scope_key' => $project->slug, 'locale' => $seo->locale],
                        $seo->only(['title', 'description', 'og_image', 'robots']) + ['is_dirty' => true],
                    );
                }
            }

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
            foreach ($techs as $i => $name) {
                ProjectTechnology::query()->create([
                    'project_id' => $project->id,
                    'name' => $name,
                    'sort_order' => $i,
                ]);
            }

            if ($request->has('gallery_media_present')) {
                $project->media()->where('role', 'gallery')->delete();
                foreach ($data['gallery_media'] ?? [] as $index => $id) {
                    $project->media()->create(['media_asset_id' => $id, 'role' => 'gallery', 'sort_order' => $index]);
                }
            }

            $setting = SiteSetting::query()->firstOrNew(['key' => SiteSetting::PROJECT_PREFIX.$project->id]);
            $details = $setting->value ?? [];
            foreach (['source', 'cover', 'gallery'] as $field) {
                if (array_key_exists($field, $data)) {
                    $details[$field] = $field === 'gallery' ? $this->lines($data[$field]) : $data[$field];
                }
            }
            foreach ($data['details'] ?? [] as $locale => $copy) {
                $details[$locale] = [
                    'title' => $locale === 'fa' ? ($copy['title'] ?? null) : null,
                    'overview' => $copy['overview'] ?? null,
                    'features' => $this->lines($copy['features'] ?? null),
                    'approach' => $copy['approach'] ?? null,
                ];
            }
            $setting->value = $details;
            $setting->save();

            ContentRevision::query()->create([
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'action' => $before ? 'update' : 'create',
                'before' => $before,
                'after' => $project->fresh()->toArray() + ['details' => $details],
                'user_id' => $request->user()->id,
            ]);
        });
    }

    private function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text ?? ''))));
    }
}
