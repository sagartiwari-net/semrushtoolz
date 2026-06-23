<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PayPalWebhookEvent;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalWebhookService
{
    public function __construct(
        protected PayPalService $paypal,
        protected OrderService $orders,
        protected SubscriptionService $subscriptions,
    ) {}

    public function handle(array $event): void
    {
        $eventId = $event['id'] ?? null;
        if (! $eventId) {
            return;
        }

        if (PayPalWebhookEvent::where('event_id', $eventId)->whereNotNull('processed_at')->exists()) {
            return;
        }

        $record = PayPalWebhookEvent::firstOrCreate(
            ['event_id' => $eventId],
            [
                'event_type' => $event['event_type'] ?? 'unknown',
                'resource_id' => data_get($event, 'resource.id'),
                'payload' => $event,
            ]
        );

        try {
            match ($event['event_type'] ?? '') {
                'BILLING.SUBSCRIPTION.ACTIVATED' => $this->handleActivated($event),
                'BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED',
                'PAYMENT.SALE.COMPLETED' => $this->handlePaymentSucceeded($event),
                'BILLING.SUBSCRIPTION.CANCELLED',
                'BILLING.SUBSCRIPTION.EXPIRED' => $this->handleEnded($event, 'cancelled'),
                'BILLING.SUBSCRIPTION.SUSPENDED' => $this->handleEnded($event, 'suspended'),
                default => null,
            };

            $record->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('PayPal webhook processing failed', [
                'event_id' => $eventId,
                'type' => $event['event_type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function handleActivated(array $event): void
    {
        $subscriptionId = data_get($event, 'resource.id');
        if (! $subscriptionId) {
            return;
        }

        $order = $this->findPendingOrder($subscriptionId, $event);
        if (! $order || $order->status === 'completed') {
            return;
        }

        $order->update(['paypal_subscription_id' => $subscriptionId]);

        if ($order->status !== 'completed') {
            $this->orders->completeOrder($order);
        }
    }

    protected function findPendingOrder(string $subscriptionId, array $event): ?Order
    {
        $order = Order::where('paypal_subscription_id', $subscriptionId)
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->latest()
            ->first();

        if ($order) {
            return $order;
        }

        $customId = data_get($event, 'resource.custom_id')
            ?? data_get($event, 'resource.subscriber.payer_id');

        if ($customId) {
            $byNumber = Order::where('order_number', $customId)
                ->whereIn('status', ['pending', 'awaiting_payment'])
                ->first();

            if ($byNumber) {
                return $byNumber;
            }
        }

        return null;
    }

    protected function handlePaymentSucceeded(array $event): void
    {
        $subscriptionId = data_get($event, 'resource.billing_agreement_id')
            ?? data_get($event, 'resource.id')
            ?? data_get($event, 'resource.supplementary_data.related_ids.subscription_id');

        if (! $subscriptionId) {
            return;
        }

        $subscription = Subscription::where('paypal_subscription_id', $subscriptionId)->first();

        if (! $subscription) {
            return;
        }

        // Initial payment is handled during order approval — skip duplicate extension.
        if ($subscription->ends_at->gt(now()->addDays(15))) {
            return;
        }

        $amount = (float) data_get($event, 'resource.amount.total', data_get($event, 'resource.amount.value', 0));

        $this->subscriptions->renewFromPayPal($subscription, $amount);
    }

    protected function handleEnded(array $event, string $status): void
    {
        $subscriptionId = data_get($event, 'resource.id');
        if (! $subscriptionId) {
            return;
        }

        Subscription::where('paypal_subscription_id', $subscriptionId)
            ->where('status', 'active')
            ->update([
                'status' => $status,
                'auto_renew' => false,
                'next_billing_at' => null,
            ]);
    }
}
