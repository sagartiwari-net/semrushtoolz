<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $combo = collect(config('pricing.main_plans'))->firstWhere('id', 'combo');

        if (! $combo) {
            return;
        }

        $features = collect($combo['features'] ?? [])->map(function ($f) {
            return is_array($f) ? $f['text'] : $f;
        })->values()->all();

        $model = Plan::updateOrCreate(
            ['slug' => 'combo'],
            [
                'product_type' => 'combo',
                'display_group' => 'main',
                'name' => $combo['name'],
                'tagline' => $combo['tagline'] ?? null,
                'price_inr' => $combo['price_inr'],
                'price_usd' => $combo['price_usd'],
                'features' => $features,
                'badge' => $combo['badge'] ?? null,
                'is_featured' => true,
                'is_active' => true,
                'is_bundle' => true,
                'show_on_homepage' => true,
                'sort_order' => 1,
            ]
        );

        $toolIds = Tool::whereIn('slug', ['semrush', 'ahrefs', 'ahrefs_bar'])->pluck('id');
        $model->tools()->sync($toolIds);

        Plan::where('slug', '!=', 'combo')->update([
            'is_bundle' => false,
            'is_active' => false,
            'show_on_homepage' => false,
        ]);
    }
}
