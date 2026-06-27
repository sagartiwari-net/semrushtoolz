<?php

/**
 * Tool access endpoints — mirrors aMember route_tool.php $toolsConfig.
 * Hub pages read from "groups"; handshake reads from "endpoints".
 *
 * username_field: which User column is sent as "username" to Go proxy (aMember used login)
 * plan_product_ids: map Laravel plan slug → aMember product IDs for handshake payload
 */

$semrushSecret = env('TOOL_SECRET_SEMRUSH', 'toolsmandi_recloudsemrush_secret_xyz123');

return [
    'username_field' => env('TOOL_HANDSHAKE_USERNAME_FIELD', 'email'),

    'plan_product_ids' => [
        'semrush' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_SEMRUSH', '1'))),
        'semrush_site_audit' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_SEMRUSH_SITE', '2'))),
        'combo' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_COMBO', '1,2,3,4,5,6,7,22,29,11'))),
        'combo_trial' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_COMBO_TRIAL', '1,2,3,4,5,6,7,22,29,11'))),
        'ahrefs_plan_1' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_AHREFS_1', '4'))),
        'ahrefs_plan_2' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_AHREFS_2', '5'))),
        'ahrefs_plan_3' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_AHREFS_3', '6'))),
        'ahrefs_plan_4' => array_map('intval', explode(',', env('PLAN_PRODUCT_IDS_AHREFS_4', '7'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Hub Pages (dashboard shows 1 button → opens this page)
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'semrush' => [
            'title' => 'Semrush Access',
            'subtitle' => 'Choose a server below. If one is busy, try another.',
            'grant' => 'semrush',
            'logo' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
            'sections' => [
                [
                    'title' => null,
                    'buttons' => [
                        ['type' => 'proxy', 'slug' => 'nnxsm1', 'label' => 'Access Semrush 1'],
                        ['type' => 'proxy', 'slug' => 'nnxsm2', 'label' => 'Access Semrush 2'],
                        ['type' => 'proxy', 'slug' => 'nnxsm3', 'label' => 'Access Semrush 3'],
                        ['type' => 'proxy', 'slug' => 'nnxsm4', 'label' => 'Access Semrush 4'],
                        ['type' => 'proxy', 'slug' => 'nnxsm5', 'label' => 'Access Semrush 5'],
                        ['type' => 'proxy', 'slug' => 'nnxsm6', 'label' => 'Access Semrush 6'],
                        ['type' => 'proxy', 'slug' => 'nazsm1', 'label' => 'Access Semrush 7'],
                        ['type' => 'proxy', 'slug' => 'nazsm2', 'label' => 'Access Semrush 8'],
                        ['type' => 'proxy', 'slug' => 'nazsm3', 'label' => 'Access Semrush 9'],
                        ['type' => 'proxy', 'slug' => 'ntbsm1', 'label' => 'Access Semrush 10'],
                        ['type' => 'proxy', 'slug' => 'ntbsm2', 'label' => 'Access Semrush 11'],
                    ],
                ],
                [
                    'title' => 'For Export only',
                    'buttons' => [
                        [
                            'type' => 'direct',
                            'url' => env('TOOL_EXPORT_URL_SEMRUSH', 'https://6.semrush.com.in/analytics/overview/?searchType=domain'),
                            'label' => 'Access Export Only',
                        ],
                    ],
                ],
            ],
        ],

        'ahrefs' => [
            'title' => 'Ahrefs Access',
            'subtitle' => 'Choose a server below. If one is busy, try another.',
            'grant' => 'ahrefs',
            'logo' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
            'sections' => [
                [
                    'title' => null,
                    'buttons' => [
                        ['type' => 'proxy', 'slug' => 'ahrefs', 'label' => 'Access Ahrefs'],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Go Proxy Endpoints (same as route_tool.php $toolsConfig)
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'ahrefs' => [
            'group' => 'ahrefs',
            'website_id' => (int) env('TOOL_WEBSITE_ID_AHREFS', 7),
            'domain' => env('TOOL_DOMAIN_AHREFS', 'ct.toolsmandi.com'),
            'secret_key' => env('TOOL_SECRET_AHREFS', 'toolsmandi_ahrefs_secret_xyz123'),
        ],
        'nnxsm1' => ['group' => 'semrush', 'website_id' => 10, 'domain' => 'nnxsm1.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsm2' => ['group' => 'semrush', 'website_id' => 11, 'domain' => 'nnxsm2.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsm3' => ['group' => 'semrush', 'website_id' => 12, 'domain' => 'nnxsm3.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsm4' => ['group' => 'semrush', 'website_id' => 13, 'domain' => 'nnxsm4.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsm5' => ['group' => 'semrush', 'website_id' => 14, 'domain' => 'nnxsm5.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsm6' => ['group' => 'semrush', 'website_id' => 15, 'domain' => 'nnxsm6.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsite1' => ['group' => 'semrush', 'website_id' => 16, 'domain' => 'nnxsite1.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsite2' => ['group' => 'semrush', 'website_id' => 17, 'domain' => 'nnxsite3.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nnxsite3' => ['group' => 'semrush', 'website_id' => 18, 'domain' => 'nnxsite3.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nazsm1' => ['group' => 'semrush', 'website_id' => 20, 'domain' => 'nazsm1.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nazsm2' => ['group' => 'semrush', 'website_id' => 21, 'domain' => 'nazsm2.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nazsm3' => ['group' => 'semrush', 'website_id' => 22, 'domain' => 'nazsm3.1clkaccess.store', 'secret_key' => $semrushSecret],
        'nazsite1' => ['group' => 'semrush', 'website_id' => 25, 'domain' => 'nazsite1.1clkaccess.store', 'secret_key' => $semrushSecret],
        'ntbsm1' => ['group' => 'semrush', 'website_id' => 26, 'domain' => 'ntbsm1.1clkaccess.store', 'secret_key' => $semrushSecret],
        'ntbsm2' => ['group' => 'semrush', 'website_id' => 28, 'domain' => 'ntbsm2.1clkaccess.store', 'secret_key' => $semrushSecret],
        'ntbsite1' => ['group' => 'semrush', 'website_id' => 29, 'domain' => 'ntbsite1.1clkaccess.store', 'secret_key' => $semrushSecret],
        'semrush' => [
            'group' => 'semrush',
            'website_id' => 49,
            'domain' => 'seosite.1clkaccess.store',
            'secret_key' => env('TOOL_SECRET_SEMRUSH_SITE', 'toolsmandi_seosite_secret_xyz123'),
        ],
    ],
];
