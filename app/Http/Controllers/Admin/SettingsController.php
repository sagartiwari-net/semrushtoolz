<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'general' => SiteSetting::generalConfig(),
            'affiliate' => SiteSetting::affiliateConfig(),
            'wallet' => SiteSetting::walletConfig(),
            'support' => SiteSetting::supportConfig(),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_favicon_url' => ['nullable', 'url', 'max:500'],
            'support_email' => ['required', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'telegram_handle' => ['nullable', 'string', 'max:100'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'gst_enabled' => ['nullable', 'boolean'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gst_label' => ['nullable', 'string', 'max:30'],
            'gst_number' => ['nullable', 'string', 'max:20'],
        ]);

        SiteSetting::set('site_name', $data['site_name']);
        SiteSetting::set('site_favicon_url', $data['site_favicon_url'] ?? SiteSetting::DEFAULT_FAVICON_URL);
        SiteSetting::set('support_email', $data['support_email']);
        SiteSetting::set('whatsapp_number', $data['whatsapp_number'] ?? '');
        SiteSetting::set('telegram_handle', $data['telegram_handle'] ?? '');
        SiteSetting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');
        SiteSetting::set('gst_enabled', $request->boolean('gst_enabled') ? '1' : '0');
        SiteSetting::set('gst_rate', (string) ($data['gst_rate'] ?? 18));
        SiteSetting::set('gst_label', $data['gst_label'] ?? 'GST');
        SiteSetting::set('gst_number', $data['gst_number'] ?? '');

        return back()->with('success', 'General settings saved.');
    }

    public function updateAffiliate(Request $request)
    {
        $data = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_payout' => ['required', 'integer', 'min:1'],
            'auto_approve' => ['nullable', 'boolean'],
            'signup_bonus_enabled' => ['nullable', 'boolean'],
            'signup_bonus_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'signup_bonus_days' => ['required', 'integer', 'min:1', 'max:30'],
            'monthly_email_enabled' => ['nullable', 'boolean'],
            'monthly_email_min_payable' => ['required', 'integer', 'min:0'],
            'paypal_hold_months' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        SiteSetting::set('affiliate_commission_rate', (string) ($data['commission_rate'] / 100));
        SiteSetting::set('affiliate_min_payout', (string) $data['min_payout']);
        SiteSetting::set('affiliate_auto_approve', $request->boolean('auto_approve') ? '1' : '0');
        SiteSetting::set('affiliate_signup_bonus_enabled', $request->boolean('signup_bonus_enabled') ? '1' : '0');
        SiteSetting::set('affiliate_signup_bonus_percent', (string) ($data['signup_bonus_percent'] / 100));
        SiteSetting::set('affiliate_signup_bonus_days', (string) $data['signup_bonus_days']);
        SiteSetting::set('affiliate_monthly_email_enabled', $request->boolean('monthly_email_enabled') ? '1' : '0');
        SiteSetting::set('affiliate_monthly_email_min_payable', (string) $data['monthly_email_min_payable']);
        SiteSetting::set('affiliate_paypal_hold_months', (string) $data['paypal_hold_months']);

        return back()->with('success', 'Affiliate settings saved.');
    }

    public function updateWallet(Request $request)
    {
        $data = $request->validate([
            'wallet_enabled' => ['nullable', 'boolean'],
            'wallet_affiliate_bonus_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'wallet_cashback_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'wallet_min_affiliate_transfer' => ['required', 'integer', 'min:1'],
        ]);

        SiteSetting::set('wallet_enabled', $request->boolean('wallet_enabled') ? '1' : '0');
        SiteSetting::set('wallet_affiliate_bonus_percent', (string) ($data['wallet_affiliate_bonus_percent'] / 100));
        SiteSetting::set('wallet_cashback_percent', (string) ($data['wallet_cashback_percent'] / 100));
        SiteSetting::set('wallet_min_affiliate_transfer', (string) $data['wallet_min_affiliate_transfer']);

        return back()->with('success', 'Wallet settings saved.');
    }

    public function updateSupport(Request $request)
    {
        $data = $request->validate([
            'faqs' => ['nullable', 'array'],
            'faqs.*.q' => ['nullable', 'string', 'max:255'],
            'faqs.*.a' => ['nullable', 'string', 'max:2000'],
        ]);

        $faqs = collect($data['faqs'] ?? [])
            ->filter(fn ($faq) => filled($faq['q'] ?? null) && filled($faq['a'] ?? null))
            ->values()
            ->all();

        SiteSetting::set('support_faqs', json_encode($faqs));

        return back()->with('success', 'Support FAQs saved.');
    }
}
