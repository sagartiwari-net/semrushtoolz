<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPrice extends Model
{
    protected $fillable = [
        'tool_id',
        'price_inr',
    ];

    protected function casts(): array
    {
        return [
            'price_inr' => 'integer',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
