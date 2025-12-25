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
        Schema::create('dns_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->string('domain', 255);
            $table->json('record_types')->default('["A", "AAAA", "CNAME", "MX", "NS", "TXT", "SOA"]'); // Types to monitor
            $table->integer('check_interval')->default(60); // Minutes
            $table->boolean('alert_on_change')->default(true); // Alert when DNS records change
            $table->boolean('alert_on_missing')->default(true); // Alert when expected records are missing
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->enum('status', ['healthy', 'changed', 'missing', 'error', 'unknown'])->default('unknown');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_active']);
            $table->index('status');
            $table->index('last_checked_at');
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_monitors');
    }
};
