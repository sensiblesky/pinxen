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
        if (Schema::hasTable('api_monitor_checks')) {
            return;
        }
        
        Schema::create('api_monitor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_monitor_id')->constrained('api_monitors')->onDelete('cascade');
            $table->enum('status', ['up', 'down']);
            $table->integer('response_time')->nullable(); // Milliseconds
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable(); // Store response for debugging
            $table->text('error_message')->nullable();
            $table->text('validation_errors')->nullable(); // JSON array of validation failures
            $table->boolean('latency_exceeded')->default(false);
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['api_monitor_id', 'checked_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_monitor_checks');
    }
};
