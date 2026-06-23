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

        $trial = Plan::updateOrCreate(
            ['slug' => 'combo_trial'],
            [
                'product_type' => 'combo_trial',
                'display_group' => 'main',
                'name' => 'Semrush + Ahrefs Trial',
                'tagline' => 'Test the full combo — Semrush & Ahrefs access',
                'price_inr' => config('pricing.trial_durations.1.price_inr', 100),
                'price_usd' => (int) config('pricing.trial_durations.1.price_usd', 2),
                'features' => $features,
                'badge' => 'Trial',
                'is_featured' => false,
                'is_active' => true,
                'is_bundle' => true,
                'is_trial' => true,
                'show_on_homepage' => true,
                'sort_order' => 2,
            ]
        );

        $trial->tools()->sync($toolIds);

        Plan::whereNotIn('slug', ['combo', 'combo_trial'])->update([
            'is_bundle' => false,
            'is_active' => false,
            'show_on_homepage' => false,
        ]);
    }
}
