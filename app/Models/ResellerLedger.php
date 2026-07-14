<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerLedger extends Model
{
    public const TYPE_CREDIT_ADMIN = 'credit_admin';

    public const TYPE_CREDIT_REQUEST = 'credit_request';

    public const TYPE_DEBIT_PROVISION = 'debit_provision';

    public const TYPE_DEBIT_ADJUSTMENT = 'debit_adjustment';

    public const TYPE_CREDIT_CANCEL_REFUND = 'credit_cancel_refund';

    public const TYPE_CREDIT_PAYMENT = 'credit_payment';

    protected $table = 'reseller_ledger';

    protected $fillable = [
        'reseller_user_id',
        'type',
        'amount_inr',
        'balance_after',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_inr' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_user_id');
    }
}
