<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const PROJECT_PREFIX = 'project-details.';

    public const EXPERIENCE_PREFIX = 'work-experience.';

    public const EXPERIENCE_TYPES = [
        'full-time' => ['en' => 'Full-time', 'fa' => 'تمام‌وقت'],
        'part-time' => ['en' => 'Part-time', 'fa' => 'پاره‌وقت'],
        'contract' => ['en' => 'Contract', 'fa' => 'قراردادی'],
        'freelance' => ['en' => 'Freelance', 'fa' => 'فریلنس'],
        'internship' => ['en' => 'Internship', 'fa' => 'کارآموزی'],
    ];

    protected $fillable = ['key', 'value'];

    public static function isRemovedEmployer(array $entry): bool
    {
        $host = strtolower((string) parse_url($entry['website'] ?? '', PHP_URL_HOST));
        $companyEn = strtolower(trim($entry['company_en'] ?? ''));
        $companyFa = str_replace(['‌', ' '], '', $entry['company_fa'] ?? '');

        return in_array($host, ['medpip.com', 'www.medpip.com'], true)
            || in_array($companyEn, ['pol ideal pars', 'pol ideal pars (medpip)', 'medpip'], true)
            || str_starts_with($companyFa, 'پلایدهآلپارس')
            || str_starts_with($companyFa, 'پلایدهالپارس')
            || ($entry['logo_path'] ?? null) === 'assets/img/experience/medpip.webp';
    }

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
