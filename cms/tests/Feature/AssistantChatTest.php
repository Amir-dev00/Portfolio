<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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
            'assistant.max_history_turns' => 8,
            'assistant.timeout' => 5,
        ]);
    }

    public function test_status_reports_configuration(): void
    {
        $this->getJson('/api/assistant/status')
            ->assertOk()
            ->assertJson(['ok' => true, 'configured' => true]);
    }

    public function test_chat_returns_reply_from_responses_api(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => 'He builds web apps and APIs with Laravel and React.',
            ], 200),
        ]);

        $this->postJson('/api/assistant/chat', [
            'message' => 'What is his stack?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reply', 'He builds web apps and APIs with Laravel and React.');
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

    public function test_chat_handles_missing_config(): void
    {
        config(['assistant.api_key' => '']);

        $this->postJson('/api/assistant/chat', [
            'message' => 'Hello',
            'locale' => 'fa',
        ])
            ->assertStatus(503)
            ->assertJsonPath('code', 'missing_config');
    }

    public function test_chat_ip_rate_limit(): void
    {
        config(['assistant.rate_limit' => 1]);
        Cache::put('assistant.ip.'.hash('sha256', '127.0.0.1'), 1, now()->addHour());

        $this->postJson('/api/assistant/chat', ['message' => 'Blocked', 'locale' => 'en'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'rate_limit');
    }

    public function test_reset_clears_session_history(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['output_text' => 'Noted.'], 200),
        ]);

        $this->postJson('/api/assistant/chat', ['message' => 'Hi', 'locale' => 'en'])->assertOk();

        $this->postJson('/api/assistant/chat', ['reset' => true, 'locale' => 'en'])
            ->assertOk()
            ->assertJson(['ok' => true, 'reset' => true, 'history' => []]);
    }

    public function test_provider_outage_returns_localized_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'down']], 500),
        ]);

        $this->postJson('/api/assistant/chat', ['message' => 'Hi', 'locale' => 'en'])
            ->assertStatus(502)
            ->assertJsonPath('code', 'provider_error');
    }
}
