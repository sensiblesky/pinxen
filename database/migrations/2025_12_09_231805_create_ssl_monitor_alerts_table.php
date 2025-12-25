<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ssl_monitor_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssl_monitor_id')->constrained('ssl_monitors')->onDelete('cascade');
            $table->enum('alert_type', ['expiring_soon', 'expired', 'invalid', 'recovered'])->default('expired');
            $table->text('message');
            $table->enum('communication_channel', ['email', 'sms', 'whatsapp', 'telegram', 'discord'])->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['ssl_monitor_id', 'alert_type']);
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_monitor_alerts');
    }
};
