<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tool;
use App\Models\ToolAccessGroup;
use App\Models\ToolAccessServer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class BonusToolsSeeder extends Seeder
{
    private const SECRET = 'toolsmandi_recloud_secret_xyz123';

    public function run(): void
    {
        $tools = [
            ['slug' => 'ubersuggest', 'name' => 'Ubersuggest', 'description' => 'Keyword research & SEO ideas', 'category' => 'seo', 'sort_order' => 30],
            ['slug' => 'similarweb', 'name' => 'Similarweb', 'description' => 'Website traffic & competitor insights', 'category' => 'seo', 'sort_order' => 31],
            ['slug' => 'writerzen', 'name' => 'WriterZen', 'description' => 'Content research & writing', 'category' => 'writing', 'sort_order' => 32],
            ['slug' => 'spyfu', 'name' => 'SpyFu', 'description' => 'Competitor keywords & ads', 'category' => 'seo', 'sort_order' => 33],
            ['slug' => 'kwfinder', 'name' => 'KWFinder', 'description' => 'Long-tail keyword research', 'category' => 'seo', 'sort_order' => 34],
            ['slug' => 'majestic', 'name' => 'Majestic', 'description' => 'Backlink intelligence', 'category' => 'seo', 'sort_order' => 35],
            ['slug' => 'searchatlas', 'name' => 'Search Atlas', 'description' => 'SEO platform & content tools', 'category' => 'seo', 'sort_order' => 36],
            ['slug' => 'seranking', 'name' => 'SE Ranking', 'description' => 'Rank tracking & SEO toolkit', 'category' => 'seo', 'sort_order' => 37],
            ['slug' => 'indexer', 'name' => 'Indexer', 'description' => 'URL indexing tools', 'category' => 'seo', 'sort_order' => 38],
            ['slug' => 'indexification', 'name' => 'Indexification', 'description' => 'Indexation management', 'category' => 'seo', 'sort_order' => 39],
            ['slug' => 'bkrepo', 'name' => 'Backlink Repo', 'description' => 'Backlink resources', 'category' => 'seo', 'sort_order' => 40],
            ['slug' => 'grammarly', 'name' => 'Grammarly', 'description' => 'Writing assistant', 'category' => 'writing', 'sort_order' => 41],
            ['slug' => 'vecteezy', 'name' => 'Vecteezy', 'description' => 'Vectors & design assets', 'category' => 'design', 'sort_order' => 42],
            ['slug' => 'flaticon', 'name' => 'Flaticon', 'description' => 'Icons & graphics', 'category' => 'design', 'sort_order' => 43],
            ['slug' => 'wordtune', 'name' => 'Wordtune', 'description' => 'AI writing & rewrite assistant', 'category' => 'writing', 'sort_order' => 44],
            ['slug' => 'writehuman', 'name' => 'WriteHuman', 'description' => 'Humanize AI content', 'category' => 'writing', 'sort_order' => 46],
            ['slug' => 'bypassgpt', 'name' => 'BypassGPT', 'description' => 'AI detector bypass', 'category' => 'ai', 'sort_order' => 47],
            ['slug' => 'hixbypass', 'name' => 'HixByPass', 'description' => 'AI content bypass', 'category' => 'ai', 'sort_order' => 48],
            ['slug' => 'tubemagic', 'name' => 'TubeMagic', 'description' => 'YouTube AI tools', 'category' => 'ai', 'sort_order' => 49],
            ['slug' => 'writecream', 'name' => 'Writecream', 'description' => 'AI copywriting', 'category' => 'writing', 'sort_order' => 50],
            ['slug' => 'vidlq', 'name' => 'VidIQ', 'description' => 'YouTube growth & analytics', 'category' => 'seo', 'sort_order' => 51],
            ['slug' => 'jasper', 'name' => 'Jasper', 'description' => 'AI writing assistant', 'category' => 'writing', 'sort_order' => 52],
            ['slug' => 'quillbot', 'name' => 'Quillbot', 'description' => 'Paraphrasing & grammar', 'category' => 'writing', 'sort_order' => 53],
            ['slug' => 'humanizer', 'name' => 'Humanizer', 'description' => 'Humanize AI text', 'category' => 'ai', 'sort_order' => 54],
            ['slug' => 'iconscout', 'name' => 'IconScout', 'description' => 'Icons & design assets downloader', 'category' => 'design', 'sort_order' => 55],
            ['slug' => 'wordai', 'name' => 'WordAi', 'description' => 'AI content rewriter', 'category' => 'writing', 'sort_order' => 56],
            ['slug' => 'keywordtoolz', 'name' => 'Keyword Toolz', 'description' => 'Keyword research toolkit', 'category' => 'seo', 'sort_order' => 57],
            ['slug' => 'invideo', 'name' => 'InVideo', 'description' => 'AI video creation', 'category' => 'ai', 'sort_order' => 58],
            ['slug' => 'answerthepublic', 'name' => 'Answer The Public', 'description' => 'Search listening & keyword ideas', 'category' => 'seo', 'sort_order' => 59],
            ['slug' => 'seotester', 'name' => 'SEO Tester', 'description' => 'SEO analysis & testing', 'category' => 'seo', 'sort_order' => 60],
            ['slug' => 'sellthetrend', 'name' => 'Sell The Trend', 'description' => 'Dropshipping & product research', 'category' => 'seo', 'sort_order' => 61],
            ['slug' => 'smodin', 'name' => 'Smodin', 'description' => 'AI writing & paraphrasing', 'category' => 'writing', 'sort_order' => 62],
        ];

        $childSlugs = [];

        foreach ($tools as $item) {
            $childSlugs[] = $item['slug'];

            Tool::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'access_type' => Tool::ACCESS_CLOUD,
                    'category' => $item['category'],
                    'price_inr' => 0,
                    'price_usd' => 0,
                    'show_in_shop' => false,
                    'is_active' => true,
                    'is_extension' => false,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }

        Tool::updateOrCreate(
            ['slug' => 'bonus'],
            [
                'name' => 'Bonus Tools',
                'description' => 'Extra SEO, writing & design tools included with combo',
                'access_type' => Tool::ACCESS_CLOUD,
                'category' => 'seo',
                'price_inr' => 0,
                'price_usd' => 0,
                'show_in_shop' => false,
                'is_active' => true,
                'is_extension' => false,
                'sort_order' => 29,
                'grants_tool_slugs' => $childSlugs,
                'shop_features' => ['Ubersuggest', 'Similarweb', 'SpyFu', 'KWFinder', 'And more bonus tools'],
            ]
        );

        $servers = [
            ['tool' => 'ubersuggest', 'slug' => 'tzuber', 'label' => 'Ubersuggest 1', 'website_id' => 66, 'domain' => 'tzuber1.1clkaccess.store'],
            ['tool' => 'ubersuggest', 'slug' => 'nxuber1', 'label' => 'Ubersuggest 2', 'website_id' => 55, 'domain' => 'nxuber1.1clkaccess.store'],
            ['tool' => 'ubersuggest', 'slug' => 'nxuber2', 'label' => 'Ubersuggest 3', 'website_id' => 56, 'domain' => 'nxuber2.1clkaccess.store'],
            ['tool' => 'ubersuggest', 'slug' => 'nxuber3', 'label' => 'Ubersuggest 4', 'website_id' => 57, 'domain' => 'nxuber3.1clkaccess.store'],
            ['tool' => 'similarweb', 'slug' => 'tzsimillar', 'label' => 'Access Similarweb', 'website_id' => 67, 'domain' => 'tzsimillar.1clkaccess.store'],
            ['tool' => 'writerzen', 'slug' => 'tzwriterzen', 'label' => 'Access WriterZen', 'website_id' => 68, 'domain' => 'tzwriterzen.1clkaccess.store'],
            ['tool' => 'spyfu', 'slug' => 'tzspyfu', 'label' => 'Access SpyFu', 'website_id' => 69, 'domain' => 'tzspyfu.1clkaccess.store'],
            ['tool' => 'kwfinder', 'slug' => 'tzkwfind1', 'label' => 'KWFinder 1', 'website_id' => 70, 'domain' => 'tzkwfind1.1clkaccess.store'],
            ['tool' => 'kwfinder', 'slug' => 'tzkwfind2', 'label' => 'KWFinder 2', 'website_id' => 71, 'domain' => 'tzkwfind2.1clkaccess.store'],
            ['tool' => 'majestic', 'slug' => 'tzmaj', 'label' => 'Access Majestic', 'website_id' => 72, 'domain' => 'tzmaj.1clkaccess.store'],
            ['tool' => 'searchatlas', 'slug' => 'tzsearchatlas', 'label' => 'Access Search Atlas', 'website_id' => 73, 'domain' => 'tzsearchatlas.1clkaccess.store'],
            ['tool' => 'seranking', 'slug' => 'tzseranking', 'label' => 'SE Ranking 1', 'website_id' => 74, 'domain' => 'tzseranking.1clkaccess.store'],
            ['tool' => 'seranking', 'slug' => 'nxserank', 'label' => 'SE Ranking 2', 'website_id' => 100, 'domain' => 'nxserank.1clkaccess.store'],
            ['tool' => 'indexer', 'slug' => 'tzindexer', 'label' => 'Access Indexer', 'website_id' => 75, 'domain' => 'tzindexer.1clkaccess.store'],
            ['tool' => 'indexification', 'slug' => 'tzindexfication', 'label' => 'Indexification 1', 'website_id' => 76, 'domain' => 'tzindexfication.1clkaccess.store'],
            ['tool' => 'indexification', 'slug' => 'nxindex1', 'label' => 'Indexification 2', 'website_id' => 58, 'domain' => 'nxindex1.1clkaccess.store'],
            ['tool' => 'bkrepo', 'slug' => 'tzbkrepo', 'label' => 'Access Backlink Repo', 'website_id' => 77, 'domain' => 'tzbkrepo.1clkaccess.store'],
            ['tool' => 'grammarly', 'slug' => 'tzgram1', 'label' => 'Grammarly 1', 'website_id' => 78, 'domain' => 'tzgram1.1clkaccess.store'],
            ['tool' => 'grammarly', 'slug' => 'nxgram', 'label' => 'Grammarly 2', 'website_id' => 53, 'domain' => 'nxgram.1clkaccess.store'],
            ['tool' => 'vecteezy', 'slug' => 'tzvec', 'label' => 'Access Vecteezy', 'website_id' => 79, 'domain' => 'tzvec.1clkaccess.store'],
            ['tool' => 'flaticon', 'slug' => 'tzflaticon', 'label' => 'Flaticon 1', 'website_id' => 80, 'domain' => 'tzflaticon.1clkaccess.store'],
            ['tool' => 'flaticon', 'slug' => 'nxflaticon', 'label' => 'Flaticon 2', 'website_id' => 98, 'domain' => 'nxflaticon.1clkaccess.store'],
            ['tool' => 'wordtune', 'slug' => 'tztuneo', 'label' => 'Wordtune 1', 'website_id' => 81, 'domain' => 'tztuneo.1clkaccess.store'],
            ['tool' => 'wordtune', 'slug' => 'tztunet', 'label' => 'Wordtune 2', 'website_id' => 82, 'domain' => 'tztunet.1clkaccess.store'],
            ['tool' => 'writehuman', 'slug' => 'tzwritehuman', 'label' => 'Access WriteHuman', 'website_id' => 83, 'domain' => 'tzwritehuman.1clkaccess.store'],
            ['tool' => 'bypassgpt', 'slug' => 'tzbypass', 'label' => 'Access BypassGPT', 'website_id' => 84, 'domain' => 'tzbypass.1clkaccess.store'],
            ['tool' => 'hixbypass', 'slug' => 'tzhixbypass', 'label' => 'Access HixByPass', 'website_id' => 85, 'domain' => 'tzhixbypass.1clkaccess.store'],
            ['tool' => 'tubemagic', 'slug' => 'tztube', 'label' => 'Access TubeMagic', 'website_id' => 86, 'domain' => 'tztube.1clkaccess.store'],
            ['tool' => 'writecream', 'slug' => 'tzcream', 'label' => 'Access Writecream', 'website_id' => 87, 'domain' => 'tzcream.1clkaccess.store'],
            ['tool' => 'vidlq', 'slug' => 'tzvidiq', 'label' => 'Access VidIQ', 'website_id' => 89, 'domain' => 'tzvidiq.1clkaccess.store'],
            ['tool' => 'jasper', 'slug' => 'tzjas', 'label' => 'Jasper 1', 'website_id' => 90, 'domain' => 'tzjas.1clkaccess.store'],
            ['tool' => 'jasper', 'slug' => 'tzjas2', 'label' => 'Jasper 2', 'website_id' => 91, 'domain' => 'tzjas2.1clkaccess.store'],
            ['tool' => 'quillbot', 'slug' => 'tzquill', 'label' => 'Quillbot 1', 'website_id' => 92, 'domain' => 'tzquill.1clkaccess.store'],
            ['tool' => 'quillbot', 'slug' => 'nxquill', 'label' => 'Quillbot 2', 'website_id' => 103, 'domain' => 'nxquill.1clkaccess.store'],
            ['tool' => 'quillbot', 'slug' => 'nxquilll', 'label' => 'Quillbot 3', 'website_id' => 104, 'domain' => 'nxquilll.1clkaccess.store'],
            ['tool' => 'humanizer', 'slug' => 'tzhuma', 'label' => 'Access Humanizer', 'website_id' => 94, 'domain' => 'tzhuma.1clkaccess.store'],
            ['tool' => 'iconscout', 'slug' => 'tzicon', 'label' => 'Access IconScout', 'website_id' => 95, 'domain' => 'tzicon.1clkaccess.store'],
            ['tool' => 'wordai', 'slug' => 'nxwordai', 'label' => 'Access WordAi', 'website_id' => 54, 'domain' => 'nxwordai.1clkaccess.store'],
            ['tool' => 'keywordtoolz', 'slug' => 'nxkeyword', 'label' => 'Access Keyword Toolz', 'website_id' => 96, 'domain' => 'nxkeyword.1clkaccess.store'],
            ['tool' => 'invideo', 'slug' => 'nxinvideo', 'label' => 'Access InVideo', 'website_id' => 99, 'domain' => 'nxinvideo.1clkaccess.store'],
            ['tool' => 'answerthepublic', 'slug' => 'nxansthepub', 'label' => 'Access Answer The Public', 'website_id' => 101, 'domain' => 'nxansthepub.1clkaccess.store'],
            ['tool' => 'seotester', 'slug' => 'nxseotester', 'label' => 'Access SEO Tester', 'website_id' => 102, 'domain' => 'nxseotester.1clkaccess.store'],
            ['tool' => 'sellthetrend', 'slug' => 'nxsellthetrend', 'label' => 'Access Sell The Trend', 'website_id' => 105, 'domain' => 'nxsellthetrend.1clkaccess.store'],
            ['tool' => 'smodin', 'slug' => 'tzsmodin', 'label' => 'Access Smodin', 'website_id' => 97, 'domain' => 'tzsmodin.1clkaccess.store'],
        ];

        $orderByTool = [];

        foreach ($servers as $server) {
            $tool = Tool::where('slug', $server['tool'])->first();
            if (! $tool) {
                continue;
            }

            $group = ToolAccessGroup::updateOrCreate(
                ['slug' => $tool->slug],
                [
                    'tool_id' => $tool->id,
                    'title' => $tool->name.' Access',
                    'subtitle' => 'Choose a server below. If one is busy, try another.',
                    'logo_url' => $tool->logo_url,
                    'grant' => $tool->slug,
                    'sort_order' => $tool->sort_order,
                    'is_active' => true,
                ]
            );

            $orderByTool[$tool->slug] = ($orderByTool[$tool->slug] ?? 0) + 1;

            ToolAccessServer::updateOrCreate(
                ['slug' => $server['slug']],
                [
                    'tool_access_group_id' => $group->id,
                    'label' => $server['label'],
                    'type' => 'proxy',
                    'domain' => $server['domain'],
                    'website_id' => $server['website_id'],
                    'secret_key' => self::SECRET,
                    'direct_url' => null,
                    'section_title' => null,
                    'sort_order' => $orderByTool[$tool->slug],
                    'is_active' => true,
                ]
            );
        }

        $this->removeLegacyTools(['tuneo', 'tunet']);
        $this->removeLegacyServers(['tzvidlq', 'tzquilll']);
        $this->fixMisconfiguredDomains();

        $bonus = Tool::where('slug', 'bonus')->first();
        $combo = Plan::where('slug', 'combo')->first();

        if ($bonus && $combo) {
            $ids = $combo->tools()->pluck('tools.id')->all();
            if (! in_array($bonus->id, $ids, true)) {
                $ids[] = $bonus->id;
            }
            $combo->tools()->sync($ids);
        }

        Cache::forget('tools.bonus_child_slugs');
    }

    /**
     * @param  array<int, string>  $slugs
     */
    protected function removeLegacyTools(array $slugs): void
    {
        foreach ($slugs as $slug) {
            $orphanGroup = ToolAccessGroup::where('slug', $slug)->first();
            if ($orphanGroup) {
                $orphanGroup->servers()->delete();
                $orphanGroup->delete();
            }

            $tool = Tool::where('slug', $slug)->first();
            if (! $tool) {
                continue;
            }

            $tool->load('accessGroup.servers');
            $tool->accessGroup?->servers()->delete();
            $tool->accessGroup?->delete();
            $tool->credentials()->delete();
            $tool->plans()->detach();
            $tool->articles()->update(['tool_id' => null]);
            $tool->delete();
        }

        Tool::query()
            ->whereNotNull('grants_tool_slugs')
            ->get()
            ->each(function (Tool $package) use ($slugs) {
                $updated = collect($package->grants_tool_slugs ?? [])
                    ->map(fn ($s) => Tool::resolveSlugAlias((string) $s))
                    ->reject(fn ($s) => in_array($s, $slugs, true))
                    ->unique()
                    ->values()
                    ->all();

                if ($updated !== ($package->grants_tool_slugs ?? [])) {
                    $package->update(['grants_tool_slugs' => $updated]);
                }
            });
    }

    /**
     * @param  array<int, string>  $slugs
     */
    protected function removeLegacyServers(array $slugs): void
    {
        foreach ($slugs as $slug) {
            ToolAccessServer::where('slug', $slug)->delete();
        }
    }

    protected function fixMisconfiguredDomains(): void
    {
        ToolAccessServer::query()
            ->where('domain', 'like', '%.lclkaccess.store')
            ->get()
            ->each(function (ToolAccessServer $server) {
                $server->update([
                    'domain' => str_replace('.lclkaccess.store', '.1clkaccess.store', $server->domain),
                ]);
            });
    }
}
