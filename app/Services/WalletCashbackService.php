<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\WalletTransaction;

class WalletCashbackService
{
    public function __construct(
        protected WalletService $wallet,
    ) {}

    public function maybeAward(Order $order): ?WalletTransaction
    {
        if (! $this->wallet->qualifiesForCashback($order)) {
            return null;
        }

        $config = SiteSetting::walletConfig();
        $rate = (float) $config['purchase_cashback_percent'];
        if ($rate <= 0) {
            return null;
        }

        $base = (float) $order->total;
        $cashback = round($base * $rate, 2);
        if ($cashback <= 0) {
            return null;
        }

        $order->loadMissing('user');

        $tx = $this->wallet->credit(
            $order->user,
            $cashback,
            WalletTransaction::TYPE_CASHBACK,
            (int) ($rate * 100).'% cashback on '.$order->purchasedItemName(),
            $order,
            ['order_total' => $base, 'rate' => $rate],
        );

        $order->update(['wallet_cashback_amount' => $cashback]);

        app(TransactionalEmailService::class)->sendWalletCashback($order, $cashback);

        return $tx;
    }

    public function reverseForOrder(Order $order): void
    {
        if ((float) $order->wallet_cashback_amount <= 0) {
            return;
        }

        $order->loadMissing('user');

        $this->wallet->debit(
            $order->user,
            (float) $order->wallet_cashback_amount,
            WalletTransaction::TYPE_ADMIN_ADJUSTMENT,
            'Cashback reversed for refunded order '.$order->order_number,
            $order,
            ['reason' => 'order_refund'],
        );

        $order->update(['wallet_cashback_amount' => 0]);
    }
}
