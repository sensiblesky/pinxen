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
        Schema::create('ssl_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->string('domain', 255);
            $table->integer('check_interval')->default(60); // Minutes
            $table->boolean('alert_expiring_soon')->default(true); // Alert when 30 days or less remain
            $table->boolean('alert_expired')->default(true); // Alert when certificate expires
            $table->boolean('alert_invalid')->default(true); // Alert when certificate is invalid
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->enum('status', ['valid', 'expiring_soon', 'expired', 'invalid', 'unknown'])->default('unknown');
            $table->integer('days_until_expiration')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_active']);
            $table->index('status');
            $table->index('last_checked_at');
            $table->index('expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_monitors');
    }
};
