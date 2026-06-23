<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->string('access_type')->default('cloud')->after('logo_url');
            $table->unsignedInteger('price_inr')->nullable()->after('access_type');
            $table->unsignedInteger('price_usd')->nullable()->after('price_inr');
            $table->json('prices')->nullable()->after('price_usd');
            $table->string('whatsapp_number', 30)->nullable()->after('prices');
            $table->string('whatsapp_message')->nullable()->after('whatsapp_number');
            $table->string('official_url', 2048)->nullable()->after('whatsapp_message');
            $table->string('extension_download_url', 2048)->nullable()->after('official_url');
        });

        Schema::create('tool_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('username');
            $table->text('password');
            $table->string('official_url', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('tool_access_servers', function (Blueprint $table) {
            $table->string('extension_tool_key')->nullable()->after('direct_url');
        });
    }

    public function down(): void
    {
        Schema::table('tool_access_servers', function (Blueprint $table) {
            $table->dropColumn('extension_tool_key');
        });

        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('tool_credentials');

        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn([
                'access_type', 'price_inr', 'price_usd', 'prices',
                'whatsapp_number', 'whatsapp_message', 'official_url', 'extension_download_url',
            ]);
        });
    }
};
