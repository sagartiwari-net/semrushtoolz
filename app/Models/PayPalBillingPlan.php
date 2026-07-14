<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayPalBillingPlan extends Model
{
    protected $table = 'paypal_billing_plans';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'duration_months',
        'amount_usd',
        'total_cycles',
        'paypal_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
        ];
    }
}
