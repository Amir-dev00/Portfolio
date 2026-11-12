<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class AssistantController extends Controller
{
    public function __construct(private AssistantService $assistant) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'configured' => $this->assistant->configured(),
            'locale' => $request->query('locale') === 'fa' ? 'fa' : 'en',
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $locale = $request->input('locale') === 'fa' ? 'fa' : 'en';
        $message = trim(strip_tags((string) $request->input('message', '')));
        $reset = $request->boolean('reset');
        $clientKey = 'assistant.req.'.sha1($request->ip().'|'.$request->session()->getId());

        if ($reset) {
            $request->session()->forget(AssistantService::SESSION_KEY);

            return response()->json([
                'ok' => true,
                'reset' => true,
                'reply' => null,
                'history' => [],
            ]);
        }

        if ($message === '') {
            return $this->error('empty', $locale, 422);
        }

        // Prevent duplicate rapid submits from the same session.
        if (! RateLimiter::attempt($clientKey, 1, fn () => true, 2)) {
            return $this->error('duplicate', $locale, 429);
        }

        $history = (array) $request->session()->get(AssistantService::SESSION_KEY, []);

        try {
            $result = $this->assistant->ask(
                $message,
                $locale,
                (string) $request->ip(),
                $history,
                $request->boolean('preview') && $request->user()?->isOwner(),
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), $locale, $this->statusFor($e->getMessage()));
        }

        $request->session()->put(AssistantService::SESSION_KEY, $result['history']);

        return response()->json([
            'ok' => true,
            'reply' => $result['reply'],
            'action' => $result['action'] ?? 'answer',
            'destination' => $result['destination'] ?? null,
            'url' => $result['url'] ?? null,
            'label' => $result['label'] ?? null,
            'mode' => $result['mode'] ?? null,
            'locale' => $locale,
            // Client keeps a mirrored transcript; server session is the source of truth for continuity.
            'turns' => count($result['history']),
        ]);
    }

    protected function statusFor(string $code): int
    {
        return match ($code) {
            'empty' => 422,
            'missing_config' => 503,
            'rate_limit', 'global_cap', 'provider_rate', 'duplicate' => 429,
            'timeout' => 504,
            default => 502,
        };
    }

    protected function error(string $code, string $locale, int $status): JsonResponse
    {
        $messages = [
            'en' => [
                'empty' => 'Type a short question first.',
                'missing_config' => 'The assistant is not configured on this server yet. Use Contact or Book a call instead.',
                'rate_limit' => 'Too many questions for now. Please wait a bit, then try again.',
                'global_cap' => 'The assistant is at capacity today. Please email or book a call instead.',
                'duplicate' => 'That question is already sending — wait a moment.',
                'timeout' => 'The assistant took too long. Please retry.',
                'provider_rate' => 'The assistant is busy. Please retry in a minute.',
                'provider_error' => 'The assistant is temporarily unavailable. Please retry or use Contact.',
                'provider_empty' => 'No answer came back. Please retry.',
            ],
            'fa' => [
                'empty' => 'اول یک پرسش کوتاه بنویسید.',
                'missing_config' => 'دستیار هنوز روی این سرور پیکربندی نشده است. از تماس یا رزرو تماس استفاده کنید.',
                'rate_limit' => 'فعلاً تعداد پرسش‌ها زیاد شده است. کمی صبر کنید و دوباره تلاش کنید.',
                'global_cap' => 'ظرفیت امروز دستیار پر شده است. لطفاً ایمیل بزنید یا تماس رزرو کنید.',
                'duplicate' => 'همین پرسش در حال ارسال است — کمی صبر کنید.',
                'timeout' => 'پاسخ طول کشید. لطفاً دوباره تلاش کنید.',
                'provider_rate' => 'دستیار الان شلوغ است. یک دقیقه دیگر دوباره تلاش کنید.',
                'provider_error' => 'دستیار موقتاً در دسترس نیست. دوباره تلاش کنید یا از صفحه تماس استفاده کنید.',
                'provider_empty' => 'پاسخی برنگشت. لطفاً دوباره تلاش کنید.',
            ],
        ];

        $bag = $messages[$locale] ?? $messages['en'];

        return response()->json([
            'ok' => false,
            'code' => $code,
            'message' => $bag[$code] ?? $bag['provider_error'],
        ], $status);
    }
}
