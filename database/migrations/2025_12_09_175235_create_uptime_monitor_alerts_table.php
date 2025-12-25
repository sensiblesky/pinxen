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
        Schema::create('uptime_monitor_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uptime_monitor_id')->constrained('monitors_service_uptime')->onDelete('cascade');
            $table->enum('alert_type', ['down', 'up', 'recovery'])->default('down');
            $table->text('message');
            $table->enum('communication_channel', ['email', 'sms', 'whatsapp', 'telegram', 'discord']);
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['uptime_monitor_id', 'status']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uptime_monitor_alerts');
    }
};
