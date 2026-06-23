<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolAccessServer extends Model
{
    protected $fillable = [
        'tool_access_group_id', 'slug', 'label', 'type',
        'domain', 'website_id', 'secret_key', 'direct_url', 'extension_tool_key',
        'section_title', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ToolAccessGroup::class, 'tool_access_group_id');
    }

    public function isProxy(): bool
    {
        return $this->type === 'proxy';
    }

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function isExtension(): bool
    {
        return $this->type === 'extension';
    }
}
