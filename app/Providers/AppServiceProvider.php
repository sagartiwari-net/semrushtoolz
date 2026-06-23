<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\LegalPage;
use App\Http\Controllers\PublicArticleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            Article::with('tool')->where('is_published', true)->each(function (Article $article) {
                $path = trim($article->url_path, '/');
                $routeName = $article->tool
                    ? 'tools.'.$article->tool->slug
                    : 'pages.'.str_replace(['/', '-'], '_', $path);

                Route::get('/'.$path, function () use ($path) {
                    return app(PublicArticleController::class)->show($path);
                })->name($routeName);
            });

            View::composer(['components.public-footer'], function ($view) {
                $view->with('footerArticles', Article::where('is_published', true)->orderBy('title')->get(['title', 'url_path', 'breadcrumb_label', 'tool_id']));
                $view->with('footerLegalPages', LegalPage::where('is_published', true)->orderBy('sort_order')->get());
            });
        } catch (\Throwable) {
            // DB may not be ready during initial install
        }
    }
}
