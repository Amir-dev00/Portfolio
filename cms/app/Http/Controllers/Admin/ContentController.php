<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function edit(): View
    {
        $entries = ContentEntry::query()->orderBy('group')->orderBy('key')->get();
        $grouped = $entries->groupBy(function (ContentEntry $e) {
            if (str_starts_with($e->key, 'nav.')) {
                return 'Navigation';
            }
            if (str_starts_with($e->key, 'home.') || str_starts_with($e->key, 'preloader.')) {
                return 'Home';
            }
            if (str_starts_with($e->key, 'about.')) {
                return 'About';
            }
            if (str_starts_with($e->key, 'booking.') || $e->key === 'contact.bookCall') {
                return 'Booking';
            }
            if (str_starts_with($e->key, 'contact.')) {
                return 'Contact';
            }
            if (str_starts_with($e->key, 'work.') || str_starts_with($e->key, 'project.') || str_starts_with($e->key, 'category.')) {
                return 'Work UI';
            }

            return 'General';
        });

        return view('admin.content.edit', [
            'grouped' => $grouped,
            'social' => SiteSetting::query()->where('key', 'social')->value('value') ?? [],
            'profile' => SiteSetting::query()->where('key', 'profile')->value('value') ?? [],
            'featured' => SiteSetting::query()->where('key', 'featured_slugs')->value('value') ?? [],
            'booking' => array_merge(
                ['enabled' => true, 'duration' => 30, 'timezone' => 'Asia/Tehran'],
                (array) (SiteSetting::query()->where('key', 'booking')->value('value') ?? [])
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entries' => ['array'],
            'entries.*.en' => ['nullable', 'string'],
            'entries.*.fa' => ['nullable', 'string'],
            'social' => ['array'],
            'profile' => ['array'],
            'featured_slugs' => ['nullable', 'string'],
            'booking' => ['nullable', 'array'],
            'booking.enabled' => ['nullable', 'boolean'],
            'booking.duration' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        foreach ($data['entries'] ?? [] as $id => $values) {
            $entry = ContentEntry::query()->find($id);
            if (! $entry) {
                continue;
            }
            $before = $entry->only(['value_en', 'value_fa']);
            $entry->value_en = $values['en'] ?? null;
            $entry->value_fa = $values['fa'] ?? null;
            $entry->is_dirty = true;
            $entry->save();
            ContentRevision::query()->create([
                'entity_type' => 'content_entry',
                'entity_id' => $entry->id,
                'action' => 'update',
                'before' => $before,
                'after' => $entry->only(['value_en', 'value_fa']),
                'user_id' => $request->user()->id,
            ]);
        }

        if (isset($data['social'])) {
            SiteSetting::query()->updateOrCreate(['key' => 'social'], ['value' => $data['social']]);
        }
        if (isset($data['profile'])) {
            SiteSetting::query()->updateOrCreate(['key' => 'profile'], ['value' => $data['profile']]);
        }
        if (isset($data['booking'])) {
            $before = (array) (SiteSetting::query()->where('key', 'booking')->value('value') ?? []);
            $after = array_merge($before, [
                'enabled' => (bool) ($data['booking']['enabled'] ?? false),
                'duration' => isset($data['booking']['duration']) ? (int) $data['booking']['duration'] : ($before['duration'] ?? 30),
            ]);
            unset($after['url'], $after['provider']);
            $setting = SiteSetting::query()->updateOrCreate(['key' => 'booking'], ['value' => $after]);
            if ($before !== $after) {
                ContentRevision::query()->create([
                    'entity_type' => 'site_setting',
                    'entity_id' => $setting->id,
                    'action' => 'update',
                    'before' => $before,
                    'after' => $after,
                    'user_id' => $request->user()->id,
                ]);
            }
        }
        if (array_key_exists('featured_slugs', $data)) {
            $slugs = array_values(array_filter(array_map('trim', explode(',', (string) $data['featured_slugs']))));
            SiteSetting::query()->updateOrCreate(['key' => 'featured_slugs'], ['value' => $slugs]);
        }

        return back()->with('status', 'Draft content saved. Publish to update the public site.');
    }
}
