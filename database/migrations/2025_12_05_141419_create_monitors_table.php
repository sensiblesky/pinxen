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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_category_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->enum('type', ['web', 'server'])->default('web');
            $table->string('url', 500)->nullable(); // For web monitors
            $table->integer('check_interval')->default(5); // Minutes
            $table->integer('timeout')->default(30); // Seconds
            $table->integer('expected_status_code')->default(200);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['service_category_id', 'is_active']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
