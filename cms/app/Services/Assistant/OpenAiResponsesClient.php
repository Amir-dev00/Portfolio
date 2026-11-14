<?php

namespace App\Services\Assistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiResponsesClient
{
    public const SCHEMA_UNSUPPORTED_CACHE_KEY = 'assistant.responses_json_schema_unsupported';

    /**
     * @param  list<array{role:string,content:string}>  $messages
     * @return array<string, mixed>
     */
    public function createDecision(array $messages, string $instructions): array
    {
        $budget = max(400, (int) config('assistant.max_output_tokens', 400));

        try {
            return $this->requestDecision($messages, $instructions, $budget);
        } catch (RuntimeException $error) {
            if ($error->getMessage() !== 'provider_incomplete' || $budget >= 800) {
                throw $error;
            }

            return $this->requestDecision($messages, $instructions, 800);
        }
    }

    /**
     * @param  list<array{role:string,content:string}>  $messages
     * @return array<string, mixed>
     */
    private function requestDecision(array $messages, string $instructions, int $budget): array
    {
        $text = $this->create($messages, $instructions, ! Cache::get(self::SCHEMA_UNSUPPORTED_CACHE_KEY), $budget);

        return $this->decodeJsonObject($text);
    }

    /**
     * @param  list<array{role:string,content:string}>  $messages
     */
    private function create(array $messages, string $instructions, bool $useSchema, int $budget): string
    {
        $apiKey = (string) config('assistant.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('missing_config');
        }

        $payload = [
            'model' => (string) config('assistant.model'),
            'instructions' => $instructions,
            'input' => array_map(static function (array $m) {
                return [
                    'role' => $m['role'],
                    'content' => $m['content'],
                ];
            }, $messages),
            'store' => (bool) config('assistant.store', false),
            'max_output_tokens' => $budget,
        ];

        if ($payload['model'] === 'gpt-6-luna') {
            $payload['reasoning'] = ['effort' => 'none'];
        }

        if ($useSchema) {
            $payload['text'] = [
                'format' => $this->decisionSchema(),
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('assistant.timeout', 22))
                ->connectTimeout((int) config('assistant.connect_timeout', 5))
                ->post((string) config('assistant.endpoint'), $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('timeout', 0, $e);
        }

        if ($response->status() === 429) {
            throw new RuntimeException('provider_rate');
        }

        // Only retry without schema on client rejection of the format — never on 5xx outages.
        if ($useSchema && $response->status() === 400) {
            Cache::put(self::SCHEMA_UNSUPPORTED_CACHE_KEY, true, now()->addDay());

            return $this->create($messages, $instructions, false, $budget);
        }

        if ($response->failed()) {
            throw new RuntimeException('provider_error');
        }

        $data = $response->json();
        if (($data['incomplete_details']['reason'] ?? null) === 'max_output_tokens') {
            throw new RuntimeException('provider_incomplete');
        }
        if (in_array($data['status'] ?? null, ['incomplete', 'failed', 'cancelled', 'queued', 'in_progress'], true)) {
            throw new RuntimeException('provider_error');
        }

        $text = $this->extractText($data);
        if ($text === '') {
            throw new RuntimeException('provider_empty');
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decisionSchema(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'portfolio_assistant_decision',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['action', 'reply', 'destination'],
                'properties' => [
                    'action' => [
                        'type' => 'string',
                        'enum' => ['answer', 'navigate'],
                    ],
                    'reply' => [
                        'type' => 'string',
                    ],
                    'destination' => [
                        'type' => 'string',
                        'enum' => ['', 'work', 'about', 'contact', 'cv'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonObject(string $text): array
    {
        $candidate = preg_replace('/^\s*```(?:json)?\s*|\s*```\s*$/iu', '', $text) ?? $text;
        $candidate = ltrim($candidate);

        if (str_starts_with($candidate, '{') || str_starts_with($candidate, '[')) {
            $decoded = json_decode($candidate, true);
            if (! is_array($decoded)) {
                // Some gateways omit the incomplete status or HTML-encode JSON quotes.
                $decoded = json_decode(html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            }
            if (is_array($decoded) && is_string($decoded['action'] ?? null) && is_string($decoded['reply'] ?? null)) {
                return $decoded;
            }

            throw new RuntimeException('provider_incomplete');
        }

        // Some compatible gateways return prose. Reject text wrapping a JSON decision.
        if (preg_match('/\{\s*(?:"|&quot;|&#34;)(?:action|reply|destination)(?:"|&quot;|&#34;)\s*:/iu', $candidate)) {
            throw new RuntimeException('provider_incomplete');
        }

        return ['action' => 'answer', 'reply' => $candidate, 'destination' => ''];
    }

    protected function extractText(mixed $data): string
    {
        if (! is_array($data)) {
            return '';
        }

        if (is_string($data['output_text'] ?? null) && trim($data['output_text']) !== '') {
            return trim($data['output_text']);
        }

        $chunks = [];
        foreach ((array) ($data['output'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach ((array) ($item['content'] ?? []) as $part) {
                if (! is_array($part)) {
                    continue;
                }
                if (($part['type'] ?? '') === 'output_text' && is_string($part['text'] ?? null)) {
                    $chunks[] = $part['text'];
                } elseif (is_string($part['text'] ?? null)) {
                    $chunks[] = $part['text'];
                }
            }
        }

        return trim(implode("\n", array_filter($chunks)));
    }
}
