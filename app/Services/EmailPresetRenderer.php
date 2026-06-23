<?php

namespace App\Services;

use App\Models\EmailPreset;
use App\Models\SiteSetting;

class EmailPresetRenderer
{
    public function render(string $template, array $data): string
    {
        $data['site_url'] = $data['site_url'] ?? url('/');
        $data['favicon_url'] = $data['favicon_url'] ?? SiteSetting::faviconUrl();
        $data['privacy_url'] = $data['privacy_url'] ?? route('legal.privacy');
        $data['unsubscribe_url'] = $data['unsubscribe_url'] ?? url('/unsubscribe');

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            fn (array $matches) => e((string) ($data[$matches[1]] ?? '')),
            $template,
        );
    }

    public function renderPreset(EmailPreset $preset, array $data): array
    {
        return [
            'subject' => $this->render($preset->subject, $data),
            'html_body' => $this->render($preset->html_body, $data),
            'text_body' => $preset->text_body ? $this->render($preset->text_body, $data) : null,
        ];
    }

    public function sampleData(?string $presetKey = null): array
    {
        $samples = [
            'name' => 'Alex Kumar',
            'otp' => '482910',
            'minutes' => '10',
            'intro' => 'Use this code to sign in to your account.',
            'plan_name' => 'Pro Monthly',
            'tool_name' => 'Semrush',
            'verification_url' => url('/email/verify'),
            'reset_url' => url('/password/reset'),
            'expire_minutes' => '60',
            'discount_percent' => '10',
            'days_left' => '2',
            'expires_at' => now()->addDays(2)->format('d M Y, h:i A'),
            'shop_url' => route('dashboard.shop'),
            'period_label' => now()->subMonth()->format('F Y'),
            'as_of' => now()->format('d M Y'),
            'month_earnings' => '₹1,240',
            'month_payouts' => '₹500',
            'carried_balance' => '₹320',
            'held_amount' => '₹150',
            'total_payable' => '₹860',
            'paypal_hold_note' => 'PayPal commissions are on hold for 6 months due to PayPal refund policy.',
            'affiliates_url' => route('dashboard.affiliates'),
            'referral_code' => 'ALEX2026',
            'referral_link' => url('/?ref=ALEX2026'),
            'commission_rate' => '20',
            'total_earned' => '₹4,500',
            'growth_tip' => 'Share your link on WhatsApp groups and social media to reach more potential customers.',
            'program_intro' => 'Earn 20% commission on every purchase made through your unique referral link.',
            'signup_bonus_note' => 'New users you refer get 10% off their first month.',
            'wallet_balance' => '1,250',
            'wallet_url' => route('dashboard.wallet'),
            'cashback_amount' => '150',
            'purchase_name' => 'Pro Monthly',
            'site_url' => url('/'),
            'favicon_url' => SiteSetting::faviconUrl(),
            'privacy_url' => route('legal.privacy'),
            'unsubscribe_url' => url('/unsubscribe'),
            'amount' => '499',
            'currency' => 'INR',
            'order_id' => 'ORD-DEMO1234',
            'dashboard_url' => route('dashboard.tools'),
            'checkout_url' => route('dashboard.shop'),
            'login_url' => route('login'),
            'month_label' => now()->format('F Y'),
        ];

        return match ($presetKey) {
            EmailPreset::KEY_WALLET_TOPUP_COMPLETED,
            EmailPreset::KEY_WALLET_CASHBACK_RECEIVED => $samples,
            EmailPreset::KEY_AFFILIATE_PROGRAM_INVITE => $samples,
            EmailPreset::KEY_AFFILIATE_PROGRAM_BOOST => $samples,
            EmailPreset::KEY_AFFILIATE_MONTHLY_REPORT => $samples,
            default => $samples,
        };
    }
}
