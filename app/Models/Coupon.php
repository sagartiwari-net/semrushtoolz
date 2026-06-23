<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'type',
        'value',
        'currency',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'min_order_amount',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
        'allowed_plan_ids',
        'allowed_tool_ids',
        'allowed_duration_months',
        'required_plan_ids',
        'required_tool_ids',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
            'allowed_plan_ids' => 'array',
            'allowed_tool_ids' => 'array',
            'allowed_duration_months' => 'array',
            'required_plan_ids' => 'array',
            'required_tool_ids' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function discountLabel(): string
    {
        if ($this->type === self::TYPE_PERCENT) {
            return rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off';
        }

        $symbol = $this->currency === 'usd' ? '$' : '₹';

        return $symbol.number_format((float) $this->value, 0).' off';
    }

    public function usesLabel(): string
    {
        $global = $this->max_uses === null
            ? $this->used_count.'/∞'
            : $this->used_count.'/'.$this->max_uses;

        if ($this->max_uses_per_user !== null) {
            return $global.' · '.$this->max_uses_per_user.'/user';
        }

        return $global;
    }

    public function restrictionsSummary(): string
    {
        $parts = [];

        if ($this->hasProductRestrictions()) {
            $parts[] = 'Product';
        }

        if ($this->hasDurationRestrictions()) {
            $months = collect($this->allowed_duration_months)->sort()->implode(', ');
            $parts[] = $months.' mo';
        }

        if ($this->hasRequiredSubscription()) {
            $parts[] = 'Sub required';
        }

        if ($this->min_order_amount !== null) {
            $parts[] = 'Min ₹'.number_format((float) $this->min_order_amount, 0);
        }

        return $parts === [] ? 'Any order' : implode(' · ', $parts);
    }

    public function hasProductRestrictions(): bool
    {
        return $this->hasAllowedPlans() || $this->hasAllowedTools();
    }

    public function hasAllowedPlans(): bool
    {
        return filled($this->allowed_plan_ids);
    }

    public function hasAllowedTools(): bool
    {
        return filled($this->allowed_tool_ids);
    }

    public function hasDurationRestrictions(): bool
    {
        return filled($this->allowed_duration_months);
    }

    public function hasRequiredSubscription(): bool
    {
        return $this->hasRequiredPlans() || $this->hasRequiredTools();
    }

    public function hasRequiredPlans(): bool
    {
        return filled($this->required_plan_ids);
    }

    public function hasRequiredTools(): bool
    {
        return filled($this->required_tool_ids);
    }

    public function hasUsesRemaining(): bool
    {
        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    public function isWithinDateRange(): bool
    {
        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }
}
