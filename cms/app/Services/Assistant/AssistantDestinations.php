<?php

namespace App\Services\Assistant;

/**
 * Approved assistant navigation targets. URLs are resolved only in application code.
 */
class AssistantDestinations
{
    public const WORK = 'work';

    public const ABOUT = 'about';

    public const CONTACT = 'contact';

    public const CV = 'cv';

    /** @var list<string> */
    public const IDS = [self::WORK, self::ABOUT, self::CONTACT, self::CV];

    public function __construct(private PortfolioAssistantContext $context) {}

    public function isAllowed(string $id): bool
    {
        return in_array($id, self::IDS, true);
    }

    /**
     * @return array{id:string,url:string,mode:string,label:string}|null
     */
    public function resolve(string $id, string $locale, bool $preview = false): ?array
    {
        $id = strtolower(trim($id));
        if (! $this->isAllowed($id)) {
            return null;
        }

        $locale = $locale === 'fa' ? 'fa' : 'en';
        $prefix = $locale === 'fa' ? '/fa' : '';

        return match ($id) {
            self::WORK => [
                'id' => self::WORK,
                'url' => $prefix.'/work',
                'mode' => 'spa',
                'label' => $locale === 'fa' ? 'نمونه‌کارها' : 'Work',
            ],
            self::ABOUT => [
                'id' => self::ABOUT,
                'url' => $prefix.'/about',
                'mode' => 'spa',
                'label' => $locale === 'fa' ? 'درباره' : 'About',
            ],
            self::CONTACT => [
                'id' => self::CONTACT,
                'url' => $prefix.'/contact',
                'mode' => 'spa',
                'label' => $locale === 'fa' ? 'تماس' : 'Contact',
            ],
            self::CV => [
                'id' => self::CV,
                'url' => $this->context->publicContactFacts($locale, $preview)['resume_url'],
                'mode' => 'cv',
                'label' => $locale === 'fa' ? 'رزومه' : 'CV',
            ],
            default => null,
        };
    }
}
