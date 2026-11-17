<?php

namespace Tests\Feature;

use App\Models\Publication;
use App\Models\SiteSetting;
use App\Models\SiteState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_bilingual_experience_through_the_publication_workflow(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $data = [
            'company_en' => 'Example Studio',
            'company_fa' => 'استودیو نمونه',
            'role_en' => 'Software Engineer',
            'role_fa' => 'مهندس نرم‌افزار',
            'type' => 'full-time',
            'start_month' => '2024-03',
            'website' => 'https://example.com',
        ];

        $this->actingAs($owner)->post(route('admin.experience.store'), $data)
            ->assertRedirect(route('admin.experience.index'));
        $this->get('/work')->assertOk()->assertDontSee('Example Studio');
        $this->get('/work?preview=1')->assertOk()->assertSee('Example Studio')->assertSee('Present');
        $this->post(route('admin.publish'))->assertRedirect();

        $this->get('/work')->assertOk()->assertSee('id="work-projects"', false)
            ->assertSee('id="work-experience"', false)->assertSee('Example Studio');
        $this->getJson('/fa/work', ['X-AZ-Fragment' => '1'])->assertOk()
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'استودیو نمونه') && str_contains($html, 'تا امروز'));

        $entry = SiteSetting::query()->where('key', 'like', SiteSetting::EXPERIENCE_PREFIX.'%')->firstOrFail();
        $id = substr($entry->key, strlen(SiteSetting::EXPERIENCE_PREFIX));
        $edit = route('admin.experience.update', $id);
        $this->put($edit, $data + ['end_month' => '2024-02'])->assertSessionHasErrors('end_month');
        $this->put($edit, array_replace($data, ['role_en' => 'Lead Engineer']))->assertRedirect();
        $this->get('/work')->assertDontSee('Lead Engineer');
        $this->get('/work?preview=1')->assertSee('Lead Engineer');

        $this->delete(route('admin.experience.destroy', $id))->assertRedirect();
        $this->get('/work')->assertSee('Example Studio');
        $this->post(route('admin.publish'))->assertRedirect();
        $this->get('/work')->assertDontSee('Example Studio');
    }

    public function test_bundled_experience_shows_bilingual_cards_and_logos_before_projects(): void
    {
        $this->get('/work')->assertOk()
            ->assertSeeInOrder(['Work experience', 'experience/fitnet.webp', 'Fitnet', 'Aug–Sep 2026',
                'experience/novin-coders.webp', 'Novin Coders', 'May–Jun 2026', 'experience/amirkabir.webp',
                'Teaching Assistant', 'id="work-projects"'], false)
            ->assertSee('class="az-experience__list"', false)
            ->assertSee('<time datetime="2025-12">Dec 2025</time>', false);

        $this->getJson('/fa/work', ['X-AZ-Fragment' => '1'])->assertOk()
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'شهریور ۱۴۰۵')
                && str_contains($html, 'نوین کدرز')
                && str_contains($html, 'دستیار آموزشی'));
    }

    public function test_work_fragments_include_the_mobile_project_carousel_in_both_languages(): void
    {
        foreach (['en' => '/work', 'fa' => '/fa/work'] as $locale => $route) {
            $data = $this->getJson($route, ['X-AZ-Fragment' => '1'])->assertOk()->json();
            $html = $data['html'];
            $this->assertStringContainsString('class="az-work-mobile" lang="'.$locale.'"', $html);
            $this->assertSame(count($data['projects']), substr_count($html, 'class="az-work-mobile__card"'));
            $this->assertStringContainsString('class="az-work-mobile__track"', $html);
            $this->assertStringContainsString('data-mobile-step="-1"', $html);
            $this->assertStringContainsString('data-mobile-step="1"', $html);
            $this->assertStringContainsString($locale === 'fa' ? 'aria-label="پروژه بعدی"' : 'aria-label="Next project"', $html);
            foreach ($data['projects'] as $project) {
                if ($project['image']) {
                    $this->assertStringContainsString('src="'.e($project['image']).'" alt="" width="960" height="540" loading="lazy"', $html);
                }
            }
        }
    }

    public function test_removed_company_stays_off_the_site_with_legacy_draft_and_published_content(): void
    {
        config(['work-experience' => []]);

        $oldEntry = [
            'company_en' => 'Pol Ideal Pars (MedPIP)',
            'company_fa' => 'پل ایده‌آل پارس',
            'role_en' => 'Software Engineer',
            'role_fa' => 'مهندس نرم‌افزار',
            'start_month' => '2026-11',
            'website' => 'https://medpip.com/',
        ];
        SiteSetting::query()->create(['key' => SiteSetting::EXPERIENCE_PREFIX.'medpip', 'value' => $oldEntry]);
        $snapshot = Publication::query()->create([
            'label' => 'Legacy experience',
            'payload' => ['experience' => [$oldEntry]],
            'published_at' => now(),
        ]);
        $state = SiteState::current();
        $state->current_publication_id = $snapshot->id;
        $state->save();

        $this->get('/work')->assertOk()->assertSee('id="work-projects"', false)
            ->assertDontSee('id="work-experience"', false)->assertDontSee('Pol Ideal Pars');
        $this->getJson('/fa/work', ['X-AZ-Fragment' => '1'])->assertOk()
            ->assertJsonPath('html', fn (string $html) => ! str_contains($html, 'پل ایده‌آل پارس')
                && ! str_contains($html, 'id="work-experience"'));

        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get('/work?preview=1')->assertOk()->assertDontSee('Pol Ideal Pars');
        $this->get(route('admin.experience.index'))->assertOk()->assertDontSee('Pol Ideal Pars');
        $this->post(route('admin.publish'))->assertRedirect();
        $this->assertSame([], Publication::query()->latest('id')->firstOrFail()->payload['experience']);
    }

    public function test_non_owner_cannot_write_experience(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => false]))
            ->post(route('admin.experience.store'))
            ->assertForbidden();
    }
}
