<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudAccount extends Model
{
    protected $fillable = [
        'tool', 'label', 'proxy_slug', 'seat_limit',
        'is_active', 'health_status', 'last_health_check',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_health_check' => 'datetime',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ToolSession::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->sessions()->where('status', 'active')->whereNull('ended_at');
    }
}
