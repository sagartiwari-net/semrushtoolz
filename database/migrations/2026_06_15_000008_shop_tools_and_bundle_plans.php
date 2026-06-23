<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->boolean('show_in_shop')->default(true)->after('is_active');
            $table->json('shop_features')->nullable()->after('show_in_shop');
            $table->string('shop_group')->nullable()->after('shop_features');
            $table->string('grants_tool_slug')->nullable()->after('shop_group');
            $table->string('shop_badge')->nullable()->after('grants_tool_slug');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_bundle')->default(false)->after('show_on_homepage');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
            $table->foreignId('tool_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
            $table->foreignId('tool_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropColumn('tool_id');
            $table->dropForeign(['plan_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropColumn('tool_id');
            $table->dropForeign(['plan_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_bundle');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn(['show_in_shop', 'shop_features', 'shop_group', 'grants_tool_slug', 'shop_badge']);
        });
    }
};
