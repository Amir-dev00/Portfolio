<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeVersion extends Model
{
    protected $fillable = [
        'label', 'source', 'pdf_media_id', 'fields_en', 'fields_fa', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'fields_en' => 'array',
            'fields_fa' => 'array',
            'is_current' => 'boolean',
        ];
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'pdf_media_id');
    }
}
