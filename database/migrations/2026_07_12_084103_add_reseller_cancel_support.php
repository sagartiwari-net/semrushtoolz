<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_profiles', function (Blueprint $table) {
            $table->unsignedInteger('monthly_cancel_limit')->nullable()->after('notes');
        });

        Schema::table('reseller_provisions', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('subscription_id');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_role', 20)->nullable()->after('cancelled_by_user_id');
            $table->decimal('refund_amount', 12, 2)->nullable()->after('cancelled_by_role');
            $table->index(['reseller_user_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reseller_provisions', function (Blueprint $table) {
            $table->dropIndex(['reseller_user_id', 'cancelled_at']);
            $table->dropConstrainedForeignId('cancelled_by_user_id');
            $table->dropColumn(['cancelled_at', 'cancelled_by_role', 'refund_amount']);
        });

        Schema::table('reseller_profiles', function (Blueprint $table) {
            $table->dropColumn('monthly_cancel_limit');
        });
    }
};
