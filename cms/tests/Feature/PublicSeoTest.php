<?php

namespace Tests\Feature;

use App\Models\SeoEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_html_and_sitemap_expose_localized_content_and_images(): void
    {
        $portrait = asset('assets/img/amir/profile.webp');

        $this->get('/')->assertOk()->assertSee('<title>Amirhossein Zamanzade | Software Engineer</title>', false);
        $this->get('/fa/work')->assertOk()
            ->assertSee('href="'.url('/fa/work/novin-inova').'"', false);

        $about = $this->get('/fa/about')->assertOk()
            ->assertSee('src="'.$portrait.'"', false)
            ->assertSee('hreflang="en" href="'.url('/about').'"', false);
        preg_match('/id="az-profile-schema">([^<]+)<\/script>/', $about->getContent(), $schema);
        $profile = json_decode($schema[1] ?? '', true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('ProfilePage', $profile['@type']);
        $this->assertSame('Amirhossein Zamanzade', $profile['mainEntity']['name']);
        $this->assertSame($portrait, $profile['mainEntity']['image']);
        $this->assertSame('fa', $profile['inLanguage']);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'));
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $xml = simplexml_load_string($sitemap->getContent());
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('i', 'http://www.google.com/schemas/sitemap-image/1.1');
        $aboutImages = $xml->xpath('//s:url[s:loc="'.url('/fa/about').'"]/i:image/i:loc');
        $projectImages = $xml->xpath('//s:url[s:loc="'.url('/work/novin-inova').'"]/i:image/i:loc');
        $this->assertSame($portrait, (string) $aboutImages[0]);
        $this->assertNotEmpty($projectImages);
    }

    public function test_noindex_is_respected_for_a_localized_page_and_internal_responses(): void
    {
        SeoEntry::query()->create([
            'scope' => 'page', 'scope_key' => 'about', 'locale' => 'fa', 'robots' => 'noindex,follow',
        ]);

        $this->get('/fa/about')->assertOk()->assertSee('content="noindex,follow"', false)
            ->assertDontSee('id="az-profile-schema"', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>'.url('/fa/about').'</loc>', false)
            ->assertSee('<loc>'.url('/about').'</loc>', false);
        $this->get('/about?preview=1')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/about', ['X-AZ-Fragment' => '1'])->assertOk()->assertHeader('X-Robots-Tag', 'noindex')
            ->assertJsonPath('profileSchema.@type', 'ProfilePage');
    }
}
