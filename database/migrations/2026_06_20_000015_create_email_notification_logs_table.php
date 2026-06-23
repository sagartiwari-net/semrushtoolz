<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('preset_key', 80);
            $table->string('reference_type', 40);
            $table->unsignedBigInteger('reference_id');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'preset_key', 'reference_type', 'reference_id'], 'email_notify_unique');
            $table->index(['preset_key', 'reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notification_logs');
    }
};
