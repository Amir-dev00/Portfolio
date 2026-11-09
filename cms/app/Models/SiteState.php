<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteState extends Model
{
    protected $table = 'site_state';

    protected $fillable = ['current_publication_id'];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'current_publication_id');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
