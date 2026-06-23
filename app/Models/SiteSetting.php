<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const DEFAULT_FAVICON_URL = 'https://ik.imagekit.io/webfiles/SemrushToolz_fav.png';

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function extensionConfig(): array
    {
        return [
            'download_url' => static::get('extension_download_url', ''),
            'version' => static::get('extension_version', '1.0.0'),
            'install_guide' => static::get('extension_install_guide', ''),
            'api_url' => static::get('extension_api_url', env('EXTENSION_API_URL', '')),
            'secret_key' => static::get('extension_secret_key', env('EXTENSION_SECRET_KEY', '')),
            'client_slug' => static::get('extension_client_slug', env('EXTENSION_CLIENT_SLUG', 'semrushtoolz')),
            'visibility' => static::get('extension_visibility', 'any_subscription'),
            'indicator_id' => static::get('extension_indicator_id', 'my-extension-installed-indicator'),
        ];
    }

    public static function buyahrefConfig(): array
    {
        return [
            'enabled' => filter_var(static::get('buyahref_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'display_name' => static::get('buyahref_display_name', 'UPI') ?: 'UPI',
            'display_description' => static::get('buyahref_display_description', 'Secure QR payment — auto-verified (5 min window)'),
            'hub_url' => static::get('buyahref_hub_url', 'https://buyahref.com/payment') ?: 'https://buyahref.com/payment',
            'api_key' => static::get('buyahref_api_key', ''),
            'api_secret' => static::get('buyahref_api_secret', ''),
            'order_expiry_minutes' => max(1, min(60, (int) static::get('buyahref_order_expiry_minutes', '5'))),
        ];
    }

    public static function hasBuyahrefSecret(): bool
    {
        return filled(static::get('buyahref_api_secret', ''));
    }

    public static function paypalConfig(): array
    {
        return [
            'enabled' => filter_var(static::get('paypal_enabled', env('PAYPAL_ENABLED', '0')), FILTER_VALIDATE_BOOLEAN),
            'client_id' => static::get('paypal_client_id', env('PAYPAL_CLIENT_ID', '')) ?: '',
            'client_secret' => static::get('paypal_client_secret', env('PAYPAL_CLIENT_SECRET', '')) ?: '',
            'webhook_id' => static::get('paypal_webhook_id', env('PAYPAL_WEBHOOK_ID', '')) ?: '',
            'product_id' => static::get('paypal_product_id', env('PAYPAL_PRODUCT_ID', '')) ?: '',
            'mode' => static::get('paypal_mode', env('PAYPAL_MODE', 'sandbox')) ?: 'sandbox',
        ];
    }

    public static function hasPayPalSecret(): bool
    {
        return filled(static::get('paypal_client_secret', env('PAYPAL_CLIENT_SECRET', '')));
    }

    public static function mailPanelConfig(): array
    {
        return [
            'enabled' => filter_var(
                static::get('mail_panel_enabled', env('MAIL_PANEL_ENABLED', '1')),
                FILTER_VALIDATE_BOOLEAN,
            ),
            'url' => static::get('mail_panel_url', env('MAIL_PANEL_URL', 'https://email.sagartiwari.net')) ?: 'https://email.sagartiwari.net',
            'api_key' => static::get('mail_panel_api_key', env('MAIL_PANEL_API_KEY', '')) ?: '',
        ];
    }

    public static function hasMailPanelApiKey(): bool
    {
        return filled(static::get('mail_panel_api_key', env('MAIL_PANEL_API_KEY', '')));
    }

    public static function faviconUrl(): string
    {
        $url = trim((string) static::get('site_favicon_url', self::DEFAULT_FAVICON_URL));

        return $url !== '' ? $url : self::DEFAULT_FAVICON_URL;
    }

    public static function generalConfig(): array
    {
        return [
            'site_name' => static::get('site_name', config('app.name', 'Semrushtoolz')) ?: 'Semrushtoolz',
            'favicon_url' => static::faviconUrl(),
            'support_email' => static::get('support_email', 'support@semrushtoolz.com') ?: 'support@semrushtoolz.com',
            'whatsapp_number' => static::get('whatsapp_number', '') ?: '',
            'telegram_handle' => static::get('telegram_handle', '') ?: '',
            'maintenance_mode' => filter_var(static::get('maintenance_mode', '0'), FILTER_VALIDATE_BOOLEAN),
            'gst_enabled' => filter_var(static::get('gst_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'gst_rate' => max(0, min(100, (float) static::get('gst_rate', '18'))),
            'gst_label' => static::get('gst_label', 'GST') ?: 'GST',
            'gst_number' => static::get('gst_number', '') ?: '',
        ];
    }

    public static function gstConfig(): array
    {
        $general = static::generalConfig();

        return [
            'enabled' => $general['gst_enabled'],
            'rate' => $general['gst_rate'],
            'label' => $general['gst_label'],
            'number' => $general['gst_number'],
        ];
    }

    public static function walletConfig(): array
    {
        return [
            'enabled' => filter_var(static::get('wallet_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'affiliate_transfer_bonus_percent' => max(0, min(1, (float) static::get('wallet_affiliate_bonus_percent', '0.10'))),
            'purchase_cashback_percent' => max(0, min(1, (float) static::get('wallet_cashback_percent', '0.10'))),
            'min_affiliate_transfer' => max(1, (int) static::get('wallet_min_affiliate_transfer', '100')),
            'topup_amounts' => [50, 100, 300, 500, 1000],
        ];
    }

    public static function affiliateConfig(): array
    {
        return [
            'commission_rate' => max(0, min(1, (float) static::get('affiliate_commission_rate', env('AFFILIATE_COMMISSION_RATE', '0.20')))),
            'min_payout' => max(1, (int) static::get('affiliate_min_payout', env('AFFILIATE_MIN_PAYOUT', '500'))),
            'auto_approve' => filter_var(static::get('affiliate_auto_approve', '1'), FILTER_VALIDATE_BOOLEAN),
            'signup_bonus_enabled' => filter_var(static::get('affiliate_signup_bonus_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'signup_bonus_percent' => max(0, min(1, (float) static::get('affiliate_signup_bonus_percent', '0.10'))),
            'signup_bonus_days' => max(1, min(30, (int) static::get('affiliate_signup_bonus_days', '3'))),
            'monthly_email_enabled' => filter_var(static::get('affiliate_monthly_email_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'monthly_email_min_payable' => max(0, (int) static::get('affiliate_monthly_email_min_payable', '300')),
            'paypal_hold_months' => max(1, min(12, (int) static::get('affiliate_paypal_hold_months', '6'))),
        ];
    }

    public static function supportConfig(): array
    {
        $general = static::generalConfig();

        return [
            'whatsapp' => $general['whatsapp_number'],
            'telegram' => $general['telegram_handle'],
            'email' => $general['support_email'],
            'faqs' => json_decode(static::get('support_faqs', '[]'), true) ?: static::defaultSupportFaqs(),
        ];
    }

    public static function whatsappDigits(): ?string
    {
        $digits = preg_replace('/\D/', '', static::generalConfig()['whatsapp_number'] ?? '');

        return filled($digits) ? $digits : null;
    }

    public static function whatsappLink(?string $pageUrl = null): ?string
    {
        $digits = static::whatsappDigits();

        if (! $digits) {
            return null;
        }

        $url = $pageUrl ?? url()->current();
        $message = "Hi Semrushtoolz! I need help.\n\nPage: {$url}";
        $text = rawurlencode($message);

        return "https://wa.me/{$digits}?text={$text}";
    }

  /** @return array<int, array{q: string, a: string}> */
    public static function defaultSupportFaqs(): array
    {
        return [
            ['q' => 'How to access tools?', 'a' => 'Go to Dashboard → Tools, pick a tool, and click Access. Your active subscription must include that tool.'],
            ['q' => 'Payment not verified?', 'a' => 'UPI payments auto-verify within 5 minutes. If still pending, open Support and share your order ID.'],
            ['q' => 'Plan upgrade?', 'a' => 'Visit the Shop, choose a higher plan, and complete checkout. Your new plan activates after payment.'],
        ];
    }
}
