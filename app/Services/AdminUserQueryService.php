<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

class AdminUserQueryService
{
    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('joined_from')) {
            $query->whereDate('created_at', '>=', $request->joined_from);
        }

        if ($request->filled('joined_to')) {
            $query->whereDate('created_at', '<=', $request->joined_to);
        }

        $planId = $request->integer('plan_id') ?: null;
        $toolId = $request->integer('tool_id') ?: null;
        $subscription = $request->input('subscription', 'all');
        $expiresFrom = $request->input('expires_from');
        $expiresTo = $request->input('expires_to');

        if ($planId || $toolId || in_array($subscription, ['active', 'expired', 'none'], true) || $expiresFrom || $expiresTo) {
            $query->where(function ($userQuery) use ($planId, $toolId, $subscription, $expiresFrom, $expiresTo) {
                if ($subscription === 'none') {
                    $userQuery->whereDoesntHave('subscriptions', function ($s) {
                        $this->scopeActive($s);
                    });

                    return;
                }

                $userQuery->whereHas('subscriptions', function ($sub) use ($planId, $toolId, $subscription, $expiresFrom, $expiresTo) {
                    if ($planId) {
                        $sub->where('plan_id', $planId);
                    }

                    if ($toolId) {
                        $sub->where(function ($inner) use ($toolId) {
                            $inner->where('tool_id', $toolId)
                                ->orWhereHas('plan.tools', fn ($t) => $t->where('tools.id', $toolId));
                        });
                    }

                    if ($expiresFrom) {
                        $sub->whereDate('ends_at', '>=', $expiresFrom);
                    }

                    if ($expiresTo) {
                        $sub->whereDate('ends_at', '<=', $expiresTo);
                    }

                    match ($subscription) {
                        'active' => $this->scopeActive($sub),
                        'expired' => $this->scopeExpired($sub),
                        default => null,
                    };
                });
            });
        }

        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere('status', 'cancelled')
                ->orWhere(function ($inner) {
                    $inner->where('status', 'active')->where('ends_at', '<=', now());
                });
        });
    }

    /** @return array<int, array{active: int, expired: int, total: int}> */
    public function planSubscriberCounts(): array
    {
        return Cache::remember('admin.plan_subscriber_counts', now()->addMinutes(5), function () {
            $plans = Plan::pluck('id');
            $counts = [];

            foreach ($plans as $planId) {
                $base = Subscription::where('plan_id', $planId);
                $counts[$planId] = [
                    'active' => (clone $base)->where(function ($q) {
                        $this->scopeActive($q);
                    })->distinct('user_id')->count('user_id'),
                    'expired' => (clone $base)->where(function ($q) {
                        $this->scopeExpired($q);
                    })->distinct('user_id')->count('user_id'),
                    'total' => (clone $base)->distinct('user_id')->count('user_id'),
                ];
            }

            return $counts;
        });
    }

    /** @return array<int, array{active: int, expired: int, total: int}> */
    public function toolSubscriberCounts(): array
    {
        return Cache::remember('admin.tool_subscriber_counts', now()->addMinutes(5), function () {
            $tools = Tool::pluck('id');
            $counts = [];

            foreach ($tools as $toolId) {
                $counts[$toolId] = [
                    'active' => $this->countUsersForTool($toolId, 'active'),
                    'expired' => $this->countUsersForTool($toolId, 'expired'),
                    'total' => $this->countUsersForTool($toolId, 'all'),
                ];
            }

            return $counts;
        });
    }

    protected function countUsersForTool(int $toolId, string $mode): int
    {
        $query = User::query()->whereHas('subscriptions', function ($sub) use ($toolId, $mode) {
            $sub->where(function ($inner) use ($toolId) {
                $inner->where('tool_id', $toolId)
                    ->orWhereHas('plan.tools', fn ($t) => $t->where('tools.id', $toolId));
            });

            if ($mode === 'active') {
                $this->scopeActive($sub);
            } elseif ($mode === 'expired') {
                $this->scopeExpired($sub);
            }
        });

        return $query->count();
    }

    public function unverifiedStats(): array
    {
        $cutoff = now()->subDays((int) config('security.purge_unverified_days', 7));

        return [
            'total' => User::whereNull('email_verified_at')->where('role', 'user')->whereNull('created_by_reseller_id')->count(),
            'eligible_for_purge' => User::whereNull('email_verified_at')
                ->where('role', 'user')
                ->whereNull('created_by_reseller_id')
                ->where('created_at', '<=', $cutoff)
                ->whereDoesntHave('subscriptions', fn ($s) => $this->scopeActive($s))
                ->whereDoesntHave('orders', fn ($o) => $o->where('status', 'completed'))
                ->count(),
            'purge_after_days' => (int) config('security.purge_unverified_days', 7),
        ];
    }

    public function filterOptions(): array
    {
        return [
            'plans' => Plan::orderBy('sort_order')->get(['id', 'name']),
            'tools' => Tool::orderBy('sort_order')->get(['id', 'name', 'slug']),
        ];
    }

    public function presentUser(User $user): array
    {
        $activeSub = $user->subscriptions->first(
            fn ($s) => $s->status === 'active' && $s->ends_at?->isFuture()
        );

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'plan' => $activeSub?->plan?->name ?? $activeSub?->tool?->name ?? '—',
            'subscription_status' => $activeSub ? 'Active' : ($user->subscriptions->isNotEmpty() ? 'Expired' : 'None'),
            'status' => ucfirst($user->status),
            'verified' => (bool) $user->email_verified_at,
            'joined' => $user->created_at->format('M d, Y'),
            'alerts' => $user->security_alert_count,
        ];
    }

    public function presentUnverifiedUser(User $user): array
    {
        $cutoff = now()->subDays((int) config('security.purge_unverified_days', 7));
        $eligibleForPurge = $user->created_at <= $cutoff
            && ! $user->subscriptions->contains(fn ($s) => $s->status === 'active' && $s->ends_at?->isFuture())
            && ! $user->orders->contains(fn ($o) => $o->status === 'completed');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'joined' => $user->created_at->format('M d, Y'),
            'days_old' => (int) $user->created_at->diffInDays(now()),
            'eligible_for_purge' => $eligibleForPurge,
            'status' => ucfirst($user->status),
        ];
    }
}
