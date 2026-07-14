<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('created_by_reseller_id')
                ->nullable()
                ->after('referred_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('reseller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('reseller_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance_inr', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('reseller_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40);
            $table->decimal('amount_inr', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['reseller_user_id', 'created_at']);
        });

        Schema::create('reseller_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_inr');
            $table->timestamps();
        });

        Schema::create('reseller_tool_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_inr');
            $table->timestamps();
            $table->unique(['reseller_user_id', 'tool_id']);
        });

        Schema::create('reseller_balance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount_inr', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('reseller_provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('end_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('end_user_email');
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('duration_months')->default(1);
            $table->decimal('amount_charged', 12, 2);
            $table->boolean('was_new_user')->default(false);
            $table->boolean('password_reset')->default(false);
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['reseller_user_id', 'created_at']);
            $table->index(['end_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_provisions');
        Schema::dropIfExists('reseller_balance_requests');
        Schema::dropIfExists('reseller_tool_prices');
        Schema::dropIfExists('reseller_prices');
        Schema::dropIfExists('reseller_ledger');
        Schema::dropIfExists('reseller_balances');
        Schema::dropIfExists('reseller_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_reseller_id');
        });
    }
};
