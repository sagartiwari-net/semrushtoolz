<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('referral_bonus_expires_at')->nullable()->after('referred_by_user_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('referral_bonus_discount', 10, 2)->default(0)->after('coupon_discount');
            $table->unsignedTinyInteger('referral_bonus_percent')->nullable()->after('referral_bonus_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['referral_bonus_discount', 'referral_bonus_percent']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_bonus_expires_at');
        });
    }
};
