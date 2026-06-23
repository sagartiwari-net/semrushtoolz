<?php

return [
    'durations' => [
        1 => ['months' => 1, 'label' => '1 Month', 'discount' => 0],
        3 => ['months' => 3, 'label' => '3 Months', 'discount' => 0.07],
        6 => ['months' => 6, 'label' => '6 Months', 'discount' => 0.10],
        12 => ['months' => 12, 'label' => '12 Months', 'discount' => 0.20],
    ],

    'main_plans' => [
        [
            'id' => 'semrush',
            'name' => 'Semrush',
            'tagline' => 'Keyword & domain analysis',
            'price_inr' => 149,
            'price_usd' => 3,
            'logo' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
            'logo_alt' => 'Semrush',
            'featured' => false,
            'features' => [
                'Unlimited Keyword Analysis',
                'Unlimited Domain Analysis',
                'Export Feature',
                'One-Click Cloud Access',
            ],
        ],
        [
            'id' => 'semrush_site_audit',
            'name' => 'Semrush With Site Audit',
            'tagline' => 'Full Semrush + site audit',
            'price_inr' => 499,
            'price_usd' => 8,
            'logo' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
            'logo_alt' => 'Semrush',
            'featured' => false,
            'features' => [
                'Unlimited Keyword Analysis',
                'Unlimited Domain Analysis',
                'Export Feature',
                ['text' => 'Site Audit', 'marker' => '*'],
                'One-Click Cloud Access',
            ],
        ],
        [
            'id' => 'combo',
            'name' => 'Semrush + Ahrefs Combo',
            'tagline' => 'Best value — everything included',
            'price_inr' => 799,
            'price_usd' => 12,
            'logos' => [
                ['src' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768', 'alt' => 'Semrush'],
                ['src' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094', 'alt' => 'Ahrefs'],
            ],
            'featured' => true,
            'badge' => 'Best Value',
            'features' => [
                'Semrush Full Access',
                'Ahrefs Plan 1 Included',
                'Ahrefs Bar Extension',
                ['text' => 'Bonus Tools', 'marker' => '**'],
                'One-Click Cloud Access',
            ],
        ],
        [
            'id' => 'ahrefs_plan_1',
            'name' => 'Ahrefs Plan 1',
            'tagline' => 'Essential Ahrefs access',
            'price_inr' => 699,
            'price_usd' => 10,
            'logo' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
            'logo_alt' => 'Ahrefs',
            'featured' => false,
            'features' => [
                '30 Credits per Day',
                '10K Weekly Export',
                'Keyword Explorer',
                'Site Explorer',
                'One-Click Cloud Access',
            ],
        ],
    ],

    'ahrefs_plans' => [
        [
            'id' => 'ahrefs_plan_1',
            'name' => 'Ahrefs Plan 1',
            'tagline' => 'Starter Ahrefs access',
            'price_inr' => 699,
            'price_usd' => 10,
            'credits' => '30/day',
            'export' => '10K/week',
            'features' => [
                '30 Credits per Day',
                '10K Weekly Export',
                'Keyword Explorer',
                'Site Explorer',
            ],
        ],
        [
            'id' => 'ahrefs_plan_2',
            'name' => 'Ahrefs Plan 2',
            'tagline' => 'More credits & exports',
            'price_inr' => 899,
            'price_usd' => 14,
            'credits' => '50/day',
            'export' => '15K/week',
            'features' => [
                '50 Credits per Day',
                '15K Weekly Export',
                'Keyword Explorer',
                'Site Explorer',
            ],
        ],
        [
            'id' => 'ahrefs_plan_3',
            'name' => 'Ahrefs Plan 3',
            'tagline' => 'Power user access',
            'price_inr' => 1399,
            'price_usd' => 20,
            'credits' => '100/day',
            'export' => '25K/week',
            'featured' => true,
            'badge' => 'Popular',
            'features' => [
                '100 Credits per Day',
                '25K Weekly Export',
                'Keyword Explorer',
                'Site Explorer',
            ],
        ],
        [
            'id' => 'ahrefs_plan_4',
            'name' => 'Ahrefs Plan 4',
            'tagline' => 'Maximum Ahrefs limits',
            'price_inr' => 2499,
            'price_usd' => 30,
            'credits' => '200/day',
            'export' => '30K/week',
            'features' => [
                '200 Credits per Day',
                '30K Weekly Export',
                'Keyword Explorer',
                'Site Explorer',
            ],
        ],
    ],

    'payment_methods' => [
        'inr' => [
            ['id' => 'upi', 'name' => 'UPI', 'desc' => 'Secure QR payment — auto-verified in ~30 seconds (5 min window)'],
            ['id' => 'offline', 'name' => 'Offline', 'desc' => 'QR / Binance / WhatsApp'],
        ],
        'usd' => [
            ['id' => 'paypal', 'name' => 'PayPal', 'desc' => 'USD monthly recurring — auto-renews until cancelled'],
            ['id' => 'offline', 'name' => 'Offline', 'desc' => 'Binance / WhatsApp contact'],
        ],
    ],

    /** Trial combo — fixed prices, no duration discounts, coupons, or wallet */
    'trial_durations' => [
        1 => ['days' => 1, 'label' => '1 Day', 'price_inr' => 100, 'price_usd' => 1.5],
        3 => ['days' => 3, 'label' => '3 Days', 'price_inr' => 140, 'price_usd' => 2.5],
        5 => ['days' => 5, 'label' => '5 Days', 'price_inr' => 180, 'price_usd' => 3.5],
    ],

    'trial_plan_features' => [
        'Semrush Full Access',
        'Ahrefs Plan 1 Included',
        ['text' => 'Bonus Tools', 'marker' => '**'],
        'One-Click Cloud Access',
    ],
];
