<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'disk', 'path', 'public_path', 'original_name', 'mime', 'size',
        'alt_en', 'alt_fa', 'is_published', 'used_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'used_by' => 'array',
            'size' => 'integer',
        ];
    }

    public function url(): string
    {
        if ($this->is_published && $this->public_path) {
            return asset($this->public_path);
        }

        return route('admin.media.raw', $this);
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }
}
