<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerProvision extends Model
{
    protected $fillable = [
        'reseller_user_id',
        'end_user_id',
        'end_user_email',
        'tool_id',
        'duration_months',
        'amount_charged',
        'was_new_user',
        'password_reset',
        'subscription_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancelled_by_role',
        'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount_charged' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'was_new_user' => 'boolean',
            'password_reset' => 'boolean',
            'duration_months' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_user_id');
    }

    public function endUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'end_user_id');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
