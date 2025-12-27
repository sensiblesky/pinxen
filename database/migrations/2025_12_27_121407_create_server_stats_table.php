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
        Schema::create('server_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            
            // CPU Metrics
            $table->decimal('cpu_usage_percent', 5, 2)->nullable(); // 0-100
            $table->integer('cpu_cores')->nullable();
            $table->decimal('cpu_load_1min', 8, 2)->nullable();
            $table->decimal('cpu_load_5min', 8, 2)->nullable();
            $table->decimal('cpu_load_15min', 8, 2)->nullable();
            
            // Memory Metrics
            $table->bigInteger('memory_total_bytes')->nullable();
            $table->bigInteger('memory_used_bytes')->nullable();
            $table->bigInteger('memory_free_bytes')->nullable();
            $table->decimal('memory_usage_percent', 5, 2)->nullable(); // 0-100
            $table->bigInteger('swap_total_bytes')->nullable();
            $table->bigInteger('swap_used_bytes')->nullable();
            $table->bigInteger('swap_free_bytes')->nullable();
            $table->decimal('swap_usage_percent', 5, 2)->nullable();
            
            // Disk Metrics
            $table->json('disk_usage')->nullable(); // Array of disk partitions
            $table->bigInteger('disk_total_bytes')->nullable();
            $table->bigInteger('disk_used_bytes')->nullable();
            $table->bigInteger('disk_free_bytes')->nullable();
            $table->decimal('disk_usage_percent', 5, 2)->nullable();
            
            // Network Metrics
            $table->json('network_interfaces')->nullable(); // Array of network stats
            $table->bigInteger('network_bytes_sent')->nullable();
            $table->bigInteger('network_bytes_received')->nullable();
            $table->bigInteger('network_packets_sent')->nullable();
            $table->bigInteger('network_packets_received')->nullable();
            
            // System Info
            $table->integer('uptime_seconds')->nullable();
            $table->integer('processes_total')->nullable();
            $table->integer('processes_running')->nullable();
            $table->integer('processes_sleeping')->nullable();
            
            // Timestamp
            $table->timestamp('recorded_at');
            
            $table->timestamps();
            
            $table->index(['server_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_stats');
    }
};
