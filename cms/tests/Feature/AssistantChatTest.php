<?php

namespace Tests\Feature;

use App\Services\Assistant\AssistantService;
use App\Services\Assistant\PortfolioAssistantContext;
use App\Services\PortfolioContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear('assistant.req.'.sha1('127.0.0.1|'));
        config([
            'assistant.enabled' => true,
            'assistant.api_key' => 'test-key',
            'assistant.model' => 'gpt-4.1-mini',
            'assistant.endpoint' => 'https://api.openai.com/v1/responses',
            'assistant.rate_limit' => 20,
            'assistant.daily_global_cap' => 400,
            'assistant.max_history_turns' => 1,
            'assistant.max_output_tokens' => 200,
            'assistant.timeout' => 5,
        ]);

        $this->bindPortfolioPayload($this->samplePayload());
    }

    public function test_status_reports_configuration(): void
    {
        $this->getJson('/api/assistant/status')
            ->assertOk()
            ->assertJson(['ok' => true, 'configured' => true]);
    }

    public function test_ask_page_renders_for_home_ask_flow(): void
    {
        $this->get('/ask')->assertOk();
        $this->get('/fa/ask')->assertOk();
    }

    #[DataProvider('greetings')]
    public function test_simple_greeting_is_answered_without_a_provider_request(string $message, string $locale, string $reply): void
    {
        Http::fake();

        $this->postJson('/api/assistant/chat', ['message' => $message, 'locale' => $locale])
            ->assertOk()
            ->assertJsonPath('reply', $reply)
            ->assertJsonPath('turns', 0);

        Http::assertNothingSent();
    }

    public static function greetings(): array
    {
        return [
            'English' => ['Hello!', 'en', 'Hey! What would you like to know about Amirhossein?'],
            'Persian' => ['سلام!', 'fa', 'سلام! دوست داری دربارهٔ امیرحسین چی بدونی؟'],
        ];
    }

    public function test_greeting_resume_contact_and_booking_make_zero_provider_calls(): void
    {
        foreach ([
            ['download cv', 'en', 'résumé'],
            ['رزومه', 'fa', 'رزومه'],
            ['contact', 'en', '/contact'],
            ['اطلاعات تماس', 'fa', '/fa/contact'],
            ['book a call', 'en', '/contact'],
            ['رزرو تماس', 'fa', '/fa/contact'],
        ] as [$message, $locale, $needle]) {
            Http::fake();
            $response = $this->postJson('/api/assistant/chat', [
                'message' => $message,
                'locale' => $locale,
            ])->assertOk()->assertJsonPath('ok', true);

            $this->assertStringContainsString($needle, (string) $response->json('reply'));
            Http::assertNothingSent();
            $this->assertSame(0, (int) Cache::get('assistant.ip.'.hash('sha256', '127.0.0.1'), 0));
            $this->assertSame([], (array) session(AssistantService::SESSION_KEY, []));
        }
    }

    public function test_mixed_greeting_question_still_reaches_the_model(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => 'He has shipped web, desktop, and API projects.',
            ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Hello, what projects has he built?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        Http::assertSentCount(1);
    }

    public function test_booking_systems_question_is_not_local_booking_intent(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => 'Published facts do not list a booking-system product.',
            ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Has he worked on booking systems?',
            'locale' => 'en',
        ])->assertOk();

        Http::assertSentCount(1);
    }

    public function test_project_question_sends_one_compact_provider_call(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => 'He built a Laravel booking portal with MySQL.',
                'usage' => ['input_tokens' => 412, 'output_tokens' => 28],
            ], 200),
        ]);

        $question = 'با Laravel چه پروژه‌ای ساخته؟';

        $this->postJson('/api/assistant/chat', [
            'message' => $question,
            'locale' => 'fa',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($question) {
            $data = $request->data();
            $this->assertSame('gpt-4.1-mini', $data['model']);
            $this->assertSame(200, $data['max_output_tokens']);
            $this->assertCount(1, $data['input']);
            $this->assertSame('user', $data['input'][0]['role']);
            $this->assertSame($question, $data['input'][0]['content']);

            $instructions = (string) $data['instructions'];
            $this->assertStringContainsString('Relevant projects:', $instructions);
            $this->assertStringContainsString('Clinic Booking Portal', $instructions);
            $this->assertStringNotContainsString('Shopping Mall Desktop', $instructions);
            $this->assertLessThan(3500, mb_strlen($instructions));

            return true;
        });
    }

    public function test_greeting_then_real_question_only_calls_provider_once(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => 'He works with Laravel, React, and Node.js.',
            ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Hi',
            'locale' => 'en',
        ])->assertOk();
        Http::assertNothingSent();

        // Endpoint throttles duplicate submits per session within 2s.
        RateLimiter::clear('assistant.req.'.sha1('127.0.0.1|'.$this->app['session']->getId()));

        $this->postJson('/api/assistant/chat', [
            'message' => 'What is his stack?',
            'locale' => 'en',
        ])->assertOk();

        Http::assertSentCount(1);
    }

    public function test_follow_up_includes_at_most_one_prior_exchange_and_named_project(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push(['output_text' => 'The Telegram Automation Bot handles scheduled tasks.'], 200)
                ->push(['output_text' => 'That bot uses Python and SQLite.'], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Tell me about the Telegram Automation Bot',
            'locale' => 'en',
        ])->assertOk();

        $this->postJson('/api/assistant/chat', [
            'message' => 'What stack did he use for that?',
            'locale' => 'en',
        ])->assertOk();

        Http::assertSentCount(2);

        $second = Http::recorded()[1][0];
        $data = $second->data();
        $this->assertCount(3, $data['input']);
        $this->assertSame('user', $data['input'][0]['role']);
        $this->assertSame('assistant', $data['input'][1]['role']);
        $this->assertSame('user', $data['input'][2]['role']);
        $this->assertStringContainsString('Telegram Automation Bot', (string) $data['instructions']);
        $this->assertSame(200, $data['max_output_tokens']);
    }

    public function test_history_trim_keeps_only_one_turn_for_provider(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['output_text' => 'Noted.'], 200),
        ]);

        session([AssistantService::SESSION_KEY => [
            ['role' => 'user', 'content' => 'Older question about Java'],
            ['role' => 'assistant', 'content' => 'Older answer about Java'],
            ['role' => 'user', 'content' => 'Tell me about the Telegram Automation Bot'],
            ['role' => 'assistant', 'content' => 'It automates Telegram workflows.'],
        ]]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What stack did he use for that?',
            'locale' => 'en',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $input = $request->data()['input'];
            $this->assertCount(3, $input);
            $this->assertStringContainsString('Telegram Automation Bot', $input[0]['content']);
            $this->assertStringNotContainsString('Older question', json_encode($input));

            return true;
        });
    }

    public function test_provider_outage_returns_localized_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'down']], 500),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What projects use Laravel?',
            'locale' => 'en',
        ])
            ->assertStatus(502)
            ->assertJsonPath('code', 'provider_error');
    }

    public function test_chat_rejects_empty_message(): void
    {
        $this->postJson('/api/assistant/chat', [
            'message' => '   ',
            'locale' => 'en',
        ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'empty');
    }

    public function test_chat_handles_missing_config_for_model_questions(): void
    {
        config(['assistant.api_key' => '']);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What is his stack?',
            'locale' => 'fa',
        ])
            ->assertStatus(503)
            ->assertJsonPath('code', 'missing_config');
    }

    public function test_local_greeting_works_without_api_key(): void
    {
        config(['assistant.api_key' => '']);
        Http::fake();

        $this->postJson('/api/assistant/chat', [
            'message' => 'Hello',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reply', 'Hey! What would you like to know about Amirhossein?');

        Http::assertNothingSent();
    }

    public function test_chat_ip_rate_limit_applies_to_provider_path(): void
    {
        config(['assistant.rate_limit' => 1]);
        Cache::put('assistant.ip.'.hash('sha256', '127.0.0.1'), 1, now()->addHour());

        $this->postJson('/api/assistant/chat', [
            'message' => 'What is his stack?',
            'locale' => 'en',
        ])
            ->assertStatus(429)
            ->assertJsonPath('code', 'rate_limit');
    }

    public function test_reset_clears_session_history(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['output_text' => 'Noted.'], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What is his stack?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('turns', 2);

        $this->postJson('/api/assistant/chat', ['reset' => true, 'locale' => 'en'])
            ->assertOk()
            ->assertJson(['ok' => true, 'reset' => true, 'history' => []]);
    }

    public function test_compact_context_is_smaller_than_full_dump_for_sample_question(): void
    {
        /** @var PortfolioAssistantContext $context */
        $context = app(PortfolioAssistantContext::class);
        $question = 'با Laravel چه پروژه‌ای ساخته؟';

        $full = $context->forLocale('fa');
        $compact = $context->forQuestion('fa', $question);

        $this->assertLessThan(mb_strlen($full), mb_strlen($compact));
        $this->assertStringContainsString('Clinic Booking Portal', $compact);
        $this->assertStringNotContainsString('Shopping Mall Desktop', $compact);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function bindPortfolioPayload(array $payload): void
    {
        $this->app->instance(PortfolioContent::class, new class($payload) extends PortfolioContent
        {
            /** @param  array<string, mixed>  $fixed */
            public function __construct(private array $fixed)
            {
                // Skip PublicationService wiring in tests.
            }

            public function payload(?bool $preview = false): array
            {
                return $this->fixed;
            }

            public function strings(string $locale, ?bool $preview = false): array
            {
                return $this->fixed['strings'][$locale]
                    ?? $this->fixed['strings']['en']
                    ?? [];
            }

            public function projectsFor(string $locale, ?bool $preview = false): array
            {
                return array_map(function (array $project) use ($locale) {
                    $copy = $project[$locale] ?? $project['en'] ?? [];

                    return [
                        'slug' => $project['slug'],
                        'title' => $project['title'],
                        'shortDescription' => $copy['shortDescription'] ?? '',
                        'fullDescription' => $copy['fullDescription'] ?? '',
                        'category' => $copy['category'] ?? $project['category'] ?? '',
                        'technologies' => $project['technologies'] ?? [],
                        'image' => null,
                        'liveUrl' => $project['liveUrl'] ?? null,
                        'githubUrl' => $project['githubUrl'] ?? null,
                        'date' => $project['date'] ?? null,
                        'role' => $copy['role'] ?? '',
                    ];
                }, $this->fixed['projects'] ?? []);
            }

            public function seo(string $locale, string $scope, ?string $scopeKey = null, ?bool $preview = false): array
            {
                return [
                    'title' => 'Ask',
                    'description' => null,
                    'og_image' => null,
                    'robots' => 'index,follow',
                ];
            }

            public function currentPublicationId(): ?int
            {
                return null;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function samplePayload(): array
    {
        return [
            'built_at' => '2026-11-12T00:00:00+00:00',
            'strings' => [
                'en' => [
                    'home.profile.name' => 'Amirhossein Zamanzade',
                    'home.profile.role' => 'Software Engineer',
                    'about.p1' => 'Full-stack developer focused on fast, scalable products.',
                    'about.location' => 'Tehran, Iran',
                    'about.education' => 'B.Sc. Computer Science',
                    'about.status' => 'Available for freelance',
                    'skill.php' => 'PHP',
                    'skill.laravel' => 'Laravel',
                ],
                'fa' => [
                    'home.profile.name' => 'امیرحسین زمان‌زاده',
                    'home.profile.role' => 'مهندس نرم‌افزار',
                    'about.p1' => 'توسعه‌دهنده فول‌استک با تمرکز روی محصول‌های سریع و مقیاس‌پذیر.',
                    'about.location' => 'تهران، ایران',
                    'about.education' => 'کارشناسی علوم کامپیوتر',
                    'about.status' => 'آزاد برای همکاری',
                    'skill.php' => 'PHP',
                    'skill.laravel' => 'Laravel',
                ],
            ],
            'profile' => [
                'name' => 'Amirhossein Zamanzade',
            ],
            'social' => [
                'email' => 'itsamirzamani@gmail.com',
                'linkedin' => 'https://linkedin.com/in/amirhossein-zamanzade',
                'github' => 'https://github.com/Amir-dev00',
                'telegram' => 'https://t.me/Amir_dev00',
            ],
            'resume' => [
                'url' => '/assets/cv/Amirhossein-Zamanzade-CV.pdf',
            ],
            'booking' => [
                'enabled' => true,
                'duration' => 30,
            ],
            'settings' => [],
            'seo' => [],
            'featured_slugs' => [
                'personal-portfolio-website',
                'shopping-mall-desktop',
                'telegram-automation-bot',
            ],
            'projects' => [
                [
                    'slug' => 'personal-portfolio-website',
                    'title' => 'Personal Portfolio Website',
                    'category' => 'Web',
                    'is_featured' => true,
                    'technologies' => ['HTML5', 'CSS3', 'JavaScript'],
                    'en' => ['shortDescription' => 'Personal marketing site.', 'role' => 'Developer', 'category' => 'Web'],
                    'fa' => ['shortDescription' => 'سایت شخصی.', 'role' => 'توسعه‌دهنده', 'category' => 'وب'],
                ],
                [
                    'slug' => 'shopping-mall-desktop',
                    'title' => 'Shopping Mall Desktop',
                    'category' => 'Desktop',
                    'is_featured' => true,
                    'technologies' => ['Java 21', 'JavaFX 21', 'PostgreSQL'],
                    'en' => ['shortDescription' => 'Desktop mall management.', 'role' => 'Developer', 'category' => 'Desktop'],
                    'fa' => ['shortDescription' => 'مدیریت مراکز خرید.', 'role' => 'توسعه‌دهنده', 'category' => 'دسکتاپ'],
                ],
                [
                    'slug' => 'telegram-automation-bot',
                    'title' => 'Telegram Automation Bot',
                    'category' => 'Automation',
                    'is_featured' => true,
                    'technologies' => ['Python', 'SQLite', 'APScheduler'],
                    'en' => ['shortDescription' => 'Scheduled Telegram workflows.', 'role' => 'Developer', 'category' => 'Automation'],
                    'fa' => ['shortDescription' => 'اتوماسیون تلگرام.', 'role' => 'توسعه‌دهنده', 'category' => 'اتوماسیون'],
                ],
                [
                    'slug' => 'clinic-booking-portal',
                    'title' => 'Clinic Booking Portal',
                    'category' => 'Web',
                    'is_featured' => false,
                    'technologies' => ['Laravel', 'PHP', 'MySQL', 'Blade'],
                    'liveUrl' => 'https://example.com/clinic',
                    'en' => ['shortDescription' => 'Clinic appointment booking portal.', 'role' => 'Full-stack', 'category' => 'Web'],
                    'fa' => ['shortDescription' => 'پورتال نوبت‌دهی کلینیک با Laravel.', 'role' => 'فول‌استک', 'category' => 'وب'],
                ],
            ],
        ];
    }
}
