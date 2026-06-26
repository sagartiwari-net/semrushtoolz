<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_login_logs', function (Blueprint $table) {
            $table->string('device_fingerprint', 64)->nullable()->after('platform');
            $table->index(['user_id', 'device_fingerprint', 'logged_at'], 'user_login_logs_user_fp_logged');
        });
    }

    public function down(): void
    {
        Schema::table('user_login_logs', function (Blueprint $table) {
            $table->dropIndex('user_login_logs_user_fp_logged');
            $table->dropColumn('device_fingerprint');
        });
    }
};
