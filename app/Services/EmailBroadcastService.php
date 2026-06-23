<?php

namespace App\Services;

use App\Models\EmailBroadcastQueue;
use App\Models\EmailPreset;
use App\Models\User;
use Illuminate\Support\Str;

class EmailBroadcastService
{
    public function __construct(
        protected TransactionalEmailService $mail,
        protected AffiliateService $affiliates,
    ) {}

    public function startCampaign(string $audience): string
    {
        $campaignId = (string) Str::uuid();
        $rows = [];

        foreach ($this->recipientsForAudience($audience) as $userId => $presetKey) {
            $rows[] = [
                'campaign_id' => $campaignId,
                'user_id' => $userId,
                'preset_key' => $presetKey,
                'status' => EmailBroadcastQueue::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            EmailBroadcastQueue::query()->insert($chunk);
        }

        return $campaignId;
    }

    public function pendingCount(?string $campaignId = null): int
    {
        $query = EmailBroadcastQueue::query()->where('status', EmailBroadcastQueue::STATUS_PENDING);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        return $query->count();
    }

    public function processBatch(int $batchSize = 8): array
    {
        $items = EmailBroadcastQueue::query()
            ->with('user')
            ->where('status', EmailBroadcastQueue::STATUS_PENDING)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($items as $item) {
            $user = $item->user;

            if (! $user || $user->isAdmin() || $user->isBlocked()) {
                $item->update(['status' => EmailBroadcastQueue::STATUS_FAILED, 'sent_at' => now()]);
                $failed++;

                continue;
            }

            try {
                $this->mail->sendAffiliatePromoEmail($user, $item->preset_key);
                $item->update(['status' => EmailBroadcastQueue::STATUS_SENT, 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable) {
                $item->update(['status' => EmailBroadcastQueue::STATUS_FAILED, 'sent_at' => now()]);
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => $this->pendingCount(),
        ];
    }

    /**
     * @return array<int, string> user_id => preset_key
     */
    protected function recipientsForAudience(string $audience): array
    {
        $affiliateIds = $this->affiliateUserIds();
        $recipients = [];

        $baseQuery = User::query()
            ->whereNotIn('role', ['admin', 'super_admin'])
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'blocked'));

        if (in_array($audience, ['affiliates', 'both'], true)) {
            foreach ($baseQuery->clone()->whereIn('id', $affiliateIds)->pluck('id') as $userId) {
                $recipients[$userId] = EmailPreset::KEY_AFFILIATE_PROGRAM_BOOST;
            }
        }

        if (in_array($audience, ['non_affiliates', 'both'], true)) {
            foreach ($baseQuery->clone()->whereNotIn('id', $affiliateIds)->pluck('id') as $userId) {
                $recipients[$userId] = EmailPreset::KEY_AFFILIATE_PROGRAM_INVITE;
            }
        }

        if ($audience === 'all') {
            foreach ($baseQuery->clone()->pluck('id') as $userId) {
                $recipients[$userId] = in_array($userId, $affiliateIds, true)
                    ? EmailPreset::KEY_AFFILIATE_PROGRAM_BOOST
                    : EmailPreset::KEY_AFFILIATE_PROGRAM_INVITE;
            }
        }

        return $recipients;
    }

    protected function affiliateUserIds(): array
    {
        return User::query()
            ->whereIn('id', function ($query) {
                $query->select('referrer_user_id')->from('affiliate_commissions')->distinct();
            })
            ->pluck('id')
            ->all();
    }
}
