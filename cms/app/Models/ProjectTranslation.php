<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTranslation extends Model
{
    protected $fillable = [
        'project_id', 'locale', 'short_description', 'full_description', 'role', 'category_label',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
