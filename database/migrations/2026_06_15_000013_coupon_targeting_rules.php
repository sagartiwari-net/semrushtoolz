<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->json('allowed_plan_ids')->nullable()->after('description');
            $table->json('allowed_tool_ids')->nullable()->after('allowed_plan_ids');
            $table->json('allowed_duration_months')->nullable()->after('allowed_tool_ids');
            $table->json('required_plan_ids')->nullable()->after('allowed_duration_months');
            $table->json('required_tool_ids')->nullable()->after('required_plan_ids');
            $table->unsignedInteger('max_uses_per_user')->nullable()->after('max_uses');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_plan_ids',
                'allowed_tool_ids',
                'allowed_duration_months',
                'required_plan_ids',
                'required_tool_ids',
                'max_uses_per_user',
            ]);
        });
    }
};
