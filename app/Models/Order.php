<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'tool_id', 'subscription_id', 'order_number', 'order_type', 'duration_months', 'duration_days',
        'currency', 'subtotal', 'discount', 'total',
        'coupon_id', 'coupon_code', 'coupon_discount',
        'referral_bonus_discount', 'referral_bonus_percent',
        'taxable_amount', 'gst_rate', 'gst_amount',
        'wallet_amount_used', 'wallet_cashback_amount', 'balance_credit_inr',
        'payment_method', 'hub_order_id', 'hub_payment_url', 'paypal_subscription_id', 'paypal_billing_plan_id', 'is_recurring',
        'status', 'payment_proof', 'payment_note',
        'admin_note', 'paid_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'referral_bonus_discount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'wallet_amount_used' => 'decimal:2',
            'wallet_cashback_amount' => 'decimal:2',
            'balance_credit_inr' => 'decimal:2',
            'total' => 'decimal:2',
            'is_recurring' => 'boolean',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function purchasedItemName(): string
    {
        if ($this->order_type === 'wallet_topup') {
            return 'Wallet Top-up';
        }

        if ($this->order_type === 'reseller_balance_topup') {
            return 'Reseller Balance Top-up';
        }

        return $this->plan?->name ?? $this->tool?->name ?? 'Subscription';
    }

    public function isWalletTopup(): bool
    {
        return $this->order_type === 'wallet_topup';
    }

    public function isResellerBalanceTopup(): bool
    {
        return $this->order_type === 'reseller_balance_topup';
    }

    public function isBalanceTopup(): bool
    {
        return $this->isWalletTopup() || $this->isResellerBalanceTopup();
    }

    public function creditAmountInr(): float
    {
        if ($this->balance_credit_inr !== null) {
            return (float) $this->balance_credit_inr;
        }

        return (float) $this->total;
    }

    public function isSubscription(): bool
    {
        return $this->order_type === 'subscription' || $this->order_type === null;
    }

    public function hasGst(): bool
    {
        return (float) $this->gst_amount > 0 && $this->gst_rate !== null;
    }

    public function amountBeforeGst(): float
    {
        return (float) ($this->taxable_amount ?? $this->total);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.strtoupper(substr(uniqid(), -8));
    }
}
