<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BuyahrefPaymentService
{
    protected function config(): array
    {
        return SiteSetting::buyahrefConfig();
    }

    public function isConfigured(): bool
    {
        $c = $this->config();

        return $c['enabled']
            && filled($c['api_key'])
            && filled($c['api_secret']);
    }

    public function orderExpiryMinutes(): int
    {
        return $this->config()['order_expiry_minutes'];
    }

    public function hubUrl(): string
    {
        return rtrim($this->config()['hub_url'], '/');
    }

    public function createPaymentForOrder(Order $order): Order
    {
        $order->loadMissing(['user', 'plan', 'tool']);

        $payload = [
            'order_id' => $order->order_number,
            'amount' => (float) $order->total,
            'currency' => 'INR',
            'customer' => [
                'email' => $order->user->email,
                'name' => trim($order->user->name ?? '') ?: $order->user->email,
            ],
            'product' => [
                'name' => $order->purchasedItemName(),
            ],
            'return_url' => route('dashboard.orders.payment.return', $order),
            'webhook_url' => route('webhooks.buyahref'),
        ];

        $response = $this->request('POST', '/api/v1/orders/create', $payload);

        $data = $response['data'] ?? [];

        $order->update([
            'hub_order_id' => $data['hub_order_id'] ?? null,
            'hub_payment_url' => $data['payment_url'] ?? null,
        ]);

        return $order->fresh();
    }

    public function verify(string $merchantOrderId): array
    {
        $path = '/api/v1/orders/'.rawurlencode($merchantOrderId).'/verify';

        try {
            $response = $this->request('GET', $path);

            return $response['data'] ?? [];
        } catch (RuntimeException $e) {
            Log::warning('Buyahref verify failed', [
                'order_id' => $merchantOrderId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function verifyWebhookSignature(Request $request, string $rawBody): bool
    {
        $timestamp = (string) $request->header('X-Hub-Timestamp', '');
        $signature = (string) $request->header('X-Hub-Signature', '');

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        return hash_equals(
            $this->sign($timestamp, 'POST', $path, $rawBody),
            $signature
        );
    }

    protected function request(string $method, string $path, ?array $payload = null): array
    {
        $c = $this->config();
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $timestamp = (string) time();
        $signature = $this->sign($timestamp, strtoupper($method), $path, $body, $c['api_secret']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Merchant-Key' => $c['api_key'],
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ])->withBody($body, 'application/json')
            ->send($method, $this->hubUrl().$path);

        $decoded = $response->json();

        if (! $response->successful() || ! is_array($decoded) || empty($decoded['success'])) {
            $error = is_array($decoded) && ! empty($decoded['error'])
                ? $decoded['error']
                : 'HTTP '.$response->status();

            throw new RuntimeException('Payment Hub API error: '.$error);
        }

        return $decoded;
    }

    protected function sign(string $timestamp, string $method, string $path, string $body, string $secret): string
    {
        $message = $timestamp.'|'.$method.'|'.$path.'|'.$body;

        return hash_hmac('sha256', $message, $secret);
    }
}
