<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    public const TYPE_TOPUP = 'topup';

    public const TYPE_AFFILIATE_TRANSFER = 'affiliate_transfer';

    public const TYPE_AFFILIATE_BONUS = 'affiliate_bonus';

    public const TYPE_CASHBACK = 'cashback';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCredit(): bool
    {
        return (float) $this->amount > 0;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TOPUP => 'Top-up',
            self::TYPE_AFFILIATE_TRANSFER => 'Affiliate transfer',
            self::TYPE_AFFILIATE_BONUS => 'Affiliate bonus (10%)',
            self::TYPE_CASHBACK => 'Purchase cashback',
            self::TYPE_PURCHASE => 'Subscription purchase',
            self::TYPE_REFUND => 'Refund credit',
            self::TYPE_ADMIN_ADJUSTMENT => 'Admin adjustment',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
