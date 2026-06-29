<?php

namespace App\Http\Controllers;

use App\Services\ArticlePageService;

class PublicArticleController extends Controller
{
    public function __construct(
        protected ArticlePageService $pages,
    ) {}

    public function show(string $urlPath)
    {
        $article = \App\Models\Article::with('tool')
            ->where('url_path', $urlPath)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->pages->render($article);
    }

    public function semrush()
    {
        return $this->show('tools/semrush-group-buy');
    }

    public function ahrefs()
    {
        return $this->show('tools/ahrefs-group-buy');
    }
}
