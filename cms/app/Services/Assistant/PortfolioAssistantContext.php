<?php

namespace App\Services\Assistant;

use App\Services\PortfolioContent;
use Illuminate\Support\Facades\Cache;

class PortfolioAssistantContext
{
    private const DEFAULT_RESUME_PATH = '/assets/cv/Amirhossein-Zamanzade-CV.pdf';

    /** @var array<string, list<string>> */
    private const TECH_ALIASES = [
        'laravel' => ['laravel', 'لاراول'],
        'react' => ['react', 'ریاکت', 'ری‌اکت'],
        'php' => ['php', 'پی اچ پی', 'پی‌اچ‌پی'],
        'python' => ['python', 'پایتون'],
        'java' => ['java', 'جاوا'],
        'javascript' => ['javascript', 'js', 'جاوااسکریپت', 'جاوا اسکریپت'],
        'node' => ['node', 'nodejs', 'node.js', 'نود', 'نودجی‌اس'],
        'mysql' => ['mysql', 'مای‌اسکیوال', 'مای اسکیوال'],
        'postgresql' => ['postgresql', 'postgres', 'پستگرس'],
        'mongodb' => ['mongodb', 'mongo', 'مانگو'],
        'telegram' => ['telegram', 'تلگرام'],
        'javafx' => ['javafx', 'جاوا اف ایکس'],
        'express' => ['express'],
        'dotnet' => ['.net', 'dotnet', 'asp.net', 'سی شارپ', 'c#'],
    ];

    public function __construct(private PortfolioContent $content) {}

    /**
     * Compact, question-scoped facts for the model (profile + ≤3 projects).
     *
     * @param  list<array{role:string,content:string}>  $priorExchange  Recent user and assistant messages, if any.
     */
    public function forQuestion(string $locale, string $question, array $priorExchange = [], bool $preview = false): string
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $payload = $this->content->payload($preview);

