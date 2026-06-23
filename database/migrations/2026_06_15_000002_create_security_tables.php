<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('route', 100)->nullable();
            $table->string('action', 50)->default('page_view');
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['user_id', 'logged_at']);
            $table->index(['user_id', 'ip_address']);
        });

        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('severity', 20)->default('high');
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('status');
            $table->string('block_reason')->nullable()->after('blocked_at');
            $table->foreignId('blocked_by')->nullable()->after('block_reason')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('security_alert_count')->default(0)->after('blocked_by');
            $table->timestamp('last_ip_check_at')->nullable()->after('security_alert_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['blocked_by']);
            $table->dropColumn(['blocked_at', 'block_reason', 'blocked_by', 'security_alert_count', 'last_ip_check_at']);
        });

        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('user_login_logs');
    }
};
