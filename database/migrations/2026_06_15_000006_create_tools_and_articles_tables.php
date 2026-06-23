<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('is_extension')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_tool', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->primary(['plan_id', 'tool_id']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('display_group')->default('main')->after('product_type');
            $table->boolean('show_on_homepage')->default(true)->after('is_active');
        });

        Schema::table('tool_access_groups', function (Blueprint $table) {
            $table->foreignId('tool_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url_path')->unique();
            $table->string('breadcrumb_label')->nullable();
            $table->string('seo_title');
            $table->text('seo_description');
            $table->string('seo_keywords')->nullable();
            $table->string('hero_heading');
            $table->text('hero_subtext')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_cta_label')->nullable();
            $table->string('hero_cta_url')->nullable();
            $table->string('hero_secondary_label')->nullable();
            $table->string('hero_secondary_url')->nullable();
            $table->foreignId('tool_id')->nullable()->constrained()->nullOnDelete();
            $table->json('plan_slugs')->nullable();
            $table->string('pricing_heading')->nullable();
            $table->text('pricing_subtext')->nullable();
            $table->boolean('show_pricing')->default(true);
            $table->json('content_blocks')->nullable();
            $table->json('faqs')->nullable();
            $table->json('features')->nullable();
            $table->json('steps')->nullable();
            $table->json('highlights')->nullable();
            $table->text('footer_cta_heading')->nullable();
            $table->text('footer_cta_subtext')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::table('tool_access_groups', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropColumn('tool_id');
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['display_group', 'show_on_homepage']);
        });
        Schema::dropIfExists('plan_tool');
        Schema::dropIfExists('tools');
    }
};
