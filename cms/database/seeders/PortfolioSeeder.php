<?php

namespace Database\Seeders;

use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\ProjectTranslation;
use App\Models\SeoEntry;
use App\Models\SiteSetting;
use App\Models\SiteState;
use App\Services\PublicationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $root = dirname(base_path()); // Personal Site root
        $i18nPath = $root.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'az-i18n.js';
        $projectsPath = $root.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'az-projects.js';

        [$stringsEn, $stringsFa, $projectCopyFa] = $this->parseI18n($i18nPath);
        $projects = $this->parseProjects($projectsPath);
        if (! count($stringsFa)) {
            throw new \RuntimeException('Failed to parse Persian strings from az-i18n.js');
        }

        foreach ($stringsEn as $key => $value) {
            ContentEntry::query()->updateOrCreate(
                ['group' => 'ui', 'key' => $key],
                [
                    'value_en' => $value,
                    'value_fa' => $stringsFa[$key] ?? null,
                    'is_dirty' => false,
                ]
            );
        }

        SiteSetting::query()->updateOrCreate(['key' => 'social'], [
            'value' => [
                'email' => 'itsamirzamani@gmail.com',
                'linkedin' => 'https://linkedin.com/in/amirhossein-zamanzade',
                'github' => 'https://github.com/Amir-dev00',
                'telegram' => 'https://t.me/Amir_dev00',
                'telegram_handle' => '@Amir_dev00',
            ],
        ]);

        SiteSetting::query()->updateOrCreate(['key' => 'profile'], [
            'value' => [
                'name' => 'Amirhossein Zamanzade',
                'photo' => 'assets/img/amir/profile.webp',
                'logo' => 'assets/img/amir/az-logo.webp',
            ],
        ]);

        SiteSetting::query()->updateOrCreate(['key' => 'featured_slugs'], [
            'value' => array_slice(array_column($projects, 'slug'), 0, 4),
        ]);

        // Never overwrite an owner's booking link on re-seed.
        SiteSetting::query()->firstOrCreate(['key' => 'booking'], [
            'value' => ['enabled' => true, 'url' => null, 'provider' => 'auto', 'duration' => 30],
        ]);

        $pages = [
            ['en', 'page', 'home', $stringsEn['title.base'] ?? null, $stringsEn['meta.description'] ?? null],
            ['fa', 'page', 'home', $stringsFa['title.base'] ?? null, $stringsFa['meta.description'] ?? null],
            ['en', 'page', 'work', $stringsEn['title.work'] ?? null, $stringsEn['work.lead'] ?? null],
            ['fa', 'page', 'work', $stringsFa['title.work'] ?? null, $stringsFa['work.lead'] ?? null],
            ['en', 'page', 'about', $stringsEn['title.about'] ?? null, $stringsEn['about.p1'] ?? null],
            ['fa', 'page', 'about', $stringsFa['title.about'] ?? null, $stringsFa['about.p1'] ?? null],
            ['en', 'page', 'contact', $stringsEn['title.contact'] ?? null, $stringsEn['contact.intro'] ?? null],
            ['fa', 'page', 'contact', $stringsFa['title.contact'] ?? null, $stringsFa['contact.intro'] ?? null],
        ];

        foreach ($pages as [$locale, $scope, $key, $title, $description]) {
            SeoEntry::query()->updateOrCreate(
                ['locale' => $locale, 'scope' => $scope, 'scope_key' => $key],
                [
                    'title' => $title,
                    'description' => $description,
                    'og_image' => 'assets/img/amir/az-logo.webp',
                    'robots' => 'index,follow',
                    'is_dirty' => false,
                ]
            );
        }

        $order = 0;
        foreach ($projects as $item) {
            $imageRel = $item['image'] ?? null;
            $mediaId = null;
            if ($imageRel) {
                $mediaId = $this->importAsset($root, $imageRel, 'project-cover:'.$item['slug']);
            }

            $project = Project::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'category' => $item['category'] ?? null,
                    'year' => $item['date'] ?? null,
                    'live_url' => $item['liveUrl'] ?? null,
                    'github_url' => $item['githubUrl'] ?? null,
                    'sort_order' => $order++,
                    'is_featured' => $order <= 4,
                    'is_dirty' => false,
                    'cover_media_id' => $mediaId,
                ]
            );

            ProjectTranslation::query()->updateOrCreate(
                ['project_id' => $project->id, 'locale' => 'en'],
                [
                    'short_description' => $item['shortDescription'] ?? null,
                    'full_description' => $item['fullDescription'] ?? null,
                    'role' => $item['role'] ?? null,
                    'category_label' => $item['category'] ?? null,
                ]
            );

            $fa = $projectCopyFa[$item['slug']] ?? [];
            ProjectTranslation::query()->updateOrCreate(
                ['project_id' => $project->id, 'locale' => 'fa'],
                [
                    'short_description' => $fa['shortDescription'] ?? null,
                    'full_description' => $fa['fullDescription'] ?? null,
                    'role' => $fa['role'] ?? null,
                    'category_label' => isset($fa['category'])
                        ? ($stringsFa['category.'.$fa['category']] ?? $fa['category'])
                        : null,
                ]
            );

            $project->technologies()->delete();
            foreach (($item['technologies'] ?? []) as $i => $tech) {
                ProjectTechnology::query()->create([
                    'project_id' => $project->id,
                    'name' => $tech,
                    'sort_order' => $i,
                ]);
            }

            SeoEntry::query()->updateOrCreate(
                ['locale' => 'en', 'scope' => 'project', 'scope_key' => $item['slug']],
                [
                    'title' => str_replace('{name}', $item['title'], $stringsEn['title.project'] ?? $item['title']),
                    'description' => $item['shortDescription'] ?? null,
                    'og_image' => $imageRel,
                    'robots' => 'index,follow',
                ]
            );
            SeoEntry::query()->updateOrCreate(
                ['locale' => 'fa', 'scope' => 'project', 'scope_key' => $item['slug']],
                [
                    'title' => str_replace('{name}', $item['title'], $stringsFa['title.project'] ?? $item['title']),
                    'description' => $fa['shortDescription'] ?? $item['shortDescription'] ?? null,
                    'og_image' => $imageRel,
                    'robots' => 'index,follow',
                ]
            );
        }

        // Import profile photo into media library for admin reuse.
        $this->importAsset($root, 'assets/img/amir/profile.webp', 'profile');
        $this->importAsset($root, 'assets/img/amir/az-logo.webp', 'logo');

        SiteState::current();

        /** @var PublicationService $publisher */
        $publisher = app(PublicationService::class);
        $publisher->publish(null, 'Initial seed publish');
    }

    protected function importAsset(string $root, string $relative, string $usage): ?int
    {
        $src = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_file($src)) {
            return null;
        }

        $basename = basename($src);
        $destRel = 'media/library/'.$basename;
        Storage::disk('local')->put($destRel, File::get($src));

        $asset = MediaAsset::query()->updateOrCreate(
            ['path' => $destRel],
            [
                'disk' => 'local',
                'public_path' => $relative,
                'original_name' => $basename,
                'mime' => mime_content_type($src) ?: null,
                'size' => filesize($src) ?: 0,
                'alt_en' => pathinfo($basename, PATHINFO_FILENAME),
                'alt_fa' => pathinfo($basename, PATHINFO_FILENAME),
                'is_published' => true,
                'used_by' => [$usage],
            ]
        );

        // Keep a copy accessible from the Laravel public dir during local/dev.
        $publicDest = public_path($relative);
        File::ensureDirectoryExists(dirname($publicDest));
        if (! is_file($publicDest)) {
            File::copy($src, $publicDest);
        }

        return $asset->id;
    }

    protected function parseI18n(string $path): array
    {
        $js = File::get($path);
        // STRINGS block comes first — take the first en:/fa: object literals.
        $en = $this->extractObject($js, 'en:', false);
        $fa = $this->extractObject($js, 'fa:', false);
        $projectFa = [];
        if (preg_match('/PROJECT_COPY\s*=\s*\{/', $js, $m, PREG_OFFSET_CAPTURE)) {
            $from = $m[0][1];
            $faPos = strpos($js, 'fa:', $from);
            if ($faPos !== false) {
                $projectFa = $this->extractObject(substr($js, $faPos - 1), 'fa:', false);
            }
        }

        return [$en, $fa, $projectFa];
    }

    protected function parseProjects(string $path): array
    {
        $js = File::get($path);
        if (! preg_match('/window\.AZ_PROJECTS\s*=\s*(\[[\s\S]*?\]);/', $js, $m)) {
            return [];
        }

        return $this->jsonFromJsObject($m[1]);
    }

    protected function extractObject(string $js, string $marker, bool $second = false): array
    {
        $offset = 0;
        $pos = strpos($js, $marker, $offset);
        if ($second) {
            $pos = strpos($js, $marker, $pos + 1);
        }
        if ($pos === false) {
            return [];
        }
        $start = strpos($js, '{', $pos);
        $depth = 0;
        $end = $start;
        $len = strlen($js);
        for ($i = $start; $i < $len; $i++) {
            $ch = $js[$i];
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        return $this->jsonFromJsObject(substr($js, $start, $end - $start + 1));
    }

    protected function jsonFromJsObject(string $raw): array
    {
        $json = $raw;
        $json = preg_replace('/^\s*\/\/.*$/m', '', $json);
        $json = preg_replace('/,\s*([\]}])/m', '$1', $json);
        $json = preg_replace('/(\s|\{|,)([A-Za-z_][A-Za-z0-9_]*)\s*:/', '$1"$2":', $json);
        $json = str_replace(["'", "\r"], ['"', ''], $json);
        // Convert JS multi-line concatenation? none expected.
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            // Fallback: evaluate via node if available
            $tmp = storage_path('app/tmp-parse.json');
            File::put($tmp.'.js', 'process.stdout.write(JSON.stringify('.$raw.'))');
            $out = shell_exec('node '.escapeshellarg($tmp.'.js').' 2>nul');
            $decoded = json_decode((string) $out, true) ?: [];
            @unlink($tmp.'.js');
        }

        return is_array($decoded) ? $decoded : [];
    }
}
