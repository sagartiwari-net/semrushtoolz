<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('taxable_amount', 10, 2)->nullable()->after('referral_bonus_percent');
            $table->decimal('gst_rate', 5, 2)->nullable()->after('taxable_amount');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['taxable_amount', 'gst_rate', 'gst_amount']);
        });
    }
};
