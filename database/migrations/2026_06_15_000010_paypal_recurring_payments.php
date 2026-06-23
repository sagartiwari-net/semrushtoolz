<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypal_billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedTinyInteger('duration_months');
            $table->decimal('amount_usd', 10, 2);
            $table->unsignedTinyInteger('total_cycles')->nullable();
            $table->string('paypal_plan_id')->unique();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'duration_months', 'amount_usd', 'total_cycles'], 'paypal_plan_entity_unique');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('paypal_subscription_id')->nullable()->after('payment_method');
            $table->string('paypal_billing_plan_id')->nullable()->after('paypal_subscription_id');
            $table->boolean('is_recurring')->default(false)->after('paypal_billing_plan_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('paypal_subscription_id')->nullable()->after('auto_renew');
            $table->timestamp('next_billing_at')->nullable()->after('paypal_subscription_id');
        });

        Schema::create('paypal_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('resource_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypal_webhook_events');
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['paypal_subscription_id', 'next_billing_at']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paypal_subscription_id', 'paypal_billing_plan_id', 'is_recurring']);
        });
        Schema::dropIfExists('paypal_billing_plans');
    }
};
