<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\PricingService;

class HomeController extends Controller
{
    public function index()
    {
        $semrushPlans = PricingService::homepageSemrushPlans();
        $trialPlan = PricingService::trialPlanForHomepage();
        $ahrefsPlans = PricingService::ahrefsPlans();
        $bundlePlans = PricingService::bundlePlans();

        $planNotes = [
            [
                'marker' => '*',
                'text' => 'Site Audit is limited by the official website. This feature is available as per the limit — if the limit is available you can use it, otherwise you have to wait for the next account update.',
            ],
            [
                'marker' => '**',
                'text' => 'Bonus tools are available as per availability. If 1–2 extra tools can be arranged, they will be added as a bonus to your combo plan.',
            ],
        ];

        $faqs = [
            [
                'q' => 'What is Semrush group buy on Semrushtoolz?',
                'a' => 'Semrush group buy lets you access premium Semrush tools at a fraction of the official price. Semrushtoolz provides one-click cloud access — no extension needed. Plans start at just ₹149/month for keyword and domain analysis.',
            ],
            [
                'q' => 'What is Ahrefs group buy and how does it work?',
                'a' => 'Ahrefs group buy gives you affordable access to Ahrefs SEO tools including Keyword Explorer and Site Explorer. Choose from 4 Ahrefs plans with different daily credits and weekly export limits. Access via secure cloud system with one click.',
            ],
            [
                'q' => 'Is Semrushtoolz the best group buy SEO tools service in India?',
                'a' => 'Semrushtoolz is a trusted Ahrefs group buy India and Semrush group buy platform with 2,500+ active users. We offer the best group buy SEO tools with PayPal, UPI auto-verify, and offline payment options.',
            ],
            [
                'q' => 'How to buy Ahrefs at low price?',
                'a' => 'Buy Ahrefs cheap on Semrushtoolz starting at ₹699/month (Ahrefs Plan 1). For the best value, get our Semrush + Ahrefs combo at ₹799/month which includes Ahrefs Plan 1, Semrush, and Ahrefs Bar extension.',
            ],
            [
                'q' => 'How to buy Semrush account at cheap price?',
                'a' => 'Buy Semrush on Semrushtoolz from ₹149/month for unlimited keyword and domain analysis. Semrush With Site Audit plan is ₹499/month. All plans include export feature and one-click cloud access.',
            ],
            [
                'q' => 'What is Semrush Ahrefs group buy combo plan?',
                'a' => 'Our Semrush Ahrefs group buy combo (₹799/month) includes full Semrush access, Ahrefs Plan 1, Ahrefs Bar extension, and bonus tools when available. It is our best value plan for SEO professionals.',
            ],
            [
                'q' => 'Do I need Semrush login or Ahrefs login separately?',
                'a' => 'No separate Semrush.com or Ahrefs.com login needed. Login to your Semrushtoolz dashboard and click Access Now for instant one-click cloud access to your subscribed tools.',
            ],
            [
                'q' => 'What payment methods do you accept?',
                'a' => 'India users can pay via UPI (auto-verified), PayPal, or offline payment. International users can pay via PayPal or offline payment. Save more with 3, 6, or 12-month plans.',
            ],
            [
                'q' => 'What is the difference between Semrush and Semrush With Site Audit?',
                'a' => 'Basic Semrush plan (₹149) includes unlimited keyword and domain analysis with export. Site Audit plan (₹499) adds site audit access, subject to official website limits.',
            ],
            [
                'q' => 'Can I upgrade my Ahrefs or Semrush plan later?',
                'a' => 'Yes. Upgrade anytime from your dashboard shop. Switch between Ahrefs Plan 1, 2, 3, or 4 or upgrade to the combo plan. Remaining subscription time is adjusted.',
            ],
        ];

        $seo = [
            'title' => 'Semrush Group Buy & Ahrefs Group Buy India — Cheap SEO Tools',
            'description' => 'Buy Semrush & Ahrefs at cheap price. Best Semrush group buy, Ahrefs group buy India, Ahrefs groupbuy & SEO group buy tools. One-click access from ₹149. PayPal, UPI accepted.',
            'keywords' => 'semrush group buy, ahrefs group buy, buy semrush, buy ahrefs, cheap ahrefs account, semrush cheap, ahrefs groupbuy, group buy semrush, semrush ahrefs group buy, ahrefs group buy india, group buy seo tools, semrushtoolz, ahrefs cheap price, semrush low price, best group buy seo tools',
        ];

        $stats = [
            ['value' => '2,500+', 'label' => 'Active Users'],
            ['value' => '99.9%', 'label' => 'Uptime'],
            ['value' => '8', 'label' => 'Plans Available'],
            ['value' => '24/7', 'label' => 'Support'],
        ];

        $affiliateCommissionRate = (int) (SiteSetting::affiliateConfig()['commission_rate'] * 100);

        return view('pages.home', compact('semrushPlans', 'ahrefsPlans', 'trialPlan', 'bundlePlans', 'planNotes', 'faqs', 'stats', 'seo', 'affiliateCommissionRate'));
    }
}
