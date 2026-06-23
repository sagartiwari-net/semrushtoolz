<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class AffiliateWalletTransferService
{
    public function __construct(
        protected WalletService $wallet,
        protected AffiliateService $affiliates,
    ) {}

    public function transfer(User $user, float $amount): array
    {
        $config = SiteSetting::walletConfig();
        $amount = round($amount, 2);

        if (! $config['enabled']) {
            throw new \RuntimeException('Wallet is not enabled.');
        }

        if ($amount < $config['min_affiliate_transfer']) {
            throw new \RuntimeException('Minimum transfer is ₹'.$config['min_affiliate_transfer'].'.');
        }

        $available = $this->affiliates->availablePayoutBalance($user);
        if ($amount > $available) {
            throw new \RuntimeException('Amount exceeds available affiliate balance (₹'.number_format($available, 0).').');
        }

        $bonusRate = $config['affiliate_transfer_bonus_percent'];
        $bonus = round($amount * $bonusRate, 2);
        $totalCredit = round($amount + $bonus, 2);

        return DB::transaction(function () use ($user, $amount, $bonus, $totalCredit, $bonusRate) {
            $this->consumeCommissions($user, $amount);

            $transferTx = $this->wallet->credit(
                $user,
                $amount,
                WalletTransaction::TYPE_AFFILIATE_TRANSFER,
                'Affiliate earnings transferred to wallet',
                null,
                ['principal' => $amount, 'irreversible' => true],
            );

            $bonusTx = null;
            if ($bonus > 0) {
                $bonusTx = $this->wallet->credit(
                    $user,
                    $bonus,
                    WalletTransaction::TYPE_AFFILIATE_BONUS,
                    (int) ($bonusRate * 100).'% bonus on affiliate transfer',
                    null,
                    ['principal' => $amount, 'transfer_transaction_id' => $transferTx->id],
                );
            }

            return [
                'principal' => $amount,
                'bonus' => $bonus,
                'total_credited' => $totalCredit,
                'transfer_transaction' => $transferTx,
                'bonus_transaction' => $bonusTx,
            ];
        });
    }

    protected function consumeCommissions(User $user, float $amount): void
    {
        $remaining = $amount;

        $commissions = AffiliateCommission::query()
            ->where('referrer_user_id', $user->id)
            ->where('status', 'approved')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($commissions as $commission) {
            if ($remaining <= 0) {
                break;
            }

            $commissionAmount = (float) $commission->amount;
        if ($commissionAmount <= $remaining + 0.001) {
                $commission->update(['status' => 'wallet_transferred']);
                $remaining = round($remaining - $commissionAmount, 2);
            } elseif ($remaining > 0) {
                $leftover = round($commissionAmount - $remaining, 2);
                $commission->update([
                    'amount' => $remaining,
                    'status' => 'wallet_transferred',
                ]);
                AffiliateCommission::create([
                    'referrer_user_id' => $commission->referrer_user_id,
                    'referred_user_id' => $commission->referred_user_id,
                    'order_id' => $commission->order_id,
                    'amount' => $leftover,
                    'rate' => $commission->rate,
                    'status' => 'approved',
                    'hold_until' => $commission->hold_until,
                ]);
                $remaining = 0;
            }
        }

        if ($remaining > 0.01) {
            throw new \RuntimeException('Could not allocate affiliate commissions for this transfer.');
        }
    }
}
