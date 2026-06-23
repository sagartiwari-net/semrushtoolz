<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function isEnabled(): bool
    {
        return SiteSetting::walletConfig()['enabled'];
    }

    public function balance(User $user): float
    {
        return round((float) $user->wallet_balance, 2);
    }

    public function credit(
        User $user,
        float $amount,
        string $type,
        ?string $description = null,
        ?Model $reference = null,
        array $meta = [],
    ): WalletTransaction {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $reference, $meta) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $newBalance = round((float) $locked->wallet_balance + $amount, 2);

            $locked->update(['wallet_balance' => $newBalance]);

            return WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'currency' => 'inr',
                'description' => $description,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'meta' => $meta ?: null,
            ]);
        });
    }

    public function debit(
        User $user,
        float $amount,
        string $type,
        ?string $description = null,
        ?Model $reference = null,
        array $meta = [],
    ): WalletTransaction {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $reference, $meta) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->wallet_balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $newBalance = round((float) $locked->wallet_balance - $amount, 2);
            $locked->update(['wallet_balance' => $newBalance]);

            return WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'currency' => 'inr',
                'description' => $description,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'meta' => $meta ?: null,
            ]);
        });
    }

    public function paginatedHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatedAll(?string $type = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->with('user:id,name,email,wallet_balance')
            ->when(filled($type), fn ($query) => $query->where('type', $type))
            ->when(filled($search), function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function adminAdjust(User $user, float $amount, string $note, User $admin): WalletTransaction
    {
        $meta = ['admin_id' => $admin->id, 'admin_email' => $admin->email];

        if ($amount > 0) {
            return $this->credit($user, $amount, WalletTransaction::TYPE_ADMIN_ADJUSTMENT, $note, null, $meta);
        }

        return $this->debit($user, abs($amount), WalletTransaction::TYPE_ADMIN_ADJUSTMENT, $note, null, $meta);
    }

    public function formatInr(float $amount): string
    {
        return '₹'.number_format($amount, $amount < 100 ? 2 : 0);
    }

    public function qualifiesForCashback(Order $order): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($order->order_type === 'wallet_topup') {
            return false;
        }

        if ($order->currency !== 'inr') {
            return false;
        }

        if (in_array($order->payment_method, ['wallet'], true)) {
            return false;
        }

        if ((float) $order->wallet_amount_used > 0) {
            return false;
        }

        if ($order->status !== 'completed') {
            return false;
        }

        if ((float) $order->wallet_cashback_amount > 0) {
            return false;
        }

        return true;
    }
}
