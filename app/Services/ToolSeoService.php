<?php

namespace App\Services;

use App\Models\Tool;
use Illuminate\Support\Str;

class ToolSeoService
{
    public static function suggestionsForTool(Tool $tool): array
    {
        $name = trim($tool->name ?: 'SEO Tool');
        $slug = Str::slug($name);
        $tagline = trim($tool->description ?: '');
        $priceInr = (int) ($tool->price_inr ?? 0);
        $priceUsd = (int) ($tool->price_usd ?? 0);

        $title = "{$name} Group Buy India";
        if ($priceInr > 0) {
            $title .= " — from ₹{$priceInr}/month";
        }
        if ($priceUsd > 0 && $priceInr <= 0) {
            $title .= " — from \${$priceUsd}/month";
        }

        $description = "Buy {$name} at cheap price in India with Semrushtoolz.";
        if ($tagline !== '') {
            $description = "Best {$name} group buy in India. {$tagline}. One-click cloud access from Semrushtoolz.";
        }
        if ($priceInr > 0) {
            $description .= " Plans from ₹{$priceInr}/month.";
        }
        $description .= ' Instant activation via UPI & PayPal.';

        $keywords = collect([
            "{$slug} group buy",
            "group buy {$slug}",
            "buy {$slug}",
            "{$slug} cheap",
            "{$slug} group buy india",
            "{$slug} low price",
            'semrushtoolz',
        ])->unique()->implode(', ');

        return [
            'seo_title' => Str::limit($title, 200, ''),
            'seo_description' => Str::limit($description, 500, ''),
            'seo_keywords' => Str::limit($keywords, 500, ''),
            'thumbnail_url' => $tool->thumbnail_url ?: $tool->logo_url,
        ];
    }

    public static function suggestionsForToolPage(Tool $tool, ?string $pageTitle = null): array
    {
        $toolName = trim($tool->name ?: 'SEO Tool');
        $slug = Str::slug($toolName);
        $title = trim($pageTitle ?: "{$toolName} Group Buy");
        $priceInr = (int) ($tool->price_inr ?? 0);
        $tagline = trim($tool->description ?: '');

        $seoTitle = "{$toolName} Group Buy India — Buy {$toolName} at Cheap Price";
        if ($priceInr > 0) {
            $seoTitle .= " from ₹{$priceInr}";
        }

        $seoDescription = "Best {$toolName} group buy in India. Buy {$toolName} account at low price with one-click cloud access.";
        if ($tagline !== '') {
            $seoDescription = "Best {$toolName} group buy in India. {$tagline}. One-click access from Semrushtoolz.";
        }
        if ($priceInr > 0) {
            $seoDescription .= " Plans from ₹{$priceInr}/month.";
        }

        $seoKeywords = collect([
            "{$slug} group buy",
            "group buy {$slug}",
            "buy {$slug}",
            "{$slug} cheap",
            "{$slug} group buy india",
            "{$slug} groupbuy",
            'seo group buy',
        ])->unique()->implode(', ');

        return [
            'title' => $title,
            'breadcrumb_label' => "{$toolName} Group Buy",
            'url_path' => 'tools/'.$slug.'-group-buy',
            'seo_title' => Str::limit($seoTitle, 200, ''),
            'seo_description' => Str::limit($seoDescription, 500, ''),
            'seo_keywords' => Str::limit($seoKeywords, 500, ''),
            'hero_heading' => "{$toolName} Group Buy India — Buy {$toolName} at Cheap Price",
            'hero_image' => $tool->thumbnailUrl(),
            'hero_cta_label' => 'Get Started — From ₹'.($priceInr > 0 ? $priceInr : '149'),
            'hero_cta_url' => '/register',
            'hero_secondary_label' => 'View All Plans',
            'hero_secondary_url' => '/#plans',
            'pricing_heading' => "{$toolName} Group Buy Plans & Pricing",
            'pricing_subtext' => "Choose your {$toolName} plan. Save up to 20% on longer subscriptions.",
        ];
    }

    public static function productSchemaForArticle($article, array $seo, array $plans, string $articleUrl): array
    {
        $lowestInr = collect($plans)->min(fn ($p) => $p['price_inr'] ?? $p['monthly_inr'] ?? null);
        $image = method_exists($article, 'resolvedHeroImage')
            ? $article->resolvedHeroImage()
            : ($article->hero_image ?? null);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $article->title,
            'description' => $seo['description'],
            'image' => $image,
            'brand' => ['@type' => 'Brand', 'name' => 'Semrushtoolz'],
            'sku' => $article->url_path,
            'url' => $articleUrl,
        ];

        if ($lowestInr) {
            $schema['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'INR',
                'lowPrice' => (string) $lowestInr,
                'highPrice' => (string) collect($plans)->max(fn ($p) => $p['price_inr'] ?? $p['monthly_inr'] ?? $lowestInr),
                'offerCount' => (string) max(1, count($plans)),
                'availability' => 'https://schema.org/InStock',
                'url' => route('register'),
            ];
        }

        return $schema;
    }
}
