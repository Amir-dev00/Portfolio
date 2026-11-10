<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'topic',
        'message',
        'starts_at',
        'ends_at',
        'timezone',
        'status',
        'cancel_token',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'completed']);
    }

    public function scopeOverlapping(Builder $query, $startsAt, $endsAt): Builder
    {
        return $query->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }
}
