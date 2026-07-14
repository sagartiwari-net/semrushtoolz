<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPurgeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminAccountDeletionService
{
    public function __construct(
        protected UserSessionService $sessions,
    ) {}

    /**
     * Permanently delete a customer account (role=user).
     * Cascades subscriptions, orders, logs, and related rows via DB FKs.
     */
    public function deleteUser(User $user, User $actor): void
    {
        if ((int) $user->id === (int) $actor->id) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        if ($user->role !== 'user') {
            throw new RuntimeException('Only customer accounts can be deleted from Users. Use Resellers to delete a reseller.');
        }

        $this->deleteAccount($user, 'admin_manual_user', $actor);
    }

    /**
     * Permanently delete a reseller account.
     * End-users they created stay; created_by_reseller_id becomes null.
     * Reseller ledger/provisions/prices/requests cascade-delete with the account.
     */
    public function deleteReseller(User $reseller, User $actor): void
    {
        if ((int) $reseller->id === (int) $actor->id) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        if (! $reseller->isReseller()) {
            throw new RuntimeException('This account is not a reseller.');
        }

        $this->deleteAccount($reseller, 'admin_manual_reseller', $actor);
    }

    private function deleteAccount(User $user, string $reason, User $actor): void
    {
        DB::transaction(function () use ($user, $reason, $actor) {
            $this->sessions->killAllSessions($user->id);

            DB::table('sessions')->where('user_id', $user->id)->delete();

            if (filled($user->avatar_path)) {
                try {
                    Storage::disk('public')->delete($user->avatar_path);
                } catch (\Throwable) {
                    // ignore missing file
                }
            }

            UserPurgeLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'reason' => $reason,
                'triggered_by' => 'admin:'.$actor->id,
                'purged_at' => now(),
            ]);

            $user->delete();
        });
    }
}
