<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
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

    public function test_bundled_employment_is_live_and_edits_keep_the_logo_and_persian_month(): void
    {
        $this->get('/work')->assertOk()->assertSee('Pol Ideal Pars')->assertSee('Nov 2026')
            ->assertSee('Present')->assertSee('https://medpip.com/')->assertSee('assets/img/experience/medpip.webp');
        $this->get('/fa/work')->assertOk()->assertSee('آبان ۱۴۰۵')->assertSee('تا امروز');
        $this->assertFileExists(public_path('assets/img/experience/medpip.webp'));

        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get(route('admin.experience.edit', 'medpip'))->assertOk()->assertSee('medpip.webp');
        $this->post(route('admin.publish'))->assertRedirect();
        $this->put(route('admin.experience.update', 'medpip'), [
            'company_en' => 'Pol Ideal Pars (MedPIP)',
            'company_fa' => 'پل ایده‌آل پارس',
            'role_en' => 'Software Engineer',
            'role_fa' => 'مهندس نرم‌افزار',
            'start_month' => '2026-11',
            'website' => 'https://medpip.com/',
            'description_en' => 'Updated work description',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get('/work')->assertDontSee('Updated work description');
        $this->get('/work?preview=1')->assertSee('Updated work description')->assertSee('medpip.webp');
        $this->post(route('admin.publish'))->assertRedirect();
        $this->get('/fa/work')->assertSee('آبان ۱۴۰۵')->assertSee('medpip.webp');
        $this->delete(route('admin.experience.destroy', 'medpip'))->assertRedirect();
        $this->get('/work')->assertSee('Pol Ideal Pars');
        $this->post(route('admin.publish'))->assertRedirect();
        $this->get('/work')->assertDontSee('Pol Ideal Pars');
    }

    public function test_non_owner_cannot_write_experience(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => false]))
            ->post(route('admin.experience.store'))
            ->assertForbidden();
    }
}
