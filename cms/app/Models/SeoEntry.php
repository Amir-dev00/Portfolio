<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEntry extends Model
{
    protected $fillable = [
        'locale', 'scope', 'scope_key', 'title', 'description', 'og_image', 'robots', 'is_dirty',
    ];

    protected function casts(): array
    {
        return ['is_dirty' => 'boolean'];
    }
}
