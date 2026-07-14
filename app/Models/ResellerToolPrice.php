<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerToolPrice extends Model
{
    protected $fillable = [
        'reseller_user_id',
        'tool_id',
        'price_inr',
    ];

    protected function casts(): array
    {
        return [
            'price_inr' => 'integer',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_user_id');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
