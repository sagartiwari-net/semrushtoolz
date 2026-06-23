<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\User;

class DashboardPresenter
{
    public function __construct(
        protected SubscriptionService $subscriptions
    ) {}

    public function userContext(User $user): array
    {
        $user->loadMissing('referrer');
        $subs = $this->subscriptions->activeSubscriptions($user);
        $sub = $subs->first();
        $planName = $this->subscriptions->displayPlanName($user);
        $walletConfig = SiteSetting::walletConfig();

        $initials = collect(explode(' ', $user->name))
            ->filter()
            ->take(2)
            ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
            ->implode('') ?: 'U';

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'initials' => $initials,
            'avatar_url' => $user->avatarUrl(),
            'email_verified' => $user->hasVerifiedEmail(),
            'member_since' => $user->created_at?->format('M Y') ?? '—',
            'role_label' => $subs->isNotEmpty() ? $planName : 'Free Account',
            'plan' => $planName,
            'plan_status' => $subs->isNotEmpty() ? 'Active' : 'No Subscription',
            'expires_at' => $sub?->ends_at?->format('M d, Y') ?? '—',
            'days_remaining' => $sub?->daysRemaining() ?? 0,
            'referral_code' => $user->referral_code ?? '',
            'referred_by_name' => $user->referrer?->name,
            'referred_by_code' => $user->referrer?->referral_code,
            'phone' => $user->phone ?? '',
            'wallet_enabled' => $walletConfig['enabled'],
            'wallet_balance' => round((float) $user->wallet_balance, 2),
            'wallet_balance_label' => '₹'.number_format((float) $user->wallet_balance, (float) $user->wallet_balance < 100 ? 2 : 0),
        ];
    }

    public function notifications(User $user): array
    {
        $notifs = [];
        $subs = $this->subscriptions->activeSubscriptions($user);

        if ($subs->isEmpty()) {
            $notifs[] = ['type' => 'info', 'title' => 'Subscribe to a plan to access tools', 'time' => 'Now', 'unread' => true];
        } else {
            foreach ($subs as $sub) {
                if ($sub->daysRemaining() <= 7) {
                    $name = $sub->plan?->name ?? $sub->tool?->name ?? 'Subscription';
                    $notifs[] = ['type' => 'warn', 'title' => "{$name} expires in {$sub->daysRemaining()} days", 'time' => 'Today', 'unread' => true];
                }
            }
        }

        return $notifs;
    }
}
