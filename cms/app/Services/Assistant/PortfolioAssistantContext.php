<?php

namespace App\Services\Assistant;

use App\Services\PortfolioContent;
use Illuminate\Support\Facades\Cache;

class PortfolioAssistantContext
{
    public function __construct(private PortfolioContent $content) {}

    /**
     * Bounded, public-only facts for the model. Never includes bookings, admin, or secrets.
     */
    public function forLocale(string $locale, bool $preview = false): string
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $payload = $this->content->payload($preview);
        $version = (string) ($payload['built_at'] ?? 'draft');
        $cacheKey = 'assistant.context.'.$locale.'.'.sha1($version.($preview ? '.preview' : ''));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($payload, $locale) {
            return $this->build($payload, $locale);
        });
    }

    protected function build(array $payload, string $locale): string
    {
        $strings = (array) ($payload['strings'][$locale] ?? []);
        $profile = (array) ($payload['profile'] ?? []);
        $social = (array) ($payload['social'] ?? []);
        $projects = (array) ($payload['projects'] ?? []);

        $name = $locale === 'fa'
            ? (string) ($profile['name_fa'] ?? $strings['home.profile.name'] ?? 'امیرحسین زمان‌زاده')
            : (string) ($profile['name'] ?? $strings['home.profile.name'] ?? 'Amirhossein Zamanzade');

        $role = (string) ($strings['home.profile.role'] ?? ($locale === 'fa' ? 'مهندس نرم‌افزار' : 'Software Engineer'));
        $about = trim((string) ($strings['about.lead'] ?? $strings['about.intro'] ?? $strings['about.body'] ?? ''));
        if ($about === '') {
            $about = trim(implode("\n", array_filter([
                (string) ($strings['about.p1'] ?? ''),
                (string) ($strings['about.p2'] ?? ''),
            ])));
        }

        $lines = [];
        $lines[] = 'Subject: '.$name.' — '.$role;
        $lines[] = 'Portfolio site: public personal site for this engineer (EN/FA).';
        if ($about !== '') {
            $lines[] = 'Bio: '.$this->clip($about, 900);
        }

        $facts = [];
        foreach (['about.location', 'about.education', 'about.status', 'about.meta.location', 'about.meta.education', 'about.meta.status'] as $key) {
            if (! empty($strings[$key])) {
                $facts[] = $strings[$key];
            }
        }
        if ($facts) {
            $lines[] = 'Facts: '.$this->clip(implode(' | ', $facts), 500);
        }

        $skillKeys = array_filter(array_keys($strings), fn ($k) => str_starts_with((string) $k, 'skill.') || str_starts_with((string) $k, 'about.skill'));
        $skills = [];
        foreach ($skillKeys as $key) {
            $skills[] = $strings[$key];
        }
        // Static About page lists are also public knowledge; keep a compact default set if CMS lacks skill rows.
        if (! $skills) {
            $skills = $locale === 'fa'
                ? ['HTML', 'CSS', 'JavaScript', 'React', 'Python', 'Java', 'PHP', 'Laravel', 'MySQL', 'Node.js']
                : ['HTML', 'CSS', 'JavaScript', 'React', 'Python', 'Java', 'PHP', 'Laravel', 'MySQL', 'Node.js'];
        }
        $lines[] = 'Skills: '.$this->clip(implode(', ', array_unique($skills)), 500);

        $lines[] = 'Projects:';
        $count = 0;
        foreach ($projects as $project) {
            if ($count >= 12) {
                break;
            }
            if (! is_array($project)) {
                continue;
            }
            $loc = (array) ($project[$locale] ?? []);
            $title = (string) ($loc['title'] ?? $project['title'] ?? $project['slug'] ?? 'Project');
            $cat = (string) ($loc['category'] ?? $project['category'] ?? '');
            $short = (string) ($loc['shortDescription'] ?? '');
            $roleLine = (string) ($loc['role'] ?? '');
            $tech = isset($project['technologies']) && is_array($project['technologies'])
                ? implode(', ', array_slice($project['technologies'], 0, 10))
                : '';
            $chunk = '- '.$title;
            if ($cat !== '') {
                $chunk .= ' ('.$cat.')';
            }
            if ($roleLine !== '') {
                $chunk .= ' — role: '.$this->clip($roleLine, 120);
            }
            if ($short !== '') {
                $chunk .= ' — '.$this->clip($short, 220);
            }
            if ($tech !== '') {
                $chunk .= ' — stack: '.$this->clip($tech, 160);
            }
            if (! empty($project['liveUrl'])) {
                $chunk .= ' — live: '.$this->publicUrl((string) $project['liveUrl']);
            }
            if (! empty($project['githubUrl'])) {
                $chunk .= ' — code: '.$this->publicUrl((string) $project['githubUrl']);
            }
            $lines[] = $chunk;
            $count++;
        }

        $contact = [];
        if (! empty($social['email'])) {
            $contact[] = 'email: '.$social['email'];
        }
        if (! empty($social['linkedin'])) {
            $contact[] = 'LinkedIn: '.$this->publicUrl((string) $social['linkedin']);
        }
        if (! empty($social['github'])) {
            $contact[] = 'GitHub: '.$this->publicUrl((string) $social['github']);
        }
        if (! empty($social['telegram'])) {
            $contact[] = 'Telegram: '.$this->publicUrl((string) $social['telegram']);
        }
        $contact[] = 'Contact page: /contact (FA: /fa/contact)';
        $contact[] = 'Book a call: available on the Contact page';
        $lines[] = 'Public contact: '.implode(' | ', $contact);

        $text = implode("\n", $lines);

        return $this->clip($text, 6000);
    }

    protected function publicUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return url($url);
    }

    protected function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }
}
