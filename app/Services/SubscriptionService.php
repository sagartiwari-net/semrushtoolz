<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tool;
use App\Models\User;

class SubscriptionService
{
    public function activeSubscriptions(User $user)
    {
        return $user->subscriptions()
            ->with(['plan.tools', 'tool'])
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->get();
    }

    public function activeSubscription(User $user): ?Subscription
    {
        return $this->activeSubscriptions($user)->first();
    }

    public function subscriptionLabels(User $user): array
    {
        return $this->activeSubscriptions($user)
            ->map(fn (Subscription $s) => $s->plan?->name ?? $s->tool?->name)
            ->filter()
            ->values()
            ->all();
    }

    public function displayPlanName(User $user): string
    {
        $labels = $this->subscriptionLabels($user);

        if ($labels === []) {
            return 'No Plan';
        }

        return count($labels) === 1 ? $labels[0] : implode(' + ', $labels);
    }

    public function grantedToolSlugsForUser(User $user): array
    {
        $slugs = [];

        foreach ($this->activeSubscriptions($user) as $sub) {
            $slugs = array_merge($slugs, $this->grantedToolSlugs($sub));
        }

        return array_values(array_unique($slugs));
    }

    public function grantedToolSlugs(?Subscription $sub): array
    {
        if (! $sub) {
            return [];
        }

        if ($sub->plan_id && $sub->plan) {
            return $sub->plan->tools->pluck('slug')->all();
        }

        if ($sub->tool_id && $sub->tool) {
            return [$sub->tool->grantSlug()];
        }

        return [];
    }

    public function accessibleTools(User $user): array
    {
        $grantedSlugs = $this->grantedToolSlugsForUser($user);

        return Tool::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Tool $tool) use ($grantedSlugs, $user) {
                $toolAccess = app(ToolAccessService::class);

                return [
                    'id' => $tool->slug,
                    'name' => $tool->name,
                    'desc' => $tool->description ?? '',
                    'logo' => $tool->logo_url,
                    'status' => $tool->access_type,
                    'access_type' => $tool->access_type,
                    'active' => in_array($tool->slug, $grantedSlugs),
                    'featured' => $tool->slug === 'semrush',
                    'is_extension' => $tool->access_type === Tool::ACCESS_EXTENSION,
                    'seats' => $tool->isCloud() ? ($toolAccess->seatLabel($tool->slug) ?? '—') : null,
                    'last_accessed' => $toolAccess->lastAccessed($user, $tool->slug),
                    'session' => null,
                ];
            })
            ->all();
    }

    public function activateFromOrder(Order $order): Subscription
    {
        $order->load(['plan', 'tool']);
        $startsAt = now();
        $isPayPal = $order->payment_method === 'paypal';

        if ($order->duration_days) {
            $endsAt = now()->addDays($order->duration_days);
        } elseif ($isPayPal) {
            $endsAt = now()->addMonth();
        } else {
            $endsAt = now()->addMonths($order->duration_months);
        }

        $user = $order->user;

        if ($isPayPal && $order->paypal_subscription_id) {
            $existing = $user->subscriptions()
                ->where('paypal_subscription_id', $order->paypal_subscription_id)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                return $this->renewFromPayPal($existing, (float) $order->total);
            }
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $order->plan_id,
            'tool_id' => $order->tool_id,
            'status' => 'active',
            'duration_months' => $order->duration_months,
            'duration_days' => $order->duration_days,
            'currency' => $order->currency,
            'amount_paid' => $order->total,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'auto_renew' => $isPayPal,
            'paypal_subscription_id' => $isPayPal ? $order->paypal_subscription_id : null,
            'next_billing_at' => $isPayPal ? $endsAt : null,
        ]);
    }

    public function renewFromPayPal(Subscription $subscription, float $amountPaid): Subscription
    {
        $base = $subscription->ends_at->isFuture() ? $subscription->ends_at : now();
        $endsAt = $base->copy()->addMonth();

        $subscription->update([
            'status' => 'active',
            'auto_renew' => true,
            'amount_paid' => $amountPaid,
            'ends_at' => $endsAt,
            'next_billing_at' => $endsAt,
        ]);

        return $subscription->fresh();
    }
}
