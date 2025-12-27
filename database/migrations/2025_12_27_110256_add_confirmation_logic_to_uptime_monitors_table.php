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
        Schema::table('monitors_service_uptime', function (Blueprint $table) {
            $table->boolean('confirmation_enabled')->default(false)->after('is_active');
            $table->integer('confirmation_probes')->default(3)->after('confirmation_enabled'); // Number of probes to use
            $table->integer('confirmation_threshold')->default(2)->after('confirmation_probes'); // X out of Y must fail
            $table->integer('confirmation_retry_delay')->default(5)->after('confirmation_threshold'); // Seconds between retries
            $table->integer('confirmation_max_retries')->default(3)->after('confirmation_retry_delay'); // Max retry attempts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors_service_uptime', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_enabled',
                'confirmation_probes',
                'confirmation_threshold',
                'confirmation_retry_delay',
                'confirmation_max_retries',
            ]);
        });
    }
};
