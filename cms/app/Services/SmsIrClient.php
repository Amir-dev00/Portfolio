<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin SMS.ir REST client (bulk send, one recipient).
 * Official send API: POST /v1/send/bulk — see SmsPanelV2.Laravel Send.md.
 */
final class SmsIrClient
{
    /**
     * @return array{outcome:string,status:?int,message:?string,message_id:?string,cost:?float,http_status:?int}
     */
    public function sendBulk(string $message, string $mobile, int|string|null $lineNumber = null): array
    {
        $apiKey = (string) config('smsir.api_key');
        $line = $lineNumber ?? config('smsir.line_number');
        $timeout = max(1, (int) config('smsir.timeout', 5));
        $base = (string) config('smsir.base_url', 'https://api.sms.ir');

        if ($apiKey === '' || $line === null || $line === '' || trim($mobile) === '') {
            return $this->result('rejected', null, 'SMS.ir is not fully configured.', null, null, null);
        }

        $payload = [
            'lineNumber' => is_numeric($line) ? (int) $line : $line,
            'messageText' => $message,
            'mobiles' => [$this->normalizeMobile($mobile)],
        ];

        try {
            $response = Http::baseUrl($base)
                ->timeout($timeout)
                ->connectTimeout(min(3, $timeout))
                ->acceptJson()
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('/v1/send/bulk', $payload);

            $json = $response->json();
            if (! is_array($json)) {
                $json = [];
            }

            $providerStatus = isset($json['status']) ? (int) $json['status'] : null;
            $providerMessage = isset($json['message']) ? (string) $json['message'] : null;
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            $messageIds = $data['messageIds'] ?? $data['MessageIds'] ?? null;
            $messageId = null;
            if (is_array($messageIds) && $messageIds !== []) {
                $messageId = (string) $messageIds[0];
            } elseif (isset($data['messageId'])) {
                $messageId = (string) $data['messageId'];
            }
            $cost = isset($data['cost']) ? (float) $data['cost'] : (isset($data['Cost']) ? (float) $data['Cost'] : null);

            if ($response->successful() && $providerStatus === 1) {
                return $this->result('accepted', $providerStatus, $providerMessage, $messageId, $cost, $response->status());
            }

            if ($response->successful() || $response->clientError() || $response->serverError()) {
                return $this->result(
                    'rejected',
                    $providerStatus,
                    $providerMessage ?: ('HTTP '.$response->status()),
                    $messageId,
                    $cost,
                    $response->status()
                );
            }

            return $this->result('uncertain', $providerStatus, $providerMessage ?: 'Unexpected HTTP response', $messageId, $cost, $response->status());
        } catch (ConnectionException $e) {
            Log::warning('smsir.connection', ['error' => $e->getMessage()]);

            return $this->result('uncertain', null, 'Connection timeout or network error', null, null, null);
        } catch (RequestException $e) {
            Log::warning('smsir.request', ['error' => $e->getMessage()]);

            return $this->result('rejected', null, $e->getMessage(), null, null, $e->response?->status());
        } catch (Throwable $e) {
            Log::warning('smsir.unexpected', ['error' => $e->getMessage()]);

            return $this->result('uncertain', null, $e->getMessage(), null, null, null);
        }
    }

    public function isConfigured(): bool
    {
        return (bool) config('smsir.enabled')
            && filled(config('smsir.api_key'))
            && filled(config('smsir.line_number'))
            && filled(config('smsir.owner_mobile'));
    }

    protected function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?: '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits !== '' ? $digits : trim($mobile);
    }

    /**
     * @return array{outcome:string,status:?int,message:?string,message_id:?string,cost:?float,http_status:?int}
     */
    protected function result(
        string $outcome,
        ?int $status,
        ?string $message,
        ?string $messageId,
        ?float $cost,
        ?int $httpStatus,
    ): array {
        return [
            'outcome' => $outcome,
            'status' => $status,
            'message' => $message,
            'message_id' => $messageId,
            'cost' => $cost,
            'http_status' => $httpStatus,
        ];
    }
}
