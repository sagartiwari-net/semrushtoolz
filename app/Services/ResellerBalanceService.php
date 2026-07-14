<?php

namespace App\Services;

use App\Models\ResellerBalance;
use App\Models\ResellerBalanceRequest;
use App\Models\ResellerLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResellerBalanceService
{
    public function ensureBalanceRow(User $reseller): ResellerBalance
    {
        return ResellerBalance::firstOrCreate(
            ['user_id' => $reseller->id],
            ['balance_inr' => 0]
        );
    }

    public function balance(User $reseller): float
    {
        return round((float) $this->ensureBalanceRow($reseller)->balance_inr, 2);
    }

    public function credit(
        User $reseller,
        float $amount,
        string $type,
        array $meta = [],
    ): ResellerLedger {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($reseller, $amount, $type, $meta) {
            $row = ResellerBalance::query()->where('user_id', $reseller->id)->lockForUpdate()->first();
            if (! $row) {
                $row = ResellerBalance::create(['user_id' => $reseller->id, 'balance_inr' => 0]);
                $row = ResellerBalance::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            }

            $newBalance = round((float) $row->balance_inr + $amount, 2);
            $row->update(['balance_inr' => $newBalance]);

            return ResellerLedger::create([
                'reseller_user_id' => $reseller->id,
                'type' => $type,
                'amount_inr' => $amount,
                'balance_after' => $newBalance,
                'meta' => $meta,
            ]);
        });
    }

    public function debit(
        User $reseller,
        float $amount,
        string $type,
        array $meta = [],
    ): ResellerLedger {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($reseller, $amount, $type, $meta) {
            $row = ResellerBalance::query()->where('user_id', $reseller->id)->lockForUpdate()->first();
            if (! $row) {
                throw new RuntimeException('Insufficient balance.');
            }

            if ((float) $row->balance_inr < $amount) {
                throw new RuntimeException('Insufficient balance.');
            }

            $newBalance = round((float) $row->balance_inr - $amount, 2);
            $row->update(['balance_inr' => $newBalance]);

            return ResellerLedger::create([
                'reseller_user_id' => $reseller->id,
                'type' => $type,
                'amount_inr' => -$amount,
                'balance_after' => $newBalance,
                'meta' => $meta,
            ]);
        });
    }

    public function requestTopUp(User $reseller, float $amount, ?string $note = null): ResellerBalanceRequest
    {
        $amount = round($amount, 2);
        if ($amount < 1) {
            throw new RuntimeException('Minimum request amount is ₹1.');
        }

        $pending = ResellerBalanceRequest::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('status', ResellerBalanceRequest::STATUS_PENDING)
            ->exists();

        if ($pending) {
            throw new RuntimeException('You already have a pending balance request.');
        }

        return ResellerBalanceRequest::create([
            'reseller_user_id' => $reseller->id,
            'amount_inr' => $amount,
            'status' => ResellerBalanceRequest::STATUS_PENDING,
            'note' => $note,
        ]);
    }

    public function approveRequest(ResellerBalanceRequest $request, User $admin, ?string $adminNote = null): ResellerBalanceRequest
    {
        if (! $request->isPending()) {
            throw new RuntimeException('This request is already reviewed.');
        }

        return DB::transaction(function () use ($request, $admin, $adminNote) {
            $locked = ResellerBalanceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ResellerBalanceRequest::STATUS_PENDING) {
                throw new RuntimeException('This request is already reviewed.');
            }

            $reseller = User::findOrFail($locked->reseller_user_id);
            $this->credit($reseller, (float) $locked->amount_inr, ResellerLedger::TYPE_CREDIT_REQUEST, [
                'request_id' => $locked->id,
                'admin_id' => $admin->id,
                'note' => $adminNote,
            ]);

            $locked->update([
                'status' => ResellerBalanceRequest::STATUS_APPROVED,
                'admin_note' => $adminNote,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    public function rejectRequest(ResellerBalanceRequest $request, User $admin, ?string $adminNote = null): ResellerBalanceRequest
    {
        if (! $request->isPending()) {
            throw new RuntimeException('This request is already reviewed.');
        }

        $request->update([
            'status' => ResellerBalanceRequest::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }
}
