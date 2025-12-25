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
        Schema::create('dns_monitor_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_monitor_id')->constrained('dns_monitors')->onDelete('cascade');
            $table->enum('alert_type', ['changed', 'missing', 'error', 'recovered'])->default('changed');
            $table->string('record_type', 10)->nullable(); // A, AAAA, CNAME, etc.
            $table->text('message');
            $table->json('changed_records')->nullable(); // What changed
            $table->enum('communication_channel', ['email', 'sms', 'whatsapp', 'telegram', 'discord'])->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['dns_monitor_id', 'alert_type']);
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_monitor_alerts');
    }
};
