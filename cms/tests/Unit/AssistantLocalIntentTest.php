<?php

namespace Tests\Unit;

use App\Services\Assistant\AssistantLocalIntent;
use App\Services\Assistant\PortfolioAssistantContext;
use App\Services\PortfolioContent;
use Mockery;
use Tests\TestCase;

class AssistantLocalIntentTest extends TestCase
{
    public function test_detects_standalone_intents_and_ignores_mixed_questions(): void
    {
        $context = Mockery::mock(PortfolioAssistantContext::class);
        $intent = new AssistantLocalIntent($context);

        $this->assertSame(AssistantLocalIntent::GREETING, $intent->detect('Hello!'));
        $this->assertSame(AssistantLocalIntent::GREETING, $intent->detect('سلام'));
        $this->assertSame(AssistantLocalIntent::RESUME, $intent->detect('Download CV'));
        $this->assertSame(AssistantLocalIntent::CONTACT, $intent->detect('contact info'));
        $this->assertSame(AssistantLocalIntent::BOOKING, $intent->detect('book a call'));

        $this->assertNull($intent->detect('Hello, what projects has he built?'));
        $this->assertNull($intent->detect('Has he worked on booking systems?'));
        $this->assertNull($intent->detect('با Laravel چه پروژه‌ای ساخته؟'));
    }

    public function test_selects_laravel_project_for_persian_query(): void
    {
        $payload = [
            'built_at' => 't',
            'strings' => ['en' => [], 'fa' => []],
            'profile' => ['name' => 'Amirhossein Zamanzade'],
            'social' => [],
            'resume' => ['url' => '/assets/cv/Amirhossein-Zamanzade-CV.pdf'],
            'featured_slugs' => ['shopping-mall-desktop'],
            'projects' => [
                [
                    'slug' => 'shopping-mall-desktop',
                    'title' => 'Shopping Mall Desktop',
                    'is_featured' => true,
                    'technologies' => ['Java'],
                    'en' => ['shortDescription' => 'Desktop app'],
                    'fa' => ['shortDescription' => 'اپ دسکتاپ'],
                ],
                [
                    'slug' => 'clinic-booking-portal',
                    'title' => 'Clinic Booking Portal',
                    'is_featured' => false,
                    'technologies' => ['Laravel', 'PHP'],
                    'en' => ['shortDescription' => 'Booking portal'],
                    'fa' => ['shortDescription' => 'پورتال نوبت‌دهی'],
                ],
            ],
        ];

        $content = Mockery::mock(PortfolioContent::class);
        $content->shouldReceive('payload')->andReturn($payload);
        $context = new PortfolioAssistantContext($content);

        $selected = $context->selectProjects(
            $payload['projects'],
            'fa',
            'با Laravel چه پروژه‌ای ساخته؟'
        );

        $this->assertSame('relevant', $selected['mode']);
        $this->assertSame('clinic-booking-portal', $selected['projects'][0]['slug']);
    }
}
