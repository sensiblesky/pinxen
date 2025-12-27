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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_key_id')->constrained('api_keys')->onDelete('cascade');
            $table->string('name');
            $table->string('server_key', 64)->unique(); // Unique identifier for the server
            $table->text('description')->nullable();
            $table->string('hostname')->nullable();
            $table->string('os_type')->nullable(); // linux, windows, etc.
            $table->string('os_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('agent_installed_at')->nullable();
            $table->string('agent_version')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_active']);
            $table->index('server_key');
            $table->index('api_key_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
