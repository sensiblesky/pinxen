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
        Schema::create('ssl_monitor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssl_monitor_id')->constrained('ssl_monitors')->onDelete('cascade');
            $table->enum('status', ['valid', 'expiring_soon', 'expired', 'invalid'])->default('valid');
            $table->string('resolved_ip', 45)->nullable();
            $table->string('issued_to', 255)->nullable();
            $table->string('issuer_cn', 255)->nullable();
            $table->string('cert_alg', 100)->nullable();
            $table->boolean('cert_valid')->default(true);
            $table->boolean('cert_exp')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_till')->nullable();
            $table->integer('validity_days')->nullable();
            $table->integer('days_left')->nullable();
            $table->boolean('hsts_header_enabled')->default(false);
            $table->float('response_time_sec')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable(); // Store full API response
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['ssl_monitor_id', 'checked_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_monitor_checks');
    }
};
