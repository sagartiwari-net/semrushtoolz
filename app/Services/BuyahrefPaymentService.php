<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
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
        $config = $this->config();
        $internal = rtrim((string) ($config['hub_internal_url'] ?? ''), '/');

        if (filled($internal)) {
            return $internal;
        }

        return rtrim($config['hub_url'], '/');
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'UPI is not enabled or Buyahref credentials are missing.'];
        }

        try {
            $health = Http::timeout(10)->get($this->hubUrl().'/health');

            if (! $health->successful()) {
                return [
                    'ok' => false,
                    'message' => 'Payment Hub unreachable at '.$this->hubUrl().' (HTTP '.$health->status().').',
                ];
            }
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Cannot reach Payment Hub at '.$this->hubUrl().': '.$exception->getMessage(),
            ];
        }

        try {
            $this->request('GET', '/api/v1/orders/'.rawurlencode('__connection_test__').'/verify');

            return [
                'ok' => true,
                'message' => 'Connected to Buyahref Payment Hub at '.$this->hubUrl().'.',
            ];
        } catch (RuntimeException $exception) {
            $message = strtolower($exception->getMessage());

            if (str_contains($message, 'order not found')) {
                return [
                    'ok' => true,
                    'message' => 'Payment Hub credentials OK at '.$this->hubUrl().'.',
                ];
            }

            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    public function ensurePaymentUrlForOrder(Order $order): Order
    {
        $order->loadMissing(['user', 'plan', 'tool']);

        if (filled($order->hub_payment_url)) {
            return $order;
        }

        if (filled($order->hub_order_id)) {
            $url = $this->paymentUrlFromVerify($order->order_number);

            if ($url) {
                $order->update(['hub_payment_url' => $url]);

                return $order->fresh();
            }
        }

        try {
            return $this->createPaymentForOrder($order);
        } catch (RuntimeException $e) {
            if ($this->isDuplicateOrderError($e)) {
                $url = $this->paymentUrlFromVerify($order->order_number);

                if ($url) {
                    $order->update(['hub_payment_url' => $url]);

                    return $order->fresh();
                }
            }

            throw $e;
        }
    }

    public function createPaymentForOrder(Order $order): Order
    {
        $order->loadMissing(['user', 'plan', 'tool']);

        $payload = [
            'order_id' => $order->order_number,
            'amount' => round((float) $order->total, 2),
            'currency' => 'INR',
            'customer' => [
                'email' => $order->user->email,
                'name' => trim($order->user->name ?? '') ?: $order->user->email,
            ],
            'product' => [
                'name' => $order->purchasedItemName(),
            ],
            'return_url' => $this->absoluteRoute('dashboard.orders.payment.return', $order),
            'webhook_url' => $this->absoluteRoute('webhooks.buyahref'),
        ];

        $response = $this->request('POST', '/api/v1/orders/create', $payload);
        $data = $response['data'] ?? [];

        $paymentUrl = $this->extractPaymentUrl($data)
            ?? $this->paymentUrlFromVerify($order->order_number);

        if (! filled($paymentUrl)) {
            throw new RuntimeException('Payment Hub did not return a checkout URL.');
        }

        $order->update([
            'hub_order_id' => $data['hub_order_id'] ?? $order->hub_order_id,
            'hub_payment_url' => $paymentUrl,
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

    public function userFacingError(RuntimeException $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'invalid merchant key') || str_contains($message, 'invalid signature')) {
            return 'UPI payment credentials are invalid. Admin should re-save API key and secret in Payment Integration.';
        }

        if (str_contains($message, 'missing authentication')) {
            return 'UPI payment is not configured correctly. Please contact support.';
        }

        if (str_contains($message, 'checkout url')) {
            return 'Could not open the UPI checkout page. Please try again or contact support.';
        }

        return 'Could not start UPI payment. Please try again or contact support.';
    }

    protected function paymentUrlFromVerify(string $merchantOrderId): ?string
    {
        return $this->extractPaymentUrl($this->verify($merchantOrderId));
    }

    protected function extractPaymentUrl(array $data): ?string
    {
        foreach (['payment_url', 'checkout_url', 'pay_url', 'url'] as $key) {
            if (filled($data[$key] ?? null)) {
                return (string) $data[$key];
            }
        }

        return null;
    }

    protected function isDuplicateOrderError(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'already exists')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'already created');
    }

    protected function absoluteRoute(string $name, mixed $parameters = []): string
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        return route($name, $parameters, true);
    }

    protected function request(string $method, string $path, ?array $payload = null): array
    {
        $c = $this->config();
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $timestamp = (string) time();
        $signature = $this->sign($timestamp, strtoupper($method), $path, $body, $c['api_secret']);

        $response = Http::withHeaders([
            'X-Merchant-Key' => $c['api_key'],
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->connectTimeout(10);

        $url = $this->hubUrl().$path;

        $response = match (strtoupper($method)) {
            'GET' => $response->get($url),
            'DELETE' => $response->delete($url),
            default => $response
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody($body, 'application/json')
                ->post($url),
        };

        $decoded = $response->json();

        if (! $response->successful()) {
            $error = is_array($decoded) && filled($decoded['error'] ?? null)
                ? (string) $decoded['error']
                : 'HTTP '.$response->status();

            Log::warning('Buyahref API request failed', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'error' => $error,
                'body' => is_string($response->body()) ? mb_substr($response->body(), 0, 500) : null,
            ]);

            throw new RuntimeException('Payment Hub API error: '.$error);
        }

        if (! is_array($decoded)) {
            $snippet = mb_substr(trim((string) $response->body()), 0, 200);

            Log::warning('Buyahref API returned non-JSON body', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $snippet,
            ]);

            throw new RuntimeException(
                'Payment Hub returned an invalid response'
                .($snippet !== '' ? ': '.str_replace(["\n", "\r"], ' ', $snippet) : '.'),
            );
        }

        if (array_key_exists('success', $decoded) && ! $decoded['success']) {
            $error = filled($decoded['error'] ?? null)
                ? (string) $decoded['error']
                : 'Request rejected by Payment Hub';

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
