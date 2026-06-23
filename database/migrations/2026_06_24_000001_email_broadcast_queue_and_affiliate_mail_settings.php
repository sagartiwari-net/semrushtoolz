<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_broadcast_queue', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id', 36);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('preset_key', 80);
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcast_queue');
    }
};
