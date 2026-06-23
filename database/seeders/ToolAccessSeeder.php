<?php

namespace Database\Seeders;

use App\Models\ToolAccessGroup;
use App\Models\ToolAccessServer;
use Illuminate\Database\Seeder;

class ToolAccessSeeder extends Seeder
{
    public function run(): void
    {
        $semrushSecret = config('tool_endpoints.endpoints.nnxsm1.secret_key')
            ?? env('TOOL_SECRET_SEMRUSH', 'toolsmandi_recloudsemrush_secret_xyz123');

        $groups = config('tool_endpoints.groups', []);
        $endpoints = config('tool_endpoints.endpoints', []);

        foreach ($groups as $slug => $group) {
            $tool = isset($group['grant'])
                ? \App\Models\Tool::where('slug', $group['grant'])->first()
                : null;

            $model = ToolAccessGroup::updateOrCreate(
                ['slug' => $slug],
                [
                    'tool_id' => $tool?->id,
                    'title' => $group['title'],
                    'subtitle' => $group['subtitle'] ?? null,
                    'logo_url' => $group['logo'] ?? null,
                    'grant' => $group['grant'] ?? $slug,
                    'sort_order' => $slug === 'semrush' ? 1 : 2,
                    'is_active' => true,
                ]
            );

            $order = 0;
            foreach ($group['sections'] ?? [] as $section) {
                foreach ($section['buttons'] ?? [] as $button) {
                    $order++;
                    $type = $button['type'] ?? 'proxy';
                    $slugKey = $button['slug'] ?? 'direct-'.$order;
                    $endpoint = $endpoints[$slugKey] ?? [];

                    ToolAccessServer::updateOrCreate(
                        ['slug' => $slugKey],
                        [
                            'tool_access_group_id' => $model->id,
                            'label' => $button['label'],
                            'type' => $type,
                            'domain' => $endpoint['domain'] ?? null,
                            'website_id' => $endpoint['website_id'] ?? null,
                            'secret_key' => $endpoint['secret_key'] ?? ($type === 'proxy' ? $semrushSecret : null),
                            'direct_url' => $button['url'] ?? null,
                            'section_title' => $section['title'] ?? null,
                            'sort_order' => $order,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $ahrefsBar = \App\Models\Tool::where('slug', 'ahrefs_bar')->first();
        if ($ahrefsBar) {
            ToolAccessGroup::updateOrCreate(
                ['slug' => 'ahrefs_bar'],
                [
                    'tool_id' => $ahrefsBar->id,
                    'title' => 'Ahrefs Bar',
                    'subtitle' => 'WhatsApp activation for browser extension',
                    'logo_url' => $ahrefsBar->logo_url,
                    'grant' => 'ahrefs_bar',
                    'sort_order' => 3,
                    'is_active' => true,
                ]
            );
        }
    }
}
