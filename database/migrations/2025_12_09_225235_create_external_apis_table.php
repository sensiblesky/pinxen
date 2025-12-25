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
        Schema::create('external_apis', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255); // e.g., "WHOIS API", "DNS API", etc.
            $table->string('provider', 255); // e.g., "apilayer", "cloudflare", etc.
            $table->string('service_type', 100); // e.g., "whois", "dns", "ssl", etc.
            $table->string('api_key', 500)->nullable(); // API key or token
            $table->string('api_secret', 500)->nullable(); // API secret (if needed)
            $table->text('base_url')->nullable(); // Base URL for the API
            $table->text('endpoint')->nullable(); // Endpoint path
            $table->json('headers')->nullable(); // Custom headers (e.g., {"apikey": "..."})
            $table->json('config')->nullable(); // Additional configuration
            $table->boolean('is_active')->default(true);
            $table->integer('rate_limit')->nullable(); // Rate limit per minute
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['service_type', 'is_active']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_apis');
    }
};
