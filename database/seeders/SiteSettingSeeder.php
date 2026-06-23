<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::set('site_name', 'Semrushtoolz');
        SiteSetting::set('site_favicon_url', SiteSetting::DEFAULT_FAVICON_URL);
        SiteSetting::set('support_email', 'support@semrushtoolz.com');
        SiteSetting::set('whatsapp_number', '');
        SiteSetting::set('telegram_handle', '');
        SiteSetting::set('maintenance_mode', '0');
        SiteSetting::set('gst_enabled', '0');
        SiteSetting::set('gst_rate', '18');
        SiteSetting::set('gst_label', 'GST');
        SiteSetting::set('gst_number', '');
        SiteSetting::set('affiliate_commission_rate', '0.20');
        SiteSetting::set('affiliate_min_payout', '500');
        SiteSetting::set('affiliate_auto_approve', '1');
        SiteSetting::set('wallet_enabled', '1');
        SiteSetting::set('wallet_affiliate_bonus_percent', '0.10');
        SiteSetting::set('wallet_cashback_percent', '0.10');
        SiteSetting::set('wallet_min_affiliate_transfer', '100');
        SiteSetting::set('support_faqs', json_encode(SiteSetting::defaultSupportFaqs()));

        SiteSetting::set('extension_download_url', env('EXTENSION_DOWNLOAD_URL', ''));
        SiteSetting::set('extension_version', '1.0.0');
        SiteSetting::set('extension_visibility', 'any_subscription');
        SiteSetting::set('extension_indicator_id', 'my-extension-installed-indicator');
        SiteSetting::set('extension_api_url', env('EXTENSION_API_URL', ''));
        SiteSetting::set('extension_secret_key', env('EXTENSION_SECRET_KEY', ''));
        SiteSetting::set('extension_client_slug', env('EXTENSION_CLIENT_SLUG', 'semrushtoolz'));
    }
}