        return $this->buildCompact($payload, $locale, $question, $priorExchange, $preview);
    }

    /**
     * Legacy full dump kept for size comparisons / debugging — not used by ask().
     */
    public function forLocale(string $locale, bool $preview = false): string
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $payload = $this->content->payload($preview);
        $version = (string) ($payload['built_at'] ?? 'draft');
        $cacheKey = 'assistant.context.full.'.$locale.'.'.sha1($version.($preview ? '.preview' : ''));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($payload, $locale, $preview) {
            return $this->buildFull($payload, $locale, $preview);
        });
    }

    /**
     * @return array{
     *   resume_url:string,
     *   email:?string,
     *   linkedin:?string,
     *   github:?string,
     *   telegram:?string,
     *   contact_path:string
     * }
     */
    public function publicContactFacts(string $locale, bool $preview = false): array
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';

        return $this->contactFactsFromPayload($this->content->payload($preview), $locale);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   resume_url:string,
     *   email:?string,
     *   linkedin:?string,
     *   github:?string,
     *   telegram:?string,
     *   contact_path:string
     * }
     */
    protected function contactFactsFromPayload(array $payload, string $locale): array
    {
        $locale = $locale === 'fa' ? 'fa' : 'en';
        $social = (array) ($payload['social'] ?? []);
        $resume = (array) ($payload['resume'] ?? []);
        $resumeUrl = trim((string) ($resume['url'] ?? ''));
        if ($resumeUrl === '') {
            $resumeUrl = self::DEFAULT_RESUME_PATH;
        }

        return [
            'resume_url' => $this->publicUrl($resumeUrl),
            'email' => filled($social['email'] ?? null) ? (string) $social['email'] : null,
            'linkedin' => filled($social['linkedin'] ?? null) ? $this->publicUrl((string) $social['linkedin']) : null,
            'github' => filled($social['github'] ?? null) ? $this->publicUrl((string) $social['github']) : null,
            'telegram' => filled($social['telegram'] ?? null) ? $this->publicUrl((string) $social['telegram']) : null,
            'contact_path' => $locale === 'fa' ? '/fa/contact' : '/contact',
        ];
    }

    /**
     * @param  list<array{role:string,content:string}>  $priorExchange
     */
    protected function buildCompact(array $payload, string $locale, string $question, array $priorExchange, bool $preview = false): string
    {
        $strings = (array) ($payload['strings'][$locale] ?? []);
        $profile = (array) ($payload['profile'] ?? []);
        $projects = (array) ($payload['projects'] ?? []);
        $featuredSlugs = array_values(array_filter((array) ($payload['featured_slugs'] ?? [])));
        $contact = $this->contactFactsFromPayload($payload, $locale);

        $name = $locale === 'fa'
            ? (string) ($profile['name_fa'] ?? $strings['home.profile.name'] ?? 'امیرحسین زمان‌زاده')
            : (string) ($profile['name'] ?? $strings['home.profile.name'] ?? 'Amirhossein Zamanzade');

        $role = (string) ($strings['home.profile.role'] ?? ($locale === 'fa' ? 'مهندس نرم‌افزار' : 'Software Engineer'));
        $about = trim((string) ($strings['about.lead'] ?? $strings['about.intro'] ?? $strings['about.p1'] ?? ''));

        $facts = [];
        foreach (['about.location', 'about.education', 'about.status', 'about.meta.location', 'about.meta.education', 'about.meta.status'] as $key) {
            if (! empty($strings[$key])) {
                $facts[] = $strings[$key];
            }
        }

        $skills = isset($payload['settings']['about_skills'])
            ? array_merge(...array_values($payload['settings']['about_skills']))
            : $this->skillsFromStrings($strings, $locale);

        $lines = [];
        $lines[] = 'Subject: '.$name.' — '.$role;
        $lines[] = 'Team: '.($strings['about.team.name'] ?? config('novin.'.$locale.'.name')).' — '.($strings['about.team.role'] ?? config('novin.'.$locale.'.role'));
        $knowledge = trim((string) ($payload['settings']['assistant_knowledge'][$locale] ?? ''));
        if ($knowledge !== '') {
            $lines[] = 'Additional public facts: '.$this->clip($knowledge, 450);
        }
        if ($about !== '') {
            $lines[] = 'Bio: '.$this->clip($about, 280);
        }
        if ($facts) {
            $lines[] = 'Facts: '.$this->clip(implode(' | ', $facts), 220);
        }
        if ($skills) {
            $lines[] = 'Skills: '.$this->clip(implode(', ', $skills), 260);
        }

        $selected = $this->selectProjects($projects, $locale, $question, $priorExchange, $featuredSlugs);
        $lines[] = $selected['mode'] === 'featured'
            ? 'Featured projects (overview):'
            : 'Relevant projects:';

        foreach ($selected['projects'] as $project) {
            $lines[] = $this->formatProjectLine($project, $locale);
        }

        $contactBits = array_filter([
            $contact['email'] ? 'email: '.$contact['email'] : null,
            'Contact: '.$contact['contact_path'],
            'Résumé: '.$contact['resume_url'],
            'Book a call: '.$contact['contact_path'],
        ]);
        $lines[] = 'Public contact: '.implode(' | ', $contactBits);

        $context = $this->clip(implode("\n", $lines), 2200);
        $biography = $this->relevantBiography($locale, $question, $priorExchange);

        return $biography === [] ? $context : "Verified biographical facts from the owner:\n".implode("\n", $biography)."\n\n".$context;
    }

    /**
     * @param  list<array{role:string,content:string}>  $priorExchange
     * @return list<string>
     */
    protected function relevantBiography(string $locale, string $question, array $priorExchange): array
    {
        $query = ' '.$this->fold($question).' ';
        $topics = config('assistant-biography', []);
        $details = config('assistant-biography-details', []);
        $matched = collect($topics)->filter(fn (array $entry) => $this->matchesBiographyTopic($query, $entry['match']));
        $relationship = $topics['relationship'] ?? null;
        if ($relationship !== null && ! $matched->has('relationship') && $this->isBiographyFollowUp($query)) {
            foreach (array_reverse($priorExchange) as $turn) {
                if ($turn['role'] !== 'user') {
                    continue;
                }

                $previous = ' '.$this->fold($turn['content']).' ';
                if ($this->matchesBiographyTopic($previous, $relationship['match'])) {
                    $matched->put('relationship', $relationship);
                    break;
                }

                if (! $this->isBiographyFollowUp($previous)) {
                    break;
                }
            }
        }

        if ($matched->keys()->all() === ['relationship']) {
            return [
                $relationship[$locale],
                'These are the only verified facts about this relationship. Answer only the detail asked and distinguish any unknown detail from what is already known.',
            ];
        }

        // Keep clearly scoped project and navigation questions on the smaller context.
        $scoped = preg_match('/(?:\b(?:stack|portfolio projects|which .*projects?|open|navigate|show me the cv)\b|چه پروژه|کدام پروژه|باز کن|ببند|تکنولوژی های پروژه)/u', $query);
        if ($scoped && $matched->isEmpty() && $priorExchange === []) {
            return [];
        }

        return collect($topics)
            ->filter(fn (array $entry, string $topic) => $topic !== 'relationship' || $matched->has($topic))
            ->map(static fn (array $entry, string $topic) => $entry[$locale].' '.($details[$topic][$locale] ?? ''))
            ->map(trim(...))
            ->values()->all();
    }

    /** @param list<string> $terms */
    protected function matchesBiographyTopic(string $query, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($query, ' '.$this->fold($term).' ')) {
                return true;
            }
        }

        return false;
    }

    protected function isBiographyFollowUp(string $query): bool
    {
        return (bool) preg_match('/^(?:(?:and |so )?(?:tell me more|what about (?:him|her|them)|who is she|how long|since when)(?: about (?:him|her|them|it))?|(?:خب |پس )?(?:بیشتر بگو|بیشتر توضیح بده|دیگه چی|اون کیه|از کی|چند وقته))$/u', trim($query));
    }

    protected function buildFull(array $payload, string $locale, bool $preview = false): string
    {
        // Legacy full dump (up to 12 projects) for before/after size reporting.
        $strings = (array) ($payload['strings'][$locale] ?? []);
        $profile = (array) ($payload['profile'] ?? []);
        $projects = (array) ($payload['projects'] ?? []);
        $contact = $this->contactFactsFromPayload($payload, $locale);

        $name = $locale === 'fa'
            ? (string) ($profile['name_fa'] ?? $strings['home.profile.name'] ?? 'امیرحسین زمان‌زاده')
            : (string) ($profile['name'] ?? $strings['home.profile.name'] ?? 'Amirhossein Zamanzade');
        $role = (string) ($strings['home.profile.role'] ?? ($locale === 'fa' ? 'مهندس نرم‌افزار' : 'Software Engineer'));
        $about = trim((string) ($strings['about.lead'] ?? $strings['about.intro'] ?? $strings['about.p1'] ?? ''));
        if ($about === '') {
            $about = trim(implode(' ', array_filter([
                (string) ($strings['about.p1'] ?? ''),
                (string) ($strings['about.p2'] ?? ''),
            ])));
        }

        $lines = [];
        $lines[] = 'Subject: '.$name.' — '.$role;
        $lines[] = 'Team: '.($strings['about.team.name'] ?? config('novin.'.$locale.'.name')).' — '.($strings['about.team.role'] ?? config('novin.'.$locale.'.role'));
        $knowledge = trim((string) ($payload['settings']['assistant_knowledge'][$locale] ?? ''));
        if ($knowledge !== '') {
            $lines[] = 'Additional public facts: '.$this->clip($knowledge, 450);
        }
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

        $skills = isset($payload['settings']['about_skills'])
            ? array_merge(...array_values($payload['settings']['about_skills']))
            : $this->skillsFromStrings($strings, $locale);
        $lines[] = 'Skills: '.$this->clip(implode(', ', $skills), 500);
        $lines[] = 'Projects:';

        $count = 0;
        foreach ($projects as $project) {
            if (! is_array($project)) {
                continue;
            }
            if ($count >= 12) {
                break;
            }
            $lines[] = $this->formatProjectLine($project, $locale, 220);
            $count++;
        }

        $lines[] = 'Public contact: '.implode(' | ', array_filter([
            $contact['email'] ? 'email: '.$contact['email'] : null,
            $contact['linkedin'] ? 'LinkedIn: '.$contact['linkedin'] : null,
            $contact['github'] ? 'GitHub: '.$contact['github'] : null,
            $contact['telegram'] ? 'Telegram: '.$contact['telegram'] : null,
            'Contact page: /contact (FA: /fa/contact)',
            'Book a call: available on the Contact page',
            'Résumé: '.$contact['resume_url'],
        ]));

        return $this->clip(implode("\n", $lines), 6000);
    }

    /**
     * @param  list<mixed>  $projects
     * @param  list<array{role:string,content:string}>  $priorExchange
     * @param  list<string>  $featuredSlugs
     * @return array{mode:string,projects:list<array>}
     */
    public function selectProjects(array $projects, string $locale, string $question, array $priorExchange = [], array $featuredSlugs = []): array
    {
        $normalized = array_values(array_filter(array_map(
            static fn ($p) => is_array($p) ? $p : null,
            $projects
        )));

        $query = $this->searchHaystack($question, $priorExchange);
        $scored = [];

        foreach ($normalized as $index => $project) {
            $score = $this->scoreProject($project, $locale, $query);
            if ($score > 0) {
                $scored[] = ['score' => $score, 'index' => $index, 'project' => $project];
            }
        }

        usort($scored, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'] ?: $a['index'] <=> $b['index'];
        });

        if ($scored !== []) {
            return [
                'mode' => 'relevant',
                'projects' => array_map(
                    static fn (array $row) => $row['project'],
                    array_slice($scored, 0, 3)
                ),
            ];
        }

        return [
            'mode' => 'featured',
            'projects' => $this->featuredProjects($normalized, $featuredSlugs, 3),
        ];
    }

    /**
     * @param  list<array>  $projects
     * @param  list<string>  $featuredSlugs
     * @return list<array>
     */
    protected function featuredProjects(array $projects, array $featuredSlugs, int $limit): array
    {
        $bySlug = [];
        foreach ($projects as $project) {
            $slug = (string) ($project['slug'] ?? '');
            if ($slug !== '') {
                $bySlug[$slug] = $project;
            }
        }

        $picked = [];
        foreach ($featuredSlugs as $slug) {
            if (isset($bySlug[$slug])) {
                $picked[] = $bySlug[$slug];
            }
            if (count($picked) >= $limit) {
                return $picked;
            }
        }

        foreach ($projects as $project) {
            if (! empty($project['is_featured']) && ! in_array($project, $picked, true)) {
                $picked[] = $project;
            }
            if (count($picked) >= $limit) {
                return $picked;
            }
        }

        return array_slice($projects, 0, $limit);
    }

    /**
     * @param  list<array{role:string,content:string}>  $priorExchange
     */
    protected function searchHaystack(string $question, array $priorExchange): string
    {
        $parts = [$question];
        foreach ($priorExchange as $row) {
            $parts[] = (string) ($row['content'] ?? '');
        }

        return $this->fold(implode(' ', $parts));
    }

    protected function scoreProject(array $project, string $locale, string $query): int
    {
        if ($query === '') {
            return 0;
        }

        $score = 0;
        $loc = (array) ($project[$locale] ?? []);
        $alt = (array) ($project[$locale === 'fa' ? 'en' : 'fa'] ?? []);

        $title = $this->fold(implode(' ', array_filter([
            (string) ($loc['title'] ?? ''),
            (string) ($project['title'] ?? ''),
            (string) ($project['slug'] ?? ''),
            (string) ($alt['title'] ?? ''),
        ])));

        $desc = $this->fold(implode(' ', array_filter([
            (string) ($loc['shortDescription'] ?? ''),
            (string) ($loc['fullDescription'] ?? ''),
            (string) ($loc['role'] ?? ''),
            (string) ($loc['category'] ?? ''),
            (string) ($alt['shortDescription'] ?? ''),
            (string) ($project['category'] ?? ''),
        ])));

        $techList = isset($project['technologies']) && is_array($project['technologies'])
            ? $project['technologies']
            : [];
        $techJoined = $this->fold(implode(' ', $techList));

        foreach ($this->tokens($query) as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }
            if ($title !== '' && str_contains($title, $token)) {
                $score += 8;
            }
            if ($desc !== '' && str_contains($desc, $token)) {
                $score += 3;
            }
            if ($techJoined !== '' && str_contains($techJoined, $token)) {
                $score += 6;
            }
        }

        // Phrase / alias boosts for mixed-language tech asks.
        foreach (self::TECH_ALIASES as $canonical => $aliases) {
            $hit = false;
            foreach ($aliases as $alias) {
                if (str_contains($query, $this->fold($alias))) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit) {
                continue;
            }
            $needle = $this->fold($canonical);
            if (str_contains($techJoined, $needle) || str_contains($title, $needle) || str_contains($desc, $needle)) {
                $score += 10;
            }
            foreach ($aliases as $alias) {
                $a = $this->fold($alias);
                if ($a !== $needle && (str_contains($techJoined, $a) || str_contains($desc, $a))) {
                    $score += 8;
                }
            }
        }

        // Slug / title continuity for follow-ups (“that project”).
        $slug = $this->fold((string) ($project['slug'] ?? ''));
        $plainTitle = $this->fold((string) ($project['title'] ?? ''));
        if ($slug !== '' && str_contains($query, $slug)) {
            $score += 12;
        }
        if ($plainTitle !== '' && str_contains($query, $plainTitle)) {
            $score += 12;
        }

        return $score;
    }

    protected function formatProjectLine(array $project, string $locale, int $descLimit = 140): string
    {
        $loc = (array) ($project[$locale] ?? []);
        $title = (string) ($loc['title'] ?? $project['title'] ?? $project['slug'] ?? 'Project');
        $cat = (string) ($loc['category'] ?? $project['category'] ?? '');
        $short = (string) ($loc['shortDescription'] ?? '');
        $roleLine = (string) ($loc['role'] ?? '');
        $tech = isset($project['technologies']) && is_array($project['technologies'])
            ? implode(', ', array_slice($project['technologies'], 0, 8))
            : '';

        $chunk = '- '.$title;
        if ($cat !== '') {
            $chunk .= ' ('.$cat.')';
        }
        if ($roleLine !== '') {
            $chunk .= ' — role: '.$this->clip($roleLine, 80);
        }
        if ($short !== '') {
            $chunk .= ' — '.$this->clip($short, $descLimit);
        }
        if ($tech !== '') {
            $chunk .= ' — stack: '.$this->clip($tech, 120);
        }
        if (! empty($project['liveUrl'])) {
            $chunk .= ' — live: '.$this->publicUrl((string) $project['liveUrl']);
        }
        if (! empty($project['githubUrl'])) {
            $chunk .= ' — code: '.$this->publicUrl((string) $project['githubUrl']);
        }

        return $chunk;
    }

    /**
     * @return list<string>
     */
    protected function skillsFromStrings(array $strings, string $locale): array
    {
        $skillKeys = array_filter(array_keys($strings), fn ($k) => str_starts_with((string) $k, 'skill.') || str_starts_with((string) $k, 'about.skill'));
        $skills = [];
        foreach ($skillKeys as $key) {
            $skills[] = $strings[$key];
        }
        if (! $skills) {
            $skills = ['HTML', 'CSS', 'JavaScript', 'React', 'Python', 'Java', 'PHP', 'Laravel', 'MySQL', 'Node.js'];
        }

        return array_values(array_unique($skills));
    }

    /**
     * @return list<string>
     */
    protected function tokens(string $folded): array
    {
        $parts = preg_split('/\s+/u', $folded) ?: [];

        return array_values(array_filter($parts, static fn ($t) => $t !== ''));
    }

    protected function fold(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['ي', 'ك', '‌'], ['ی', 'ک', ''], $text);
        $text = preg_replace('/[^\p{L}\p{N}\s.+#]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
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
        if (str_starts_with($url, '/')) {
            return $url;
        }

        return '/'.ltrim($url, '/');
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
