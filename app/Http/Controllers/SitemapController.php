<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\LegalPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('login'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('register'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        try {
            foreach (LegalPage::where('is_published', true)->orderBy('sort_order')->get() as $page) {
                $urls[] = [
                    'loc' => $page->publicUrl(),
                    'lastmod' => $page->updated_at->toDateString(),
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                ];
            }

            foreach (Article::where('is_published', true)->get() as $article) {
                $urls[] = [
                    'loc' => $article->publicUrl(),
                    'lastmod' => $article->updated_at->toDateString(),
                    'priority' => $article->isToolProductPage() ? '0.95' : '0.8',
                    'changefreq' => 'weekly',
                ];
            }
        } catch (\Throwable) {
            //
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>' . e($url['loc']) . '</loc>';
            $xml .= '<lastmod>' . ($url['lastmod'] ?? now()->toDateString()) . '</lastmod>';
            $xml .= '<changefreq>' . $url['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $url['priority'] . '</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
