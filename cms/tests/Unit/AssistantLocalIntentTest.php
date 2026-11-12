<?php

namespace Tests\Unit;

use App\Services\Assistant\AssistantDecision;
use App\Services\Assistant\AssistantDestinations;
use App\Services\Assistant\AssistantLocalIntent;
use App\Services\Assistant\PortfolioAssistantContext;
use App\Services\PortfolioContent;
use Mockery;
use Tests\TestCase;

class AssistantLocalIntentTest extends TestCase
{
    public function test_exact_cv_and_greeting_are_local_while_natural_phrases_are_not(): void
    {
        $payload = [
            'built_at' => 't',
            'strings' => ['en' => [], 'fa' => []],
            'profile' => [],
            'social' => [],
            'resume' => ['url' => '/assets/cv/Amirhossein-Zamanzade-CV.pdf'],
            'featured_slugs' => [],
            'projects' => [],
        ];
        $content = Mockery::mock(PortfolioContent::class);
        $content->shouldReceive('payload')->andReturn($payload);
        $context = new PortfolioAssistantContext($content);
        $intent = new AssistantLocalIntent(new AssistantDecision(new AssistantDestinations($context)));

        $greeting = $intent->resolve('Hello!', 'en');
        $this->assertSame('answer', $greeting['action']);

        $cv = $intent->resolve('CV', 'en');
        $this->assertSame('navigate', $cv['action']);
        $this->assertSame('cv', $cv['destination']);
        $this->assertSame('/assets/cv/Amirhossein-Zamanzade-CV.pdf', $cv['url']);

        $resumeFa = $intent->resolve('رزومه', 'fa');
        $this->assertSame('navigate', $resumeFa['action']);
        $this->assertSame('cv', $resumeFa['destination']);

        $this->assertNull($intent->resolve('می‌خوام رزومه‌ش رو ببینم', 'fa'));
        $this->assertNull($intent->resolve('Where can I see his projects?', 'en'));
        $this->assertNull($intent->resolve('چطور می‌تونم باهاش در ارتباط باشم؟', 'fa'));
        $this->assertNull($intent->resolve('Which Laravel projects has he built?', 'en'));
    }

    public function test_exact_cv_falls_back_locally_when_navigate_cannot_resolve(): void
    {
        $decisions = Mockery::mock(AssistantDecision::class);
        $decisions->shouldReceive('navigate')
            ->once()
            ->andReturn(null);
        $decisions->shouldReceive('answer')
            ->once()
            ->andReturn([
                'action' => 'answer',
                'reply' => 'The CV isn’t available right now. Use Contact, or try again later.',
                'destination' => null,
                'url' => null,
                'label' => null,
                'mode' => null,
            ]);

        $intent = new AssistantLocalIntent($decisions);
        $result = $intent->resolve('CV', 'en');

        $this->assertSame('answer', $result['action']);
        $this->assertStringContainsString('isn’t available', $result['reply']);
    }

    public function test_decision_rejects_invalid_destination(): void
    {
        $payload = [
            'built_at' => 't',
            'strings' => ['en' => [], 'fa' => []],
            'profile' => [],
            'social' => [],
            'resume' => ['url' => '/assets/cv/x.pdf'],
            'featured_slugs' => [],
            'projects' => [],
        ];
        $content = Mockery::mock(PortfolioContent::class);
        $content->shouldReceive('payload')->andReturn($payload);
        $context = new PortfolioAssistantContext($content);
        $decisions = new AssistantDecision(new AssistantDestinations($context));

        $invalid = $decisions->fromModelPayload([
            'action' => 'navigate',
            'reply' => 'Opening admin',
            'destination' => 'admin',
        ], 'en');

        $this->assertSame('answer', $invalid['action']);
        $this->assertNull($invalid['destination']);
        $this->assertStringContainsString('couldn’t open', mb_strtolower($invalid['reply']));
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
