<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            ToolSeeder::class,
            PlanSeeder::class,
            ToolAccessSeeder::class,
            ArticleSeeder::class,
            LegalPageSeeder::class,
            SiteSettingSeeder::class,
        ]);
    }
}
