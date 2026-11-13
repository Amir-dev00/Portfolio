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

    public function test_non_owner_cannot_write_experience(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => false]))
            ->post(route('admin.experience.store'))
            ->assertForbidden();
    }
}
