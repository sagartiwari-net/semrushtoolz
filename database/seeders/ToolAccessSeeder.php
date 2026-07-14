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

        $sortOrders = [
            'semrush' => 1,
            'semrush_site_audit' => 2,
            'ahrefs' => 3,
            'ahrefs_bar' => 4,
        ];

        $activeSlugs = [];

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
                    'sort_order' => $sortOrders[$slug] ?? 99,
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
                    $activeSlugs[] = $slugKey;

                    $defaults = [
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
                    ];

                    $server = ToolAccessServer::where('slug', $slugKey)->first();

                    if ($server) {
                        // Keep admin-edited connection settings; sync layout + group placement.
                        $server->update([
                            'tool_access_group_id' => $defaults['tool_access_group_id'],
                            'label' => $defaults['label'],
                            'section_title' => $defaults['section_title'],
                            'sort_order' => $defaults['sort_order'],
                            'is_active' => true,
                        ]);
                    } else {
                        ToolAccessServer::create(array_merge(['slug' => $slugKey], $defaults));
                    }
                }
            }
        }

        // Hide Export Only buttons for now (any leftover direct export links).
        ToolAccessServer::query()
            ->where(function ($q) {
                $q->where('section_title', 'For Export only')
                    ->orWhere('label', 'like', '%Export Only%')
                    ->orWhere('label', 'like', '%Export only%');
            })
            ->update(['is_active' => false]);

        // Hide Site Audit servers that are not part of the current Site Audit hub layout.
        ToolAccessServer::query()
            ->whereIn('slug', ['nnxsite3', 'ntbsite1'])
            ->whereNotIn('slug', $activeSlugs)
            ->update(['is_active' => false]);

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
                    'sort_order' => $sortOrders['ahrefs_bar'],
                    'is_active' => true,
                ]
            );
        }
    }
}
