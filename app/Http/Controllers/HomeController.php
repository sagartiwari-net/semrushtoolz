<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\HomepageService;
use App\Services\PricingService;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageService $homepage,
    ) {}

    public function index()
    {
        $content = $this->homepage->config();
        $affiliateCommissionRate = (int) (SiteSetting::affiliateConfig()['commission_rate'] * 100);

        $content['cta']['subtext_html'] = $this->homepage->resolveCtaHtml($content['cta']['subtext_html']);
        $content['features']['items'] = collect($content['features']['items'])->map(fn (array $item) => [
            ...$item,
            'desc' => $this->homepage->resolveFeatureDesc($item['desc'], $affiliateCommissionRate),
        ])->all();

        return view('pages.home', [
            'homepage' => $content,
            'customSections' => $this->homepage->sortedCustomSections($content),
            'comboTags' => $this->homepage->comboTags($content['combo_block']),
            'semrushPlans' => PricingService::homepageSemrushPlans(),
            'trialPlan' => PricingService::trialPlanForHomepage(),
            'ahrefsPlans' => PricingService::ahrefsPlans(),
            'bundlePlans' => PricingService::bundlePlans(),
            'affiliateCommissionRate' => $affiliateCommissionRate,
        ]);
    }
}
