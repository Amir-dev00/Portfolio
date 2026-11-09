<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentEntry extends Model
{
    protected $fillable = [
        'group', 'key', 'value_en', 'value_fa', 'is_dirty',
    ];

    protected function casts(): array
    {
        return ['is_dirty' => 'boolean'];
    }

    public function valueFor(string $locale): ?string
    {
        return $locale === 'fa' ? $this->value_fa : $this->value_en;
    }
}
