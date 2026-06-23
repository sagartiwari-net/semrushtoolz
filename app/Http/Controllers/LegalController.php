<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\SiteSetting;

class LegalController extends Controller
{
    public function terms()
    {
        return $this->show('terms');
    }

    public function privacy()
    {
        return $this->show('privacy');
    }

    public function refund()
    {
        return $this->show('refund');
    }

    public function show(string $slug)
    {
        $page = LegalPage::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('pages.legal.show', [
            'page' => $page,
            'seo' => $page->seo(),
            'siteName' => SiteSetting::generalConfig()['site_name'],
        ]);
    }
}
