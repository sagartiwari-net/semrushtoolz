<?php

namespace Database\Seeders;

use App\Models\EmailPreset;
use Illuminate\Database\Seeder;

class EmailPresetSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = require __DIR__.'/data/email_preset_defaults.php';
        $htmlBodies = require __DIR__.'/data/email_html.php';

        foreach ($defaults as $preset) {
            if (isset($htmlBodies[$preset['key']])) {
                $preset['html_body'] = $htmlBodies[$preset['key']];
            }

            EmailPreset::query()->updateOrCreate(
                ['key' => $preset['key']],
                [
                    'name' => $preset['name'],
                    'category' => $preset['category'],
                    'slug' => EmailPreset::slugFromKey($preset['key']),
                    'subject' => $preset['subject'],
                    'html_body' => $preset['html_body'],
                    'text_body' => $preset['text_body'] ?? null,
                    'type' => $preset['type'] ?? EmailPreset::TYPE_TRANSACTIONAL,
                    'is_enabled' => true,
                    'is_system' => true,
                    'sort_order' => $preset['sort_order'] ?? 0,
                    'description' => $preset['description'] ?? null,
                    'variables_help' => $preset['variables_help'] ?? null,
                ],
            );
        }
    }
}
