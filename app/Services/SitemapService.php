<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LegalPage;
use App\Models\SiteSetting;

class SitemapService
{
    public const EXTRA_URLS_KEY = 'sitemap_extra_urls';

    public const ROBOTS_KEY = 'robots_txt';

    /**
     * @return array<int, array{loc: string, lastmod?: string, priority: string, changefreq: string, source: string}>
     */
    public function urls(): array
    {
        $urls = collect($this->coreUrls());

        try {
            foreach (LegalPage::where('is_published', true)->orderBy('sort_order')->get() as $page) {
                $urls->push([
                    'loc' => $page->publicUrl(),
                    'lastmod' => $page->updated_at->toDateString(),
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                    'source' => 'Legal page: '.$page->title,
                ]);
            }

            foreach (Article::where('is_published', true)->orderBy('title')->get() as $article) {
                $urls->push([
                    'loc' => $article->publicUrl(),
                    'lastmod' => $article->updated_at->toDateString(),
                    'priority' => $article->isToolProductPage() ? '0.95' : '0.8',
                    'changefreq' => 'weekly',
                    'source' => 'Tool page: '.$article->title,
                ]);
            }
        } catch (\Throwable) {
            //
        }

        foreach ($this->extraUrls() as $extra) {
            $urls->push([
                'loc' => $extra['loc'],
                'lastmod' => $extra['lastmod'] ?? now()->toDateString(),
                'priority' => $extra['priority'] ?? '0.6',
                'changefreq' => $extra['changefreq'] ?? 'monthly',
                'source' => 'Manual',
            ]);
        }

        return $urls
            ->unique('loc')
            ->values()
            ->all();
    }

    public function toXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($this->urls() as $url) {
            $xml .= '<url>';
            $xml .= '<loc>'.e($url['loc']).'</loc>';
            $xml .= '<lastmod>'.($url['lastmod'] ?? now()->toDateString()).'</lastmod>';
            $xml .= '<changefreq>'.$url['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$url['priority'].'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * @return array<int, array{loc: string, priority?: string, changefreq?: string, lastmod?: string}>
     */
    public function extraUrls(): array
    {
        $raw = json_decode((string) SiteSetting::get(self::EXTRA_URLS_KEY, '[]'), true);

        return is_array($raw) ? array_values(array_filter($raw, fn ($row) => filled($row['loc'] ?? null))) : [];
    }

    /**
     * @param  array<int, array{loc: string, priority?: string, changefreq?: string}>  $urls
     */
    public function saveExtraUrls(array $urls): void
    {
        $clean = collect($urls)
            ->map(fn ($row) => [
                'loc' => trim((string) ($row['loc'] ?? '')),
                'priority' => trim((string) ($row['priority'] ?? '0.6')) ?: '0.6',
                'changefreq' => trim((string) ($row['changefreq'] ?? 'monthly')) ?: 'monthly',
            ])
            ->filter(fn ($row) => filled($row['loc']))
            ->values()
            ->all();

        SiteSetting::set(self::EXTRA_URLS_KEY, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function robotsTxt(): string
    {
        $stored = trim((string) SiteSetting::get(self::ROBOTS_KEY, ''));

        return $stored !== '' ? $stored : $this->defaultRobotsTxt();
    }

    public function saveRobotsTxt(string $content): void
    {
        SiteSetting::set(self::ROBOTS_KEY, trim($content));
    }

    public function defaultRobotsTxt(): string
    {
        $sitemap = rtrim(config('app.url'), '/').'/sitemap.xml';

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            '',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /login',
            'Disallow: /register',
            '',
            'Sitemap: '.$sitemap,
            '',
        ]);
    }

    /**
     * @return array<int, array{loc: string, lastmod?: string, priority: string, changefreq: string, source: string}>
     */
    protected function coreUrls(): array
    {
        return [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily', 'source' => 'Homepage'],
            ['loc' => route('subscribe'), 'priority' => '0.85', 'changefreq' => 'weekly', 'source' => 'Subscribe page'],
            ['loc' => route('login'), 'priority' => '0.4', 'changefreq' => 'monthly', 'source' => 'Login'],
            ['loc' => route('register'), 'priority' => '0.7', 'changefreq' => 'weekly', 'source' => 'Register'],
        ];
    }
}
