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
        Schema::create('uptime_monitor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uptime_monitor_id')->constrained('monitors_service_uptime')->onDelete('cascade');
            $table->enum('status', ['up', 'down']);
            $table->integer('response_time')->nullable(); // Milliseconds
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['uptime_monitor_id', 'checked_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uptime_monitor_checks');
    }
};
