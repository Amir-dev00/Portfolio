<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ExperienceController extends Controller
{
    public function index(): View
    {
        $entries = SiteSetting::query()->where('key', 'like', SiteSetting::EXPERIENCE_PREFIX.'%')
            ->get()->keyBy('key');
        foreach (config('work-experience', []) as $id => $value) {
            $key = SiteSetting::EXPERIENCE_PREFIX.$id;
            if (! $entries->has($key) && ! $entries->contains(fn (SiteSetting $entry) => strcasecmp(rtrim($entry->value['website'] ?? '', '/'), rtrim($value['website'], '/')) === 0)) {
                $entries->put($key, new SiteSetting(['key' => $key, 'value' => $value]));
            }
        }
        $entries = $entries->reject(fn (SiteSetting $entry) => ! empty($entry->value['removed']))
            ->sortByDesc(fn (SiteSetting $entry) => $entry->value['start_month']);

        return view('admin.experience.index', compact('entries'));
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(string $experience): View
    {
        return $this->form($this->find($experience));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new SiteSetting(['key' => SiteSetting::EXPERIENCE_PREFIX.Str::uuid()]));
    }

    public function update(Request $request, string $experience): RedirectResponse
    {
        return $this->save($request, $this->find($experience));
    }

    public function destroy(string $experience): RedirectResponse
    {
        $entry = $this->find($experience);
        if (array_key_exists($experience, config('work-experience', []))) {
            $entry->value = ['removed' => true];
            $entry->save();
        } else {
            $entry->delete();
        }

        return redirect()->route('admin.experience.index')->with('status', __('Experience removed from draft. Publish live to update the site.'));
    }

    private function find(string $experience): SiteSetting
    {
        $key = SiteSetting::EXPERIENCE_PREFIX.$experience;
        $entry = SiteSetting::query()->where('key', $key)->first();
        if ($entry) {
            abort_if(! empty($entry->value['removed']), 404);

            return $entry;
        }

        $defaults = config('work-experience', []);
        abort_unless(isset($defaults[$experience]), 404);

        return new SiteSetting(['key' => $key, 'value' => $defaults[$experience]]);

    }

    private function form(?SiteSetting $entry): View
    {
        $experience = $entry?->value ?? [];
        $logoId = $experience['logo_id'] ?? null;

        return view('admin.experience.form', [
            'entry' => $entry,
            'experience' => $experience,
            'logo' => $logoId ? MediaAsset::find($logoId) : null,
            'types' => SiteSetting::EXPERIENCE_TYPES,
        ]);
    }

    private function save(Request $request, SiteSetting $entry): RedirectResponse
    {
        $data = $request->validate([
            'company_en' => ['required', 'string', 'max:150'],
            'company_fa' => ['required', 'string', 'max:150'],
            'role_en' => ['required', 'string', 'max:150'],
            'role_fa' => ['required', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(array_keys(SiteSetting::EXPERIENCE_TYPES))],
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month' => ['nullable', 'date_format:Y-m', 'after_or_equal:start_month'],
            'website' => ['nullable', 'url:http,https', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:2500'],
            'description_fa' => ['nullable', 'string', 'max:2500'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $file = $data['logo'] ?? null;
        $removeLogo = $data['remove_logo'] ?? false;
        unset($data['logo'], $data['remove_logo']);
        $path = null;

        try {
            DB::transaction(function () use ($entry, $data, $file, $removeLogo, &$path) {
                $record = $entry->exists ? SiteSetting::query()->lockForUpdate()->findOrFail($entry->id) : $entry;
                $data['logo_id'] = $removeLogo ? null : ($record->value['logo_id'] ?? null);
                if (! $removeLogo && ! $file && ! empty($record->value['logo_path'])) {
                    $data['logo_path'] = $record->value['logo_path'];
                }
                if (($record->value['start_month'] ?? null) === $data['start_month'] && ! empty($record->value['start_label_fa'])) {
                    $data['start_label_fa'] = $record->value['start_label_fa'];
                }
                if ($file) {
                    $path = $file->store('media/drafts/img', 'local');
                    if ($path === false) {
                        throw new RuntimeException('The company logo could not be saved.');
                    }
                    $data['logo_id'] = MediaAsset::create([
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'is_published' => false,
                        'used_by' => ['work-experience'],
                    ])->id;
                }

                $record->value = $data;
                $record->save();
            });
        } catch (Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }

        return redirect()->route('admin.experience.index')->with('status', __('Experience saved as draft. Publish live to update the site.'));
    }
}
