<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Services\ResellerPricingService;
use Illuminate\Http\Request;

class ResellerPricingController extends Controller
{
    public function __construct(
        protected ResellerPricingService $pricing,
    ) {}

    public function edit()
    {
        $tools = Tool::query()
            ->where('is_active', true)
            ->where('show_in_shop', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaults = $this->pricing->defaultsMap();

        return view('admin.resellers.pricing', compact('tools', 'defaults'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $tools = Tool::query()->get()->keyBy('id');

        foreach ($data['prices'] ?? [] as $toolId => $price) {
            $tool = $tools->get((int) $toolId);
            if (! $tool) {
                continue;
            }

            $raw = $request->input("prices.{$toolId}");
            if ($raw === null || $raw === '') {
                $this->pricing->deleteDefaultPrice($tool);
            } else {
                $this->pricing->upsertDefaultPrice($tool, (int) $price);
            }
        }

        return back()->with('success', 'Default reseller pricing saved.');
    }
}
