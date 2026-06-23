<?php

use App\Models\Tool;
use App\Services\ToolSeoService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->string('thumbnail_url')->nullable()->after('logo_url');
            $table->string('seo_title')->nullable()->after('shop_badge');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('seo_keywords')->nullable()->after('seo_description');
        });

        Tool::query()->each(function (Tool $tool) {
            $updates = [];

            if (! $tool->thumbnail_url && $tool->logo_url) {
                $updates['thumbnail_url'] = $tool->logo_url;
            }

            if (! $tool->seo_title || ! $tool->seo_description) {
                $suggestions = ToolSeoService::suggestionsForTool($tool);
                $updates['seo_title'] = $tool->seo_title ?: $suggestions['seo_title'];
                $updates['seo_description'] = $tool->seo_description ?: $suggestions['seo_description'];
                $updates['seo_keywords'] = $tool->seo_keywords ?: $suggestions['seo_keywords'];
            }

            if ($updates !== []) {
                $tool->update($updates);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_url', 'seo_title', 'seo_description', 'seo_keywords']);
        });
    }
};
