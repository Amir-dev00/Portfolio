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
        $this->get('/')->assertOk();
        $this->get('/fa')->assertOk();
    }

    #[DataProvider('greetings')]
    public function test_simple_greeting_is_answered_without_a_provider_request(string $message, string $locale, string $reply): void
    {
        Http::fake();

        $this->postJson('/api/assistant/chat', ['message' => $message, 'locale' => $locale])
            ->assertOk()
            ->assertJsonPath('reply', $reply)
            ->assertJsonPath('action', 'answer')
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

    public function test_exact_cv_shortcut_navigates_with_zero_provider_calls(): void
    {
        Http::fake();

        $this->postJson('/api/assistant/chat', ['message' => 'CV', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'cv')
            ->assertJsonPath('mode', 'cv')
            ->assertJsonPath('url', '/assets/cv/Amirhossein-Zamanzade-CV.pdf');

        Http::assertNothingSent();

        $this->postJson('/api/assistant/chat', ['message' => 'رزومه', 'locale' => 'fa'])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'cv');

        Http::assertNothingSent();
    }

    public function test_natural_language_navigation_uses_one_provider_call(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'navigate',
                    'reply' => 'Opening Work.',
                    'destination' => 'work',
                ]),
            ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Where can I see his projects?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'work')
            ->assertJsonPath('url', '/work')
            ->assertJsonPath('label', 'Work');

        Http::assertSentCount(1);
    }

    public function test_persian_contact_and_cv_natural_requests_navigate(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'output_text' => json_encode([
                        'action' => 'navigate',
                        'reply' => 'دارم رزومه را باز می‌کنم.',
                        'destination' => 'cv',
                    ]),
                ], 200)
                ->push([
                    'output_text' => json_encode([
                        'action' => 'navigate',
                        'reply' => 'دارم صفحه تماس را باز می‌کنم.',
                        'destination' => 'contact',
                    ]),
                ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'می‌خوام رزومه‌ش رو ببینم',
            'locale' => 'fa',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'cv')
            ->assertJsonPath('url', '/assets/cv/Amirhossein-Zamanzade-CV.pdf');

        RateLimiter::clear('assistant.req.'.sha1('127.0.0.1|'.$this->app['session']->getId()));

        $this->postJson('/api/assistant/chat', [
            'message' => 'چطور می‌تونم باهاش در ارتباط باشم؟',
            'locale' => 'fa',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'contact')
            ->assertJsonPath('url', '/fa/contact');

        Http::assertSentCount(2);
    }

    public function test_questions_that_only_mention_a_destination_still_answer(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'output_text' => json_encode([
                        'action' => 'answer',
                        'reply' => 'He built a Clinic Booking Portal with Laravel and MySQL.',
                        'destination' => '',
                    ]),
                ], 200)
                ->push([
                    'output_text' => json_encode([
                        'action' => 'answer',
                        'reply' => 'His CV lists Computer Science study and freelance availability.',
                        'destination' => '',
                    ]),
                ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Which Laravel projects has he built?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'answer')
            ->assertJsonPath('destination', null);

        RateLimiter::clear('assistant.req.'.sha1('127.0.0.1|'.$this->app['session']->getId()));

        $this->postJson('/api/assistant/chat', [
            'message' => 'What experience is listed on his CV?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'answer');

        Http::assertSentCount(2);
    }

    public function test_ambiguous_follow_up_asks_for_clarification(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'answer',
                    'reply' => 'Which page should I open — Work, About, Contact, or CV?',
                    'destination' => '',
                ]),
            ], 200),
        ]);

        session([AssistantService::SESSION_KEY => [
            ['role' => 'user', 'content' => 'Tell me about his skills and contact options'],
            ['role' => 'assistant', 'content' => 'He works with Laravel and is reachable via Contact.'],
        ]]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'اون رو باز کن',
            'locale' => 'fa',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'answer')
            ->assertJsonPath('destination', null);

        Http::assertSentCount(1);
    }

    public function test_clear_follow_up_can_navigate_from_prior_exchange(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'navigate',
                    'reply' => 'Opening Work.',
                    'destination' => 'work',
                ]),
            ], 200),
        ]);

        session([AssistantService::SESSION_KEY => [
            ['role' => 'user', 'content' => 'Where can I see his projects?'],
            ['role' => 'assistant', 'content' => 'Opening Work. [destination:work|Work]'],
        ]]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Open it',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'work');

        Http::assertSent(function ($request) {
            $input = $request->data()['input'];
            $this->assertCount(3, $input);
            $this->assertStringContainsString('destination:work', $input[1]['content']);

            return true;
        });
    }

    public function test_invalid_destination_falls_back_to_localized_answer(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'navigate',
                    'reply' => 'Opening admin',
                    'destination' => 'admin',
                ]),
            ], 200),
        ]);

        $en = $this->postJson('/api/assistant/chat', [
            'message' => 'Open the admin panel',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'answer')
            ->assertJsonPath('destination', null)
            ->assertJsonPath('url', null)
            ->json('reply');

        $this->assertStringContainsString('couldn’t open', mb_strtolower((string) $en));

        RateLimiter::clear('assistant.req.'.sha1('127.0.0.1|'.$this->app['session']->getId()));
        Cache::flush();
        $this->bindPortfolioPayload($this->samplePayload());

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'navigate',
                    'reply' => 'Opening secrets',
                    'destination' => 'secrets',
                ]),
            ], 200),
        ]);

        $fa = $this->postJson('/api/assistant/chat', [
            'message' => 'صفحه مخفی را باز کن',
            'locale' => 'fa',
        ])->assertOk()->json('reply');
        $this->assertStringContainsString('نتونستم', (string) $fa);
    }

    public function test_project_question_sends_one_compact_provider_call(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'answer',
                    'reply' => 'او یک پورتال نوبت‌دهی کلینیک با Laravel ساخته است.',
                    'destination' => '',
                ]),
            ], 200),
        ]);

        $question = 'با Laravel چه پروژه‌ای ساخته؟';

        $this->postJson('/api/assistant/chat', [
            'message' => $question,
            'locale' => 'fa',
        ])
            ->assertOk()
            ->assertJsonPath('action', 'answer');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($question) {
            $data = $request->data();
            $this->assertSame('gpt-4.1-mini', $data['model']);
            $this->assertSame(200, $data['max_output_tokens']);
            $this->assertCount(1, $data['input']);
            $this->assertSame($question, $data['input'][0]['content']);
            $this->assertArrayHasKey('text', $data);
            $this->assertStringContainsString('Clinic Booking Portal', (string) $data['instructions']);

            return true;
        });
    }

    public function test_provider_outage_returns_localized_error_and_counts_toward_caps(): void
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

        Http::assertSentCount(1);
        $this->assertSame(1, (int) Cache::get('assistant.ip.'.hash('sha256', '127.0.0.1'), 0));
        $this->assertSame(1, (int) Cache::get('assistant.global.'.now()->format('Y-m-d'), 0));
    }

    public function test_json_schema_rejection_retries_once_then_caches_unsupported(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'unsupported text.format']], 400)
                ->push([
                    'output_text' => json_encode([
                        'action' => 'answer',
                        'reply' => 'He uses Laravel and React.',
                        'destination' => '',
                    ]),
                ], 200)
                ->push([
                    'output_text' => json_encode([
                        'action' => 'answer',
                        'reply' => 'He also works with Node.js.',
                        'destination' => '',
                    ]),
                ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What is his stack?',
            'locale' => 'en',
        ])->assertOk()->assertJsonPath('action', 'answer');

        // First ask: schema attempt + plain retry.
        Http::assertSentCount(2);
        $this->assertTrue((bool) Cache::get(\App\Services\Assistant\OpenAiResponsesClient::SCHEMA_UNSUPPORTED_CACHE_KEY));

        RateLimiter::clear('assistant.req.'.sha1('127.0.0.1|'.$this->app['session']->getId()));

        $this->postJson('/api/assistant/chat', [
            'message' => 'What else does he use?',
            'locale' => 'en',
        ])->assertOk();

        // Second ask: cached unsupported → single plain call (3 total recorded).
        Http::assertSentCount(3);
        $third = Http::recorded()[2][0]->data();
        $this->assertArrayNotHasKey('text', $third);
    }

    public function test_server_error_does_not_blindly_retry_schema_request(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'down']], 500),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What projects use Laravel?',
            'locale' => 'en',
        ])->assertStatus(502);

        Http::assertSentCount(1);
    }

    public function test_chat_rejects_empty_message(): void
    {
        $this->postJson('/api/assistant/chat', [
            'message' => '   ',
            'locale' => 'en',
        ])
            ->assertStatus(422)
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

    public function test_local_greeting_and_cv_work_without_api_key(): void
    {
        config(['assistant.api_key' => '']);
        Http::fake();

        $this->postJson('/api/assistant/chat', ['message' => 'Hello', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('action', 'answer');

        $this->postJson('/api/assistant/chat', ['message' => 'CV', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('action', 'navigate')
            ->assertJsonPath('destination', 'cv');

        Http::assertNothingSent();
    }

    public function test_reset_clears_session_history(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'action' => 'answer',
                    'reply' => 'Noted.',
                    'destination' => '',
                ]),
            ], 200),
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
        $full = $context->forLocale('fa');
        $compact = $context->forQuestion('fa', 'با Laravel چه پروژه‌ای ساخته؟');

        $this->assertLessThan(mb_strlen($full), mb_strlen($compact));
        $this->assertStringContainsString('Clinic Booking Portal', $compact);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function bindPortfolioPayload(array $payload): void
    {
        $this->app->instance(PortfolioContent::class, new class($payload) extends PortfolioContent
        {
            /** @param  array<string, mixed>  $fixed */
            public function __construct(private array $fixed) {}

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
                    'skill.laravel' => 'Laravel',
                ],
                'fa' => [
                    'home.profile.name' => 'امیرحسین زمان‌زاده',
                    'home.profile.role' => 'مهندس نرم‌افزار',
                    'about.p1' => 'توسعه‌دهنده فول‌استک.',
                    'about.location' => 'تهران، ایران',
                    'about.education' => 'کارشناسی علوم کامپیوتر',
                    'about.status' => 'آزاد برای همکاری',
                    'skill.laravel' => 'Laravel',
                ],
            ],
            'profile' => ['name' => 'Amirhossein Zamanzade'],
            'social' => [
                'email' => 'itsamirzamani@gmail.com',
                'linkedin' => 'https://linkedin.com/in/amirhossein-zamanzade',
                'github' => 'https://github.com/Amir-dev00',
                'telegram' => 'https://t.me/Amir_dev00',
            ],
            'resume' => ['url' => '/assets/cv/Amirhossein-Zamanzade-CV.pdf'],
            'booking' => ['enabled' => true, 'duration' => 30],
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
