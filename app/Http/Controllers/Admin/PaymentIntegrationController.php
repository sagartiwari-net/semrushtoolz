<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\BuyahrefPaymentService;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentIntegrationController extends Controller
{
    public function edit()
    {
        $config = SiteSetting::buyahrefConfig();
        $paypal = SiteSetting::paypalConfig();

        return view('admin.payment-integration.edit', [
            'config' => $config,
            'hasSecret' => SiteSetting::hasBuyahrefSecret(),
            'upiReady' => app(BuyahrefPaymentService::class)->isConfigured(),
            'webhookUrl' => url('/webhooks/buyahref'),
            'paypal' => $paypal,
            'hasPayPalSecret' => SiteSetting::hasPayPalSecret(),
            'paypalReady' => app(PayPalService::class)->isConfigured(),
            'paypalWebhookUrl' => url('/webhooks/paypal'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'display_name' => ['required', 'string', 'max:80'],
            'display_description' => ['nullable', 'string', 'max:255'],
            'hub_url' => ['required', 'url', 'max:500'],
            'api_key' => ['required', 'string', 'max:120'],
            'api_secret' => [
                Rule::requiredIf(fn () => $request->boolean('enabled') && ! SiteSetting::hasBuyahrefSecret()),
                'nullable',
                'string',
                'max:255',
            ],
            'order_expiry_minutes' => ['required', 'integer', 'min:1', 'max:60'],
        ], [
            'api_secret.required' => 'Merchant API Secret required — Payment Hub se sk_... copy karke paste karo.',
        ]);

        SiteSetting::set('buyahref_enabled', $request->boolean('enabled') ? '1' : '0');
        SiteSetting::set('buyahref_display_name', trim($data['display_name']));
        SiteSetting::set('buyahref_display_description', trim($data['display_description'] ?? ''));
        SiteSetting::set('buyahref_hub_url', rtrim($data['hub_url'], '/'));
        SiteSetting::set('buyahref_api_key', trim($data['api_key']));
        SiteSetting::set('buyahref_order_expiry_minutes', (string) $data['order_expiry_minutes']);

        if (filled($data['api_secret'] ?? null)) {
            SiteSetting::set('buyahref_api_secret', trim($data['api_secret']));
        }

        return back()->with('success', 'Payment integration settings saved.');
    }

    public function testBuyahref(BuyahrefPaymentService $buyahref)
    {
        $result = $buyahref->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updatePayPal(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => [
                Rule::requiredIf(fn () => $request->boolean('enabled') && ! SiteSetting::hasPayPalSecret()),
                'nullable',
                'string',
                'max:255',
            ],
            'webhook_id' => ['nullable', 'string', 'max:64'],
            'mode' => ['required', 'string', Rule::in(['sandbox', 'live'])],
        ], [
            'client_secret.required' => 'PayPal Client Secret required — Developer Dashboard se copy karo.',
        ]);

        SiteSetting::set('paypal_enabled', $request->boolean('enabled') ? '1' : '0');
        SiteSetting::set('paypal_client_id', trim($data['client_id']));
        SiteSetting::set('paypal_webhook_id', trim($data['webhook_id'] ?? ''));
        SiteSetting::set('paypal_mode', $data['mode']);

        if (filled($data['client_secret'] ?? null)) {
            SiteSetting::set('paypal_client_secret', trim($data['client_secret']));
        }

        PayPalService::clearTokenCache();

        return back()->with('success', 'PayPal settings saved.');
    }

    public function testPayPal(PayPalService $paypal)
    {
        $result = $paypal->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
