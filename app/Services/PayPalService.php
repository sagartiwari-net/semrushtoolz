<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PayPalBillingPlan;
use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    public function config(): array
    {
        return SiteSetting::paypalConfig();
    }

    public function isConfigured(): bool
    {
        $c = $this->config();

        return $c['enabled']
            && filled($c['client_id'])
            && filled($c['client_secret']);
    }

    public function baseUrl(): string
    {
        return $this->config()['mode'] === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function sdkUrl(): string
    {
        $clientId = $this->config()['client_id'];

        return "https://www.paypal.com/sdk/js?client-id={$clientId}&vault=true&intent=subscription&currency=USD";
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'PayPal is not enabled or credentials are missing.'];
        }

        Cache::forget($this->tokenCacheKey());

        try {
            $this->accessToken();

            return [
                'ok' => true,
                'message' => 'Connected to PayPal ('.$this->config()['mode'].').',
                'mode' => $this->config()['mode'],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public static function clearTokenCache(): void
    {
        Cache::forget('paypal_access_token_'.md5(SiteSetting::paypalConfig()['client_id'].SiteSetting::paypalConfig()['mode']));
    }

    protected function tokenCacheKey(): string
    {
        $c = $this->config();

        return 'paypal_access_token_'.md5($c['client_id'].$c['mode']);
    }

    public function accessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), 3000, function () {
            $c = $this->config();
            $response = Http::withBasicAuth($c['client_id'], $c['client_secret'])
                ->asForm()
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('PayPal authentication failed: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson();
    }

    public function productId(): string
    {
        $stored = $this->config()['product_id'];
        if ($stored) {
            return $stored;
        }

        $response = $this->client()->post('/v1/catalogs/products', [
            'name' => config('app.name', 'Semrushtoolz'),
            'description' => 'Group buy SEO tools subscription',
            'type' => 'SERVICE',
            'category' => 'SOFTWARE',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal product creation failed: '.$response->body());
        }

        $productId = (string) $response->json('id');
        SiteSetting::set('paypal_product_id', $productId);

        return $productId;
    }

    public function billingPlanForOrder(Order $order): string
    {
        $order->loadMissing(['plan', 'tool']);

        $entityType = $order->tool_id ? 'tool' : 'plan';
        $entityId = $order->tool_id ?? $order->plan_id;
        $monthlyUsd = $this->monthlyUsdForOrder($order);
        $totalCycles = $this->totalCyclesForOrder($order);

        $cached = PayPalBillingPlan::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('duration_months', $order->duration_months)
            ->where('amount_usd', $monthlyUsd)
            ->where('total_cycles', $totalCycles)
            ->first();

        if ($cached) {
            return $cached->paypal_plan_id;
        }

        $name = $order->purchasedItemName().' — '.$order->duration_months.' month(s)';
        $planId = $this->createBillingPlan($name, $monthlyUsd, $totalCycles);

        PayPalBillingPlan::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'duration_months' => $order->duration_months,
            'amount_usd' => $monthlyUsd,
            'total_cycles' => $totalCycles,
            'paypal_plan_id' => $planId,
        ]);

        return $planId;
    }

    public function monthlyUsdForOrder(Order $order): float
    {
        $months = max(1, (int) $order->duration_months);

        return round((float) $order->total / $months, 2);
    }

    public function totalCyclesForOrder(Order $order): ?int
    {
        return $order->duration_months > 1 ? (int) $order->duration_months : null;
    }

    public function createBillingPlan(string $name, float $amountUsd, ?int $totalCycles = null): string
    {
        $cycle = [
            'frequency' => [
                'interval_unit' => 'MONTH',
                'interval_count' => 1,
            ],
            'tenure_type' => 'REGULAR',
            'sequence' => 1,
            'pricing_scheme' => [
                'fixed_price' => [
                    'value' => number_format($amountUsd, 2, '.', ''),
                    'currency_code' => 'USD',
                ],
            ],
        ];

        if ($totalCycles) {
            $cycle['total_cycles'] = $totalCycles;
        }

        $response = $this->client()->post('/v1/billing/plans', [
            'product_id' => $this->productId(),
            'name' => $name,
            'description' => $name,
            'billing_cycles' => [$cycle],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => 2,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal billing plan creation failed: '.$response->body());
        }

        $planId = (string) $response->json('id');

        $activate = $this->client()->post("/v1/billing/plans/{$planId}/activate");
        if (! $activate->successful()) {
            throw new RuntimeException('PayPal billing plan activation failed: '.$activate->body());
        }

        return $planId;
    }

    public function getSubscription(string $subscriptionId): array
    {
        $response = $this->client()->get("/v1/billing/subscriptions/{$subscriptionId}");

        if (! $response->successful()) {
            throw new RuntimeException('PayPal subscription lookup failed: '.$response->body());
        }

        return $response->json();
    }

    public function cancelSubscription(string $subscriptionId, string $reason = 'Cancelled by user'): void
    {
        $response = $this->client()->post("/v1/billing/subscriptions/{$subscriptionId}/cancel", [
            'reason' => $reason,
        ]);

        if (! $response->successful() && $response->status() !== 422) {
            throw new RuntimeException('PayPal subscription cancel failed: '.$response->body());
        }
    }

    public function verifyWebhook(array $headers, string $body): bool
    {
        $webhookId = $this->config()['webhook_id'];
        if (! $webhookId) {
            return app()->environment('local');
        }

        $response = $this->client()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $headers['paypal-auth-algo'][0] ?? $headers['PAYPAL-AUTH-ALGO'][0] ?? '',
            'cert_url' => $headers['paypal-cert-url'][0] ?? $headers['PAYPAL-CERT-URL'][0] ?? '',
            'transmission_id' => $headers['paypal-transmission-id'][0] ?? $headers['PAYPAL-TRANSMISSION-ID'][0] ?? '',
            'transmission_sig' => $headers['paypal-transmission-sig'][0] ?? $headers['PAYPAL-TRANSMISSION-SIG'][0] ?? '',
            'transmission_time' => $headers['paypal-transmission-time'][0] ?? $headers['PAYPAL-TRANSMISSION-TIME'][0] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true),
        ]);

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }

    public function prepareOrderForPayment(Order $order): Order
    {
        if ($order->payment_method !== 'paypal') {
            return $order;
        }

        if (! $order->paypal_billing_plan_id) {
            $planId = $this->billingPlanForOrder($order);
            $order->update([
                'paypal_billing_plan_id' => $planId,
                'is_recurring' => true,
                'currency' => 'usd',
            ]);
        }

        return $order->fresh();
    }

    public function subscriptionMatchesOrder(array $subscription, Order $order): bool
    {
        $planId = $subscription['plan_id'] ?? null;

        return $planId && $planId === $order->paypal_billing_plan_id;
    }
}
