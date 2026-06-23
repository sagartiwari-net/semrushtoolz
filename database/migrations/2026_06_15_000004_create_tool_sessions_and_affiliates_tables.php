<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloud_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('tool');
            $table->string('label');
            $table->string('proxy_slug')->nullable();
            $table->unsignedSmallInteger('seat_limit')->default(10);
            $table->boolean('is_active')->default(true);
            $table->string('health_status')->default('unknown');
            $table->timestamp('last_health_check')->nullable();
            $table->timestamps();
        });

        Schema::create('tool_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tool');
            $table->foreignId('cloud_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('proxy_session_id')->nullable();
            $table->string('status')->default('active');
            $table->string('ip_address', 45)->nullable();
            $table->string('access_url', 2048)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tool', 'status']);
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('rate', 5, 2);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('tool_sessions');
        Schema::dropIfExists('cloud_accounts');
    }
};
