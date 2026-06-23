<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->json('amember_product_ids')->nullable()->after('sort_order');
        });

        Schema::create('tool_access_groups', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('grant');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tool_access_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_access_group_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('type')->default('proxy');
            $table->string('domain')->nullable();
            $table->unsignedInteger('website_id')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('direct_url', 2048)->nullable();
            $table->string('section_title')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_access_servers');
        Schema::dropIfExists('tool_access_groups');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('amember_product_ids');
        });
    }
};
