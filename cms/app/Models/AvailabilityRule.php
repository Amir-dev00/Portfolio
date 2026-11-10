<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilityRule extends Model
{
    protected $fillable = [
        'weekday',
        'start_time',
        'end_time',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weekday' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
