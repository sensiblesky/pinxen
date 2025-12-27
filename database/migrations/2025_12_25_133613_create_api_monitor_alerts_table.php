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
        if (Schema::hasTable('api_monitor_alerts')) {
            return;
        }
        
        Schema::create('api_monitor_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_monitor_id')->constrained('api_monitors')->onDelete('cascade');
            $table->enum('alert_type', ['down', 'up', 'latency', 'validation_failed', 'status_code_mismatch', 'auth_failed']);
            $table->text('message');
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['api_monitor_id', 'created_at']);
            $table->index('is_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_monitor_alerts');
    }
};
