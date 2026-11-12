<?php

namespace App\Services\Assistant;

/**
 * Zero-token shortcuts for exact commands. Natural-language intents go to the model.
 */
class AssistantLocalIntent
{
    public function __construct(private AssistantDecision $decisions) {}

    /**
     * @return array{
     *   action:string,
     *   reply:string,
     *   destination:?string,
     *   url:?string,
     *   label:?string,
     *   mode:?string
     * }|null
     */
    public function resolve(string $message, string $locale, bool $preview = false): ?array
    {
        $normalized = $this->normalize($message);
        if ($normalized === '') {
            return null;
        }

        if ($this->isGreeting($normalized)) {
            return $this->decisions->answer(
                $locale === 'fa'
                    ? 'سلام! دوست داری دربارهٔ امیرحسین چی بدونی؟'
                    : 'Hey! What would you like to know about Amirhossein?'
            );
        }

        // Exact CV / résumé commands only — natural phrasing uses the model.
        if ($this->isExactCv($normalized)) {
            $decision = $this->decisions->navigate(
                AssistantDestinations::CV,
                $locale,
                $locale === 'fa' ? 'دارم رزومه را باز می‌کنم.' : 'Opening his CV.',
                $preview
            );

            if ($decision !== null) {
                return $decision;
            }

            return $this->decisions->answer(
                $locale === 'fa'
                    ? 'رزومه الان در دسترس نیست. از صفحه تماس استفاده کنید یا بعداً دوباره تلاش کنید.'
                    : 'The CV isn’t available right now. Use Contact, or try again later.'
            );
        }

        return null;
    }

    protected function normalize(string $message): string
    {
        $message = mb_strtolower(trim($message));
        $message = str_replace(['ي', 'ك', '‌'], ['ی', 'ک', ''], $message);
        $message = preg_replace('/[^\p{L}\p{N}\s+]/u', ' ', $message) ?? $message;
        $message = preg_replace('/\s+/u', ' ', $message) ?? $message;

        return trim($message);
    }

    protected function isGreeting(string $normalized): bool
    {
        return (bool) preg_match(
            '/^(hi|hello|hey|yo|hi there|hello there|hey there|good morning|good afternoon|good evening|greetings|salam|salaam|سلام|درود|سلام علیکم)$/u',
            $normalized
        );
    }

    protected function isExactCv(string $normalized): bool
    {
        return (bool) preg_match(
            '/^(cv|resume|résumé|رزومه|سی وی|سیوی)$/u',
            $normalized
        );
    }
}
