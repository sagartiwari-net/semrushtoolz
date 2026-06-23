<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('is_bundle');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_days')->nullable()->after('duration_months');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_days')->nullable()->after('duration_months');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_trial');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
