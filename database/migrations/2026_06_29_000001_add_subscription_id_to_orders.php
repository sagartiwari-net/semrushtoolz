<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('tool_id')->constrained()->nullOnDelete();
        });

        DB::table('orders')
            ->where('status', 'completed')
            ->whereNull('subscription_id')
            ->orderBy('id')
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $subQuery = DB::table('subscriptions')->where('user_id', $order->user_id);

                    if ($order->paypal_subscription_id) {
                        $subQuery->where('paypal_subscription_id', $order->paypal_subscription_id);
                    } elseif ($order->plan_id) {
                        $subQuery->where('plan_id', $order->plan_id);
                    } elseif ($order->tool_id) {
                        $subQuery->where('tool_id', $order->tool_id);
                    } else {
                        continue;
                    }

                    $sub = $subQuery->orderByDesc('created_at')->first();

                    if ($sub) {
                        DB::table('orders')->where('id', $order->id)->update(['subscription_id' => $sub->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });
    }
};
