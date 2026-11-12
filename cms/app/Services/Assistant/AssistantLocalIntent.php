<?php

namespace App\Services\Assistant;

/**
 * Answers clear, standalone visitor intents without calling the model provider.
 */
class AssistantLocalIntent
{
    public const GREETING = 'greeting';

    public const RESUME = 'resume';

    public const CONTACT = 'contact';

    public const BOOKING = 'booking';

    public function __construct(private PortfolioAssistantContext $context) {}

    /**
     * @return self::GREETING|self::RESUME|self::CONTACT|self::BOOKING|null
     */
    public function detect(string $message): ?string
    {
        $normalized = $this->normalize($message);
        if ($normalized === '') {
            return null;
        }

        // Order matters: booking before contact (contact is broader).
        if ($this->isBooking($normalized)) {
            return self::BOOKING;
        }
        if ($this->isResume($normalized)) {
            return self::RESUME;
        }
        if ($this->isContact($normalized)) {
            return self::CONTACT;
        }
        if ($this->isGreeting($normalized)) {
            return self::GREETING;
        }

        return null;
    }

    public function reply(string $intent, string $locale, bool $preview = false): string
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $facts = $this->context->publicContactFacts($locale, $preview);

        return match ($intent) {
            self::GREETING => $locale === 'fa'
                ? 'سلام! دوست داری دربارهٔ امیرحسین چی بدونی؟'
                : 'Hey! What would you like to know about Amirhossein?',
            self::RESUME => $locale === 'fa'
                ? 'رزومهٔ او از این لینک قابل دریافت است: '.$facts['resume_url']
                : 'You can download his résumé here: '.$facts['resume_url'],
            self::CONTACT => $this->contactReply($locale, $facts),
            self::BOOKING => $locale === 'fa'
                ? 'برای رزرو تماس از صفحهٔ تماس استفاده کنید: '.$facts['contact_path']
                : 'You can book a call on the Contact page: '.$facts['contact_path'],
            default => $locale === 'fa'
                ? 'لطفاً پرسش کوتاهی دربارهٔ نمونه‌کار بپرسید.'
                : 'Please ask a short question about the portfolio.',
        };
    }

    /**
     * @param  array{resume_url:string,email:?string,linkedin:?string,github:?string,telegram:?string,contact_path:string}  $facts
     */
    protected function contactReply(string $locale, array $facts): string
    {
        $bits = [];
        if (! empty($facts['email'])) {
            $bits[] = $facts['email'];
        }
        if (! empty($facts['linkedin'])) {
            $bits[] = 'LinkedIn: '.$facts['linkedin'];
        }
        if (! empty($facts['github'])) {
            $bits[] = 'GitHub: '.$facts['github'];
        }
        if (! empty($facts['telegram'])) {
            $bits[] = 'Telegram: '.$facts['telegram'];
        }

        $joined = $bits ? implode(' · ', $bits) : '';

        if ($locale === 'fa') {
            return $joined !== ''
                ? 'راه‌های تماس عمومی: '.$joined.' — یا صفحهٔ تماس: '.$facts['contact_path']
                : 'از صفحهٔ تماس استفاده کنید: '.$facts['contact_path'];
        }

        return $joined !== ''
            ? 'Public contact: '.$joined.' — or the Contact page: '.$facts['contact_path']
            : 'Use the Contact page: '.$facts['contact_path'];
    }

    protected function normalize(string $message): string
    {
        $message = mb_strtolower(trim($message));
        $message = str_replace(['ي', 'ك', '‌'], ['ی', 'ک', ''], $message);
        // Keep letters/numbers/spaces; drop punctuation so “Hi!” and “سلام.” match.
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

    protected function isResume(string $normalized): bool
    {
        return (bool) preg_match(
            '/^(cv|resume|résumé|his cv|her cv|your cv|my cv|the cv|his resume|your resume|the resume|download cv|download resume|download the cv|download the resume|get the cv|get the resume|send (me )?(the )?(cv|resume)|رزومه|سی وی|سیوی|دانلود رزومه|فایل رزومه|لینک رزومه)$/u',
            $normalized
        );
    }

    protected function isContact(string $normalized): bool
    {
        return (bool) preg_match(
            '/^(contact|contact (him|her|info|information|details)|how (can|do) i (contact|reach)( him| her)?|how to (contact|reach)( him| her)?|email|his email|your email|e mail|reach him|get in touch|اطلاعات تماس|راه ارتباطی|راه های ارتباطی|راههای ارتباطی|تماس|ایمیل|ایمیلش|ایمیل او|چطور تماس بگیرم)$/u',
            $normalized
        );
    }

    protected function isBooking(string $normalized): bool
    {
        // Avoid product topics like “booking systems” — require a clear schedule/call ask.
        return (bool) preg_match(
            '/^(book a call|book call|schedule a call|schedule call|book an appointment|book a meeting|i (want|would like) to book a call|رزرو تماس|رزرو جلسه|وقت مشاوره|میخوام تماس رزرو کنم|می خواهم تماس رزرو کنم)$/u',
            $normalized
        );
    }
}
