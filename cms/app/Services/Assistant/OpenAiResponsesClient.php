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
        $text = $this->create($messages, $instructions, true);
        $decoded = $this->decodeJsonObject($text);
        if ($decoded === null) {
            throw new RuntimeException('provider_empty');
        }

        return $decoded;
    }

    /**
     * @param  list<array{role:string,content:string}>  $messages
     */
    public function create(array $messages, string $instructions, bool $jsonObject = false): string
    {
        $apiKey = (string) config('assistant.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('missing_config');
        }

        // After a gateway rejects json_schema, stay on prompt-only JSON for a day (one call per ask).
        $useSchema = $jsonObject && ! Cache::get(self::SCHEMA_UNSUPPORTED_CACHE_KEY);

        $payload = [
            'model' => (string) config('assistant.model', 'gpt-4.1-mini'),
            'instructions' => $instructions,
            'input' => array_map(static function (array $m) {
                return [
                    'role' => $m['role'],
                    'content' => $m['content'],
                ];
            }, $messages),
            'store' => (bool) config('assistant.store', false),
            'max_output_tokens' => (int) config('assistant.max_output_tokens', 200),
        ];

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

            return $this->create($messages, $instructions, false);
        }

        if ($response->failed()) {
            throw new RuntimeException('provider_error');
        }

        $data = $response->json();
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
     * @return array<string, mixed>|null
     */
    protected function decodeJsonObject(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $match)) {
            $decoded = json_decode($match[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Schema-less fallback: treat a non-empty prose reply as an answer.
        return [
            'action' => 'answer',
            'reply' => $text,
            'destination' => '',
        ];
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
