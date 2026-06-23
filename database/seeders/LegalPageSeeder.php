<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'sort_order' => 1,
                'seo_title' => 'Terms of Service — Semrushtoolz',
                'seo_description' => 'Terms of Service for Semrushtoolz group buy access to Semrush, Ahrefs and other SEO tools.',
                'seo_keywords' => 'terms of service, semrushtoolz terms, group buy terms',
                'html_body' => $this->termsHtml(),
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'sort_order' => 2,
                'seo_title' => 'Privacy Policy — Semrushtoolz',
                'seo_description' => 'How Semrushtoolz collects, uses, and protects your personal information.',
                'seo_keywords' => 'privacy policy, data protection, semrushtoolz privacy',
                'html_body' => $this->privacyHtml(),
            ],
            [
                'slug' => 'refund',
                'title' => 'Refund Policy',
                'sort_order' => 3,
                'seo_title' => 'Refund Policy — Semrushtoolz',
                'seo_description' => 'Refund and cancellation policy for Semrushtoolz subscriptions and group buy plans.',
                'seo_keywords' => 'refund policy, cancellation, semrushtoolz refund',
                'html_body' => $this->refundHtml(),
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, ['is_published' => true]),
            );
        }
    }

    protected function termsHtml(): string
    {
        return <<<'HTML'
<p>Welcome to Semrushtoolz. By creating an account or purchasing a subscription, you agree to these Terms of Service.</p>
<h2>1. Service description</h2>
<p>Semrushtoolz provides shared cloud access to third-party SEO tools through a group-buy model. We are not affiliated with those tool providers.</p>
<h2>2. Accounts</h2>
<p>You must provide accurate information, keep your login secure, and not resell or share dashboard access.</p>
<h2>3. Payments</h2>
<p>Subscriptions activate after verified payment. Prices and plan features are shown at checkout.</p>
<h2>4. Acceptable use</h2>
<p>Do not abuse sessions, bypass limits, scrape interfaces at scale, or use the service illegally.</p>
<h2>5. Affiliate program</h2>
<p>Referral commissions follow rates and payout rules shown in your affiliate dashboard.</p>
<h2>6. Contact</h2>
<p>Questions: support@semrushtoolz.com</p>
HTML;
    }

    protected function privacyHtml(): string
    {
        return <<<'HTML'
<p>Semrushtoolz respects your privacy. This policy explains what we collect and how we use it.</p>
<h2>Information we collect</h2>
<p>Account data, order history, usage logs, support messages, and cookies for sessions and referral tracking.</p>
<h2>How we use it</h2>
<p>To provide access, process payments, send transactional emails, prevent fraud, and improve the service.</p>
<h2>Sharing</h2>
<p>We do not sell personal data. We share data only with payment processors, email providers, and when required by law.</p>
<h2>Contact</h2>
<p>Privacy questions: support@semrushtoolz.com</p>
HTML;
    }

    protected function refundHtml(): string
    {
        return <<<'HTML'
<p>Digital subscriptions are generally final once activated. Refunds are reviewed case by case.</p>
<h2>Eligible situations</h2>
<p>Duplicate charges, access never activated due to our error, or extended outage in the first 7 days.</p>
<h2>How to request</h2>
<p>Open a support ticket within 7 days of purchase with your order number.</p>
<h2>Processing</h2>
<p>Approved refunds return to the original payment method within 5–10 business days.</p>
HTML;
    }
}
