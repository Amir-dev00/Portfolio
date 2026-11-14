<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundled_project_import_edit_and_archive_follow_publication(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $slug = 'novin-inova';
        $this->get('/admin/projects')->assertOk()->assertSee('Import 14');
        $this->post('/admin/projects/import')->assertRedirect();
        $this->post('/admin/projects/import')->assertRedirect();
        $this->assertDatabaseCount('projects', 14);
        $project = Project::query()->where('slug', $slug)->firstOrFail();
        $this->get(route('admin.projects.edit', $project))->assertOk()->assertSee('English case study');
        $this->put(route('admin.projects.update', $project), [
            'title' => 'Inova updated',
            'slug' => $slug,
            'details' => [
                'en' => ['overview' => 'Custom portfolio overview', 'features' => "First\nSecond", 'approach' => 'Implementation'],
                'fa' => ['title' => 'اینوا به‌روز', 'overview' => 'نمای تازه'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $headers = ['X-AZ-Fragment' => '1'];
        $this->getJson('/work/'.$slug.'?preview=1', $headers)->assertOk()
            ->assertJsonPath('projects.0.caseStudy.features', ['First', 'Second'])
            ->assertJsonCount(5, 'projects.0.gallery');
        $this->post('/admin/publish')->assertRedirect();
        $this->getJson('/fa/work/'.$slug, $headers)->assertOk()->assertJsonPath('projects.0.title', 'اینوا به‌روز');

        $this->post(route('admin.projects.archive', $project))->assertRedirect();
        $this->get('/work/'.$slug)->assertOk();
        $this->get('/work/'.$slug.'?preview=1')->assertNotFound();
        $this->post('/admin/publish')->assertRedirect();
        $this->get('/work/'.$slug)->assertNotFound();
    }

    public function test_project_order_is_saved_as_a_draft_and_published_in_both_languages(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post(route('admin.projects.import'))->assertRedirect();
        $this->get(route('admin.projects.index'))->assertOk()->assertSee('Save project order');
        $projects = Project::query()->orderBy('sort_order')->orderBy('id')->get();
        $ids = $projects->modelKeys();
        $slugs = $projects->pluck('slug')->all();
        $headers = ['X-AZ-Fragment' => '1'];

        $this->post(route('admin.projects.reorder'), ['order' => [end($ids)]])->assertSessionHasErrors('order');
        $this->post(route('admin.projects.reorder'), ['order' => array_reverse($ids)])->assertRedirect()->assertSessionHasNoErrors();
        $this->getJson('/work', $headers)->assertJsonPath('projects.0.slug', $slugs[0]);
        $this->getJson('/work?preview=1', $headers)->assertJsonPath('projects.0.slug', end($slugs));

        $this->post(route('admin.publish'))->assertRedirect();
        foreach (['/work', '/fa/work'] as $path) {
            $actual = $this->getJson($path, $headers)->assertOk()->json('projects');
            $this->assertSame(array_reverse($slugs), array_column($actual, 'slug'));
        }
    }

    public function test_imported_project_with_no_live_link_uses_verified_fallback(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post(route('admin.projects.import'))->assertRedirect();
        $project = Project::query()->where('slug', 'novin-coursescan')->firstOrFail();
        $project->update(['live_url' => null]);
        $this->post(route('admin.publish'))->assertRedirect();

        $headers = ['X-AZ-Fragment' => '1'];
        $this->getJson('/work/novin-coursescan', $headers)->assertOk()
            ->assertJsonPath('projects.0.liveUrl', 'https://coursescan.ir/');
        $this->getJson('/fa/work/novin-fitnet', $headers)->assertOk()
            ->assertJsonPath('projects.0.websiteUrl', 'https://fitnetsport.ir/')
            ->assertJsonPath('projects.0.liveUrl', 'https://demo.fitnetsport.ir/');
    }

    public function test_new_showcases_render_in_both_languages_and_keep_live_links_after_import(): void
    {
        $projects = [
            'sarva-dental-clinic' => ['https://sarvadentalclinic.ir/', ['PHP', 'Blade', 'HTML', 'CSS', 'JavaScript'], 3],
            'alwin-windows' => ['https://alwinco.ir/', ['PHP', 'Blade', 'HTML', 'CSS', 'JavaScript'], 4],
            'rb-cafe' => ['https://rbcaferestaurant.ir/', ['Next.js', 'PHP', 'Laravel'], 4],
            'fitnet' => ['https://demo.fitnetsport.ir/', ['PHP', 'Laravel', 'Next.js'], 4],
        ];
        $headers = ['X-AZ-Fragment' => '1'];

        foreach ($projects as $slug => [$liveUrl, $technologies, $count]) {
            $path = '/work/novin-'.$slug;
            $response = $this->getJson($path, $headers)->assertOk()
                ->assertJsonPath('projects.0.liveUrl', $liveUrl)
                ->assertJsonPath('projects.0.technologies', $technologies)
                ->assertJsonCount($count, 'projects.0.gallery');
            $project = $response->json('projects.0');
            $this->assertNotEmpty($project['caseStudy']['features']);
            foreach ([$project['image'], ...$project['gallery']] as $imageUrl) {
                $this->assertFileExists(public_path(ltrim(parse_url($imageUrl, PHP_URL_PATH), '/')));
            }
        }

        $this->getJson('/fa/work/novin-fitnet', $headers)->assertOk()
            ->assertJsonPath('projects.0.title', 'فیت‌نت | کشف و رزرو باشگاه')
            ->assertJsonPath('projects.0.category', 'وب‌اپ پیش‌رونده');

        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/projects/import')->assertRedirect();
        $this->post('/admin/publish')->assertRedirect();
        $this->getJson('/work/novin-fitnet', $headers)->assertOk()
            ->assertJsonPath('projects.0.liveUrl', 'https://demo.fitnetsport.ir/')
            ->assertJsonCount(4, 'projects.0.gallery');
    }

    public function test_owner_can_publish_new_text_and_settings_and_export_bundled_projects(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get('/admin/content')->assertOk()->assertSee('Miro’s knowledge');
        $this->get('/admin/content?group=Miro')->assertOk()->assertSee('assistant.suggestions.education.question');
        $this->post('/admin/content', [
            'texts' => [['key' => 'about.team.name', 'en' => 'Studio Example', 'fa' => 'استودیو نمونه']],
            'about_skills' => ['backend' => 'Laravel, PostgreSQL'],
            'assistant_knowledge' => ['en' => 'Public portfolio fact'],
            'team' => ['url' => 'https://example.com/team'],
            'home_backgrounds' => [
                1 => ['desktop' => '/assets/img/first.webp', 'mobile' => '/assets/img/phone.webp'],
                2 => ['desktop' => null], 3 => ['desktop' => null], 4 => ['desktop' => null],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->getJson('/admin/export.json')->assertOk()->assertJsonCount(14, 'projects')
            ->assertJsonPath('settings.assistant_knowledge.en', 'Public portfolio fact');
        $this->post('/admin/publish')->assertRedirect();
        $this->get('/about')->assertOk()->assertSee('Studio Example')->assertSee('https://example.com/team');
        $this->get('/')->assertOk()->assertSee('/assets/img/first.webp')->assertSee('/assets/img/phone.webp');
        $this->get('/admin/media')->assertOk()->assertSee('Search by filename');
        $this->get('/admin/seo?target=project:novin-inova')->assertOk()->assertSee('novin-inova');
        $this->post('/admin/seo', [
            'target' => 'project:novin-inova',
            'seo' => ['fa' => ['title' => 'عنوان جست‌وجوی اینوا', 'robots' => 'index,follow']],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/admin/publish')->assertRedirect();
        $this->get('/fa/work/novin-inova')->assertOk()->assertSee('عنوان جست‌وجوی اینوا');
    }

    public function test_owner_can_delete_a_project_without_reimporting_it(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/projects/import')->assertRedirect();
        $project = Project::query()->where('slug', 'novin-inova')->firstOrFail();
        $this->get(route('admin.projects.edit', $project))->assertOk()->assertSee('Delete project');
        $this->delete(route('admin.projects.destroy', $project))->assertSessionHasErrors('confirm_delete');
        $this->assertModelExists($project);

        $this->delete(route('admin.projects.destroy', $project), ['confirm_delete' => 1])
            ->assertRedirect(route('admin.projects.index'));
        $this->assertModelMissing($project);
        $this->assertDatabaseMissing('project_translations', ['project_id' => $project->id]);
        $this->get('/work/novin-inova')->assertOk();
        $this->get('/work/novin-inova?preview=1')->assertNotFound();
        $this->post('/admin/projects/import')->assertRedirect();
        $this->assertDatabaseCount('projects', 13);
        $this->post('/admin/publish')->assertRedirect();
        $this->get('/work/novin-inova')->assertNotFound();
        $this->get('/fa/work/novin-inova')->assertNotFound();
    }

    public function test_persian_admin_language_is_saved_and_does_not_change_public_pages(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Owner login');
        $this->from('/admin/login')->post('/admin/language', ['locale' => 'fa'])->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('lang="fa" dir="rtl"', false)->assertSee('ورود مدیر');

        $this->actingAs(User::factory()->create(['is_owner' => true]));
        foreach ([
            '/admin' => 'داشبورد',
            '/admin/projects/create' => 'پروژه جدید',
            '/admin/projects' => 'افزودن پروژه',
            '/admin/content?group=Miro' => 'جست‌وجو بر اساس کلید یا متن فعلی',
            '/admin/bookings/settings' => 'تنظیمات جلسه',
            '/admin/bookings' => 'خروجی CSV',
            '/admin/experience/create' => 'نام شرکت',
            '/admin/resume' => 'رزومه فعلی',
            '/admin/seo' => 'جست‌وجو و اشتراک‌گذاری',
            '/admin/media' => 'کتابخانه رسانه',
        ] as $url => $label) {
            $this->get($url)->assertOk()->assertSee('lang="fa" dir="rtl"', false)->assertSee($label);
        }
        $this->get('/about')->assertOk()->assertSee('lang="en" dir="ltr"', false);
        $this->from('/admin/projects')->post('/admin/language', ['locale' => 'de'])->assertSessionHasErrors('locale');
        $this->from('/admin/projects')->post('/admin/language', ['locale' => 'en'])->assertRedirect('/admin/projects');
        $this->get('/admin/projects')->assertOk()->assertSee('lang="en" dir="ltr"', false);
        $this->from('/admin/projects')->post('/admin/language', ['locale' => 'fa'])->assertRedirect('/admin/projects');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('ورود مدیر');
    }

    public function test_non_owners_cannot_manage_content(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->post('/admin/projects/import')->assertForbidden();
        $project = Project::query()->create(['title' => 'Protected', 'slug' => 'protected']);
        $this->delete(route('admin.projects.destroy', $project), ['confirm_delete' => 1])->assertForbidden();
        $this->assertModelExists($project);
        $this->post('/admin/content')->assertForbidden();
        $this->get('/admin/export.json')->assertForbidden();
    }
}
