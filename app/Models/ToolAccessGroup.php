<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolAccessGroup extends Model
{
    protected $fillable = [
        'tool_id', 'slug', 'title', 'subtitle', 'logo_url', 'grant',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(ToolAccessServer::class)->orderBy('sort_order');
    }

    public function activeServers(): HasMany
    {
        return $this->servers()->where('is_active', true);
    }
}
