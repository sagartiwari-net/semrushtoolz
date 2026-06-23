<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'john@example.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role' => 'user',
                'referral_code' => 'JOHN2026',
                'status' => 'active',
                'email_verified_at' => now(),
                'last_login_otp_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@semrushtoolz.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'last_login_otp_at' => now(),
            ]
        );
    }
}
