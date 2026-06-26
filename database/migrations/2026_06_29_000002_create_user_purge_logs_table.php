<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_purge_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('reason', 64);
            $table->string('triggered_by', 32)->default('cron');
            $table->timestamp('purged_at');
            $table->timestamps();

            $table->index('purged_at');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_purge_logs');
    }
};
