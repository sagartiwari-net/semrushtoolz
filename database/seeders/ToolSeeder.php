<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'slug' => 'semrush',
                'name' => 'Semrush',
                'description' => 'Keyword & domain analysis',
                'logo_url' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
                'access_type' => 'cloud',
                'category' => 'seo',
                'price_inr' => 149,
                'price_usd' => 3,
                'shop_features' => ['Unlimited Keyword Analysis', 'Unlimited Domain Analysis', 'Export Feature', 'One-Click Cloud Access'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'semrush_site_audit',
                'name' => 'Semrush With Site Audit',
                'description' => 'Full Semrush + site audit',
                'logo_url' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
                'access_type' => 'cloud',
                'category' => 'seo',
                'grants_tool_slug' => 'semrush',
                'price_inr' => 499,
                'price_usd' => 8,
                'shop_features' => ['Unlimited Keyword Analysis', 'Site Audit', 'Export Feature', 'One-Click Cloud Access'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'ahrefs',
                'name' => 'Ahrefs',
                'description' => 'Backlink & keyword explorer (access tool)',
                'logo_url' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'access_type' => 'cloud',
                'category' => 'seo',
                'price_inr' => 0,
                'price_usd' => 0,
                'show_in_shop' => false,
                'sort_order' => 10,
            ],
            [
                'slug' => 'ahrefs_plan_1',
                'name' => 'Ahrefs Plan 1',
                'description' => 'Essential Ahrefs access',
                'logo_url' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'access_type' => 'cloud',
                'category' => 'seo',
                'grants_tool_slug' => 'ahrefs',
                'price_inr' => 699,
                'price_usd' => 10,
                'shop_features' => ['30 Credits per Day', '10K Weekly Export', 'Keyword Explorer', 'Site Explorer'],
                'sort_order' => 11,
            ],
            [
                'slug' => 'ahrefs_plan_2',
                'name' => 'Ahrefs Plan 2',
                'description' => 'More credits & exports',
                'logo_url' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'access_type' => 'cloud',
                'category' => 'seo',
                'grants_tool_slug' => 'ahrefs',
                'price_inr' => 899,
                'price_usd' => 14,
                'shop_features' => ['50 Credits per Day', '15K Weekly Export', 'Keyword Explorer', 'Site Explorer'],
                'sort_order' => 12,
            ],
            [
                'slug' => 'ahrefs_plan_3',
                'name' => 'Ahrefs Plan 3',
                'description' => 'Power user access',
                'logo_url' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'access_type' => 'cloud',
                'category' => 'seo',
                'grants_tool_slug' => 'ahrefs',
                'price_inr' => 1399,
                'price_usd' => 20,
                'shop_badge' => 'Popular',
                'shop_features' => ['100 Credits per Day', '25K Weekly Export', 'Keyword Explorer', 'Site Explorer'],
                'sort_order' => 13,
            ],
            [
                'slug' => 'ahrefs_plan_4',
                'name' => 'Ahrefs Plan 4',
                'description' => 'Maximum Ahrefs limits',
                'logo_url' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'access_type' => 'cloud',
                'category' => 'seo',
                'grants_tool_slug' => 'ahrefs',
                'price_inr' => 2499,
                'price_usd' => 30,
                'shop_features' => ['200 Credits per Day', '30K Weekly Export', 'Keyword Explorer', 'Site Explorer'],
                'sort_order' => 14,
            ],
            [
                'slug' => 'ahrefs_bar',
                'name' => 'Ahrefs Bar',
                'description' => 'Browser extension — WhatsApp activation',
                'logo_url' => 'https://ik.imagekit.io/webfiles/ahrefs.avif?updatedAt=1753593854334',
                'access_type' => 'whatsapp',
                'category' => 'seo',
                'whatsapp_number' => '918510848196',
                'whatsapp_message' => 'Contact us on WhatsApp for bar activation',
                'price_inr' => 0,
                'price_usd' => 0,
                'show_in_shop' => false,
                'is_extension' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($items as $item) {
            Tool::updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, ['is_active' => true, 'show_in_shop' => $item['show_in_shop'] ?? true])
            );
        }

        Tool::where('slug', 'ubersugest')->update(['category' => 'ai']);
    }
}
