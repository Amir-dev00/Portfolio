<?php

namespace App\Services\Assistant;

/**
 * Zero-token answers for simple commands and verified biographical facts.
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

        if ($this->isGreeting($message)) {
            return $this->decisions->answer(
                $locale === 'fa'
                    ? 'سلام! دوست داری دربارهٔ امیرحسین چی بدونی؟'
                    : 'Hey! What would you like to know about Amirhossein?'
            );
        }

        $topic = $this->biographyTopic($normalized);
        if ($topic !== null) {
            $reply = $topic === 'entrance_exam_rank'
                ? config('assistant.entrance_exam_rank.'.$locale)
                : config('assistant-biography.relationship.'.$locale);
            if (is_string($reply) && $reply !== '') {
                return $this->decisions->answer($reply);
            }
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
        $message = str_replace(['ي', 'ك', '‌'], ['ی', 'ک', ' '], $message);
        $message = preg_replace('/[^\p{L}\p{N}\s+]/u', ' ', $message) ?? $message;
        $message = preg_replace('/\s+/u', ' ', $message) ?? $message;

        return trim($message);
    }

    protected function biographyTopic(string $question): ?string
    {
        $question = ' '.$question.' ';
        $rank = preg_match('/(?:رتبه|رنک)(?:ش|اش)?|\b(?:rank|ranking|placement)\b/u', $question);
        $exam = preg_match('/کنکور|\bkonkur\b|\b(?:university |college )?entrance exam\b/u', $question);

        if ($rank && $exam && ! preg_match('/کوئرا|\bquera\b/u', $question)) {
            return 'entrance_exam_rank';
        }

        if (preg_match('/(?:^| )(?:دوس(?:ت)? *دختر(?:ش|اش)?|پارتنر(?:ش|اش)?|مجرده|مجرد است|تو رابطه|در رابطه|رابطه عاطفی)(?= |$)|\b(?:girlfriend|romantic partner|in a relationship|dating anyone|is he single)\b/u', $question)) {
            if (preg_match('/(?:بیشتر|جزئیات|آدرس|شماره|قد|سن|تولد|کجا|کی آشنا|از کی|چند وقته|چند وقت)|\b(?:more|details|age|how old|where|when|since|how long|phone|address|birthday|birthdate|height|contact|instagram)\b/u', $question)) {
                return null;
            }

            return 'relationship';
        }

        return null;
    }

    public function isGreeting(string $message): bool
    {
        return (bool) preg_match(
            '/^(hi|hello|hey|yo|hi there|hello there|hey there|good morning|good afternoon|good evening|greetings|salam|salaam|سلام|درود|سلام علیکم)$/u',
            $this->normalize($message)
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
