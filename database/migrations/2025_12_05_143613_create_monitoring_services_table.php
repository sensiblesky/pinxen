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
        Schema::create('monitoring_services', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique(); // e.g., 'uptime', 'dns', 'ssl'
            $table->string('name', 255); // e.g., 'Uptime Monitoring', 'DNS Monitoring'
            $table->text('description')->nullable();
            $table->string('category', 50); // 'core', 'performance', 'security', 'content', 'infrastructure', 'email_api', 'premium'
            $table->string('icon', 100)->nullable();
            $table->json('config_schema')->nullable(); // JSON schema defining required/optional fields
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_services');
    }
};
