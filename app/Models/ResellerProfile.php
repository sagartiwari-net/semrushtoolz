<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'is_active',
        'notes',
        'monthly_cancel_limit',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'monthly_cancel_limit' => 'integer',
        ];
    }

    /** null or <=0 means cancel is disabled for this reseller. */
    public function canCancelAccess(): bool
    {
        return $this->monthly_cancel_limit !== null && (int) $this->monthly_cancel_limit > 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
