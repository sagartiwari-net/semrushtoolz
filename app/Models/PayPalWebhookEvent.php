<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayPalWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'resource_id',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
