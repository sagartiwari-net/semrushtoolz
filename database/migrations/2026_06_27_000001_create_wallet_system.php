<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 12, 2)->default(0)->after('preferences');
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('currency', 3)->default('inr');
            $table->string('description')->nullable();
            $table->nullableMorphs('reference');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type', 30)->default('subscription')->after('order_number');
            $table->decimal('wallet_amount_used', 10, 2)->default(0)->after('gst_amount');
            $table->decimal('wallet_cashback_amount', 10, 2)->default(0)->after('wallet_amount_used');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'wallet_amount_used', 'wallet_cashback_amount']);
        });

        Schema::dropIfExists('wallet_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wallet_balance');
        });
    }
};
