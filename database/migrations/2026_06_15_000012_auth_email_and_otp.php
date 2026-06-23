<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_otp_at')->nullable()->after('email_verified_at');
        });

        \Illuminate\Support\Facades\DB::table('users')
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => now(),
                'last_login_otp_at' => now(),
            ]);

        \Illuminate\Support\Facades\DB::table('users')
            ->whereNotNull('email_verified_at')
            ->whereNull('last_login_otp_at')
            ->update(['last_login_otp_at' => now()]);

        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_otp_at');
        });
    }
};
