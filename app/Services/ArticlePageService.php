<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\View\View;

class ArticlePageService
{
    public function render(Article $article, bool $isPreview = false, ?string $backUrl = null): View
    {
        $article->loadMissing('tool');

        $plans = $article->plan_slugs
            ? PricingService::plansBySlugs($article->plan_slugs)
            : PricingService::allShopPlans();

        $relatedToolPages = Article::with('tool')
            ->where('is_published', true)
            ->where('id', '!=', $article->id)
            ->whereNotNull('tool_id')
            ->orderBy('title')
            ->get();

        return view('pages.articles.show', [
            'article' => $article,
            'seo' => $article->resolvedSeo(),
            'plans' => $plans,
            'relatedToolPages' => $relatedToolPages,
            'isPreview' => $isPreview,
            'backUrl' => $backUrl,
        ]);
    }
}
