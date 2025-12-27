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
        Schema::create('api_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->string('url', 500);
            $table->enum('request_method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'])->default('GET');
            
            // Authentication
            $table->enum('auth_type', ['none', 'bearer', 'basic', 'apikey'])->default('none');
            $table->text('auth_token')->nullable(); // Bearer token or API key
            $table->string('auth_username', 255)->nullable(); // For Basic Auth
            $table->string('auth_password', 255)->nullable(); // For Basic Auth
            $table->string('auth_header_name', 100)->nullable(); // For API Key (e.g., X-API-Key)
            
            // Request Configuration
            $table->text('request_headers')->nullable(); // JSON array of custom headers
            $table->text('request_body')->nullable(); // JSON/XML request body
            $table->string('content_type', 100)->default('application/json'); // application/json, application/xml, etc.
            
            // Response Validation
            $table->integer('expected_status_code')->default(200);
            $table->text('response_assertions')->nullable(); // JSON array of assertions
            // Example: [{"type": "json_path", "path": "$.status", "operator": "equals", "value": "success"}, ...]
            $table->integer('max_latency_ms')->nullable(); // Maximum acceptable latency in milliseconds
            $table->boolean('validate_response_body')->default(true);
            
            // Monitoring Configuration
            $table->integer('check_interval')->default(5); // Minutes
            $table->integer('timeout')->default(30); // Seconds
            $table->boolean('check_ssl')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable();
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_active']);
            $table->index('status');
            $table->index('last_checked_at');
            $table->index('next_check_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_monitors');
    }
};
