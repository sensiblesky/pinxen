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
        Schema::create('monitors_service_uptime', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->string('url', 500);
            $table->integer('check_interval')->default(5); // Minutes
            $table->integer('timeout')->default(30); // Seconds
            $table->integer('expected_status_code')->default(200);
            $table->string('keyword_present', 255)->nullable(); // Optional keyword that must be present
            $table->string('keyword_absent', 255)->nullable(); // Optional keyword that must be absent
            $table->boolean('check_ssl')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_active']);
            $table->index('status');
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors_service_uptime');
    }
};
