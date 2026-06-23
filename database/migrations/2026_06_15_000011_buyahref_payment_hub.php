<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('hub_order_id', 64)->nullable()->after('payment_method');
            $table->string('hub_payment_url', 512)->nullable()->after('hub_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['hub_order_id', 'hub_payment_url']);
        });
    }
};
