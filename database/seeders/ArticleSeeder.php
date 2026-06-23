<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $semrushTool = Tool::where('slug', 'semrush')->first();
        $ahrefsTool = Tool::where('slug', 'ahrefs')->first();

        Article::updateOrCreate(
            ['url_path' => 'tools/semrush-group-buy'],
            [
                'title' => 'Semrush Group Buy',
                'breadcrumb_label' => 'Semrush Group Buy',
                'seo_title' => 'Semrush Group Buy India — Buy Semrush at Cheap Price from ₹149',
                'seo_description' => 'Best Semrush group buy in India. Buy Semrush account at low price with one-click cloud access. Group buy Semrush plans from ₹149/month.',
                'seo_keywords' => 'semrush group buy, group buy semrush, buy semrush, semrush cheap',
                'hero_heading' => 'Semrush Group Buy India — Buy Semrush at Cheap Price',
                'hero_subtext' => 'Looking for the best Semrush group buy service in India? Semrushtoolz gives you premium Semrush access from just ₹149/month with instant one-click cloud access.',
                'hero_image' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
                'hero_cta_label' => 'Get Semrush Access — ₹149/mo',
                'hero_cta_url' => '/register',
                'hero_secondary_label' => 'View All Plans',
                'hero_secondary_url' => '/#plans',
                'tool_id' => $semrushTool?->id,
                'plan_slugs' => ['semrush', 'semrush_site_audit', 'combo'],
                'pricing_heading' => 'Semrush Group Buy Plans & Pricing',
                'pricing_subtext' => 'Choose the buy Semrush account plan that fits your needs.',
                'show_pricing' => true,
                'features' => [
                    ['Keyword Research', 'Unlimited keyword analysis with search volume, difficulty, and related terms.'],
                    ['Domain Analysis', 'Full domain overview, organic traffic estimates, and competitor insights.'],
                    ['Export Feature', 'Export your research data for reports and client deliverables.'],
                    ['Site Audit', 'Technical SEO audit to find and fix on-page issues (premium plan).'],
                    ['One-Click Access', 'Cloud-based instant access — no extension or cookie setup needed.'],
                    ['24/7 Support', 'Get help anytime via our support ticket system.'],
                ],
                'steps' => [
                    ['01', 'Create Account', 'Sign up free at Semrushtoolz.com — takes less than 2 minutes.'],
                    ['02', 'Choose Your Plan', 'Select Semrush Basic (₹149), Site Audit (₹499), or Combo (₹799).'],
                    ['03', 'Make Payment', 'Pay via PayPal, UPI (auto-verified), or offline payment.'],
                    ['04', 'Access Instantly', 'Login to dashboard → My Tools → Open Access Panel. Done!'],
                ],
                'highlights' => [
                    'Instant activation after payment',
                    'One-click cloud access (no extension)',
                    'PayPal, UPI & offline payment',
                    '24/7 customer support',
                ],
                'content_blocks' => [
                    [
                        'id' => 'what-is',
                        'heading' => 'What is Semrush Group Buy?',
                        'html' => '<p><strong>Semrush group buy</strong> is a cost-sharing model that gives SEO professionals access to premium Semrush tools at a fraction of the official price.</p>',
                    ],
                    [
                        'id' => 'why-cheap',
                        'heading' => 'Why Buy Semrush at Low Price?',
                        'html' => '<p>Official Semrush pricing starts at over $139/month. With Semrushtoolz group buy, you save over 95% while still getting core features.</p>',
                    ],
                ],
                'faqs' => [
                    ['q' => 'What is Semrush group buy?', 'a' => 'Semrush group buy is a shared-access model where multiple users access premium Semrush tools at a fraction of the official price.'],
                    ['q' => 'How much does Semrush group buy cost?', 'a' => 'Plans start at ₹149/month ($3) for keyword and domain analysis.'],
                    ['q' => 'Is Semrush group buy safe?', 'a' => 'Semrushtoolz uses a secure cloud-based system with encrypted sessions.'],
                ],
                'footer_cta_heading' => 'Start Your Semrush Group Buy Today',
                'footer_cta_subtext' => 'Join 2,500+ SEO professionals. Semrush group buy from just ₹149/month.',
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        Article::updateOrCreate(
            ['url_path' => 'tools/ahrefs-group-buy'],
            [
                'title' => 'Ahrefs Group Buy',
                'breadcrumb_label' => 'Ahrefs Group Buy',
                'seo_title' => 'Ahrefs Group Buy India — Buy Ahrefs Cheap from ₹699/month',
                'seo_description' => 'Best Ahrefs group buy in India. Buy Ahrefs account at cheap price with Keyword Explorer & Site Explorer.',
                'seo_keywords' => 'ahrefs group buy, buy ahrefs, ahrefs cheap',
                'hero_heading' => 'Ahrefs Group Buy India — Buy Ahrefs at Cheap Price',
                'hero_subtext' => 'Get premium Ahrefs SEO tools from ₹699/month with secure one-click cloud access on Semrushtoolz.',
                'hero_image' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'hero_cta_label' => 'Get Ahrefs Access — ₹699/mo',
                'hero_cta_url' => '/register',
                'hero_secondary_label' => 'View All Plans',
                'hero_secondary_url' => '/#plans',
                'tool_id' => $ahrefsTool?->id,
                'plan_slugs' => ['ahrefs_plan_1', 'ahrefs_plan_2', 'ahrefs_plan_3', 'ahrefs_plan_4', 'combo'],
                'pricing_heading' => 'Ahrefs Group Buy Plans & Pricing',
                'pricing_subtext' => 'Choose your Ahrefs plan based on daily credits and export limits.',
                'show_pricing' => true,
                'features' => [
                    ['Keyword Explorer', 'Research keywords with accurate difficulty and volume data.'],
                    ['Site Explorer', 'Analyze any website backlinks, organic traffic, and top pages.'],
                    ['Daily Credits', 'Use credits each day based on your plan tier.'],
                    ['Weekly Export', 'Export data up to your plan weekly limit.'],
                ],
                'steps' => [
                    ['01', 'Sign Up', 'Create your free Semrushtoolz account.'],
                    ['02', 'Pick Ahrefs Plan', 'Choose Plan 1–4 based on your usage needs.'],
                    ['03', 'Pay & Activate', 'UPI, PayPal, or offline — access in minutes.'],
                    ['04', 'Open Access Panel', 'Dashboard → My Tools → Ahrefs → pick a server.'],
                ],
                'highlights' => [
                    'Up to 200 credits/day on Plan 4',
                    'Secure cloud access',
                    'Combo plan includes Semrush + Ahrefs',
                ],
                'content_blocks' => [
                    [
                        'id' => 'what-is',
                        'heading' => 'What is Ahrefs Group Buy?',
                        'html' => '<p><strong>Ahrefs group buy</strong> lets you access premium Ahrefs SEO tools at a much lower price than the official subscription.</p>',
                    ],
                ],
                'faqs' => [
                    ['q' => 'What is Ahrefs group buy?', 'a' => 'Ahrefs group buy provides affordable access to Keyword Explorer, Site Explorer, and more.'],
                    ['q' => 'How much does Ahrefs group buy cost?', 'a' => 'Starting at ₹699/month ($10) for Plan 1 with 30 credits/day.'],
                ],
                'footer_cta_heading' => 'Start Your Ahrefs Group Buy Today',
                'footer_cta_subtext' => 'Affordable Ahrefs access from ₹699/month.',
                'is_published' => true,
                'published_at' => now(),
            ]
        );
    }
}
