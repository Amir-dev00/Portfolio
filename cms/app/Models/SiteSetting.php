<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const EXPERIENCE_PREFIX = 'work-experience.';

    public const EXPERIENCE_TYPES = [
        'full-time' => ['en' => 'Full-time', 'fa' => 'تمام‌وقت'],
        'part-time' => ['en' => 'Part-time', 'fa' => 'پاره‌وقت'],
        'contract' => ['en' => 'Contract', 'fa' => 'قراردادی'],
        'freelance' => ['en' => 'Freelance', 'fa' => 'فریلنس'],
        'internship' => ['en' => 'Internship', 'fa' => 'کارآموزی'],
    ];

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
