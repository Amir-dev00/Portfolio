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
        $this->get('/admin/projects')->assertOk()->assertSee('Import 10');
        $this->post('/admin/projects/import')->assertRedirect();
        $this->post('/admin/projects/import')->assertRedirect();
        $this->assertDatabaseCount('projects', 10);
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
        $this->getJson('/admin/export.json')->assertOk()->assertJsonCount(10, 'projects')
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

    public function test_non_owners_cannot_manage_content(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->post('/admin/projects/import')->assertForbidden();
        $this->post('/admin/content')->assertForbidden();
        $this->get('/admin/export.json')->assertForbidden();
    }
}
