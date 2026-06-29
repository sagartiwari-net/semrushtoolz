<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function __construct(
        protected SitemapService $sitemap,
    ) {}

    public function edit()
    {
        $tab = request('tab', 'sitemap');

        return view('admin.seo.edit', [
            'tab' => $tab,
            'sitemapUrls' => $this->sitemap->urls(),
            'extraUrls' => $this->sitemap->extraUrls(),
            'robotsTxt' => $this->sitemap->robotsTxt(),
            'defaultRobots' => $this->sitemap->defaultRobotsTxt(),
            'sitemapUrl' => route('sitemap'),
            'robotsUrl' => url('/robots.txt'),
        ]);
    }

    public function updateRobots(Request $request)
    {
        $data = $request->validate([
            'robots_txt' => ['required', 'string', 'max:10000'],
        ]);

        $this->sitemap->saveRobotsTxt($data['robots_txt']);

        return redirect()
            ->route('admin.seo.edit', ['tab' => 'robots'])
            ->with('success', 'robots.txt updated.');
    }

    public function resetRobots()
    {
        $this->sitemap->saveRobotsTxt($this->sitemap->defaultRobotsTxt());

        return redirect()
            ->route('admin.seo.edit', ['tab' => 'robots'])
            ->with('success', 'robots.txt reset to default.');
    }

    public function updateSitemapExtras(Request $request)
    {
        $rows = [];
        $locs = $request->input('extra_loc', []);
        $priorities = $request->input('extra_priority', []);
        $freqs = $request->input('extra_changefreq', []);

        foreach ($locs as $i => $loc) {
            $rows[] = [
                'loc' => $loc,
                'priority' => $priorities[$i] ?? '0.6',
                'changefreq' => $freqs[$i] ?? 'monthly',
            ];
        }

        $this->sitemap->saveExtraUrls($rows);

        return redirect()
            ->route('admin.seo.edit', ['tab' => 'sitemap'])
            ->with('success', 'Extra sitemap URLs saved.');
    }
}
