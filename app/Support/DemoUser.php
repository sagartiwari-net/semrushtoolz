<?php

namespace App\Support;

class DemoUser
{
    public static function current(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'initials' => 'JD',
            'role_label' => 'Combo Plan',
            'plan' => 'Combo',
            'plan_status' => 'Active',
            'expires_at' => 'Dec 15, 2026',
            'days_remaining' => 45,
            'referral_code' => 'JOHN2026',
        ];
    }

    public static function notifications(): array
    {
        return [
            ['type' => 'promo', 'title' => 'Save 20% on 12-month plans', 'time' => '2 hours ago', 'unread' => true],
            ['type' => 'warn', 'title' => 'Your plan expires in 45 days', 'time' => '2 days ago', 'unread' => true],
            ['type' => 'info', 'title' => 'New Ahrefs Plan 3 now available', 'time' => '3 days ago', 'unread' => false],
        ];
    }

    public static function tools(): array
    {
        return [
            [
                'id' => 'semrush',
                'name' => 'Semrush',
                'desc' => 'Keyword research & domain analysis',
                'logo' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
                'status' => 'online',
                'active' => true,
                'featured' => true,
                'seats' => '4/12',
                'last_accessed' => 'Today at 2:30 PM',
            ],
            [
                'id' => 'ahrefs',
                'name' => 'Ahrefs',
                'desc' => 'Backlink & keyword explorer',
                'logo' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'status' => 'online',
                'active' => true,
                'featured' => false,
                'seats' => '7/15',
                'last_accessed' => 'Yesterday at 4:45 PM',
            ],
            [
                'id' => 'ahrefs_bar',
                'name' => 'Ahrefs Bar',
                'desc' => 'Browser extension access',
                'logo' => 'https://ik.imagekit.io/webfiles/ahrefs.avif?updatedAt=1753593854334',
                'status' => 'extension',
                'active' => false,
                'featured' => false,
                'seats' => null,
                'last_accessed' => null,
            ],
        ];
    }

    public static function activity(): array
    {
        return [
            ['title' => 'Accessed Semrush', 'meta' => 'Today at 2:30 PM', 'color' => 'accent'],
            ['title' => 'Login from new device', 'meta' => 'Yesterday at 9:15 AM · Windows PC – Chrome', 'color' => 'success'],
            ['title' => 'Used Ahrefs for keyword research', 'meta' => '2 days ago at 4:45 PM', 'color' => 'blue'],
        ];
    }

    public static function orders(): array
    {
        return [
            ['id' => 'ORD-1042', 'plan' => 'Combo Plan', 'amount' => '₹7,670', 'method' => 'UPI', 'status' => 'Completed', 'date' => 'Jun 1, 2026'],
            ['id' => 'ORD-0988', 'plan' => 'Semrush Basic', 'amount' => '₹149', 'method' => 'PayPal', 'status' => 'Completed', 'date' => 'May 10, 2026'],
            ['id' => 'ORD-1105', 'plan' => 'Ahrefs Plan 2', 'amount' => '₹899', 'method' => 'Offline', 'status' => 'Pending', 'date' => 'Jun 14, 2026'],
        ];
    }
}
