<?php

namespace App\Services\Assistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiResponsesClient
{
    /**
     * @param  list<array{role:string,content:string}>  $messages
     */
    public function create(array $messages, string $instructions): string
    {
        $apiKey = (string) config('assistant.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('missing_config');
        }

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
