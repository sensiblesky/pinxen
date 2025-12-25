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
            // Change time columns to datetime
            $table->datetime('maintenance_start_time')->nullable()->change();
            $table->datetime('maintenance_end_time')->nullable()->change();
            // Remove maintenance_days as we're using specific datetime ranges now
            $table->dropColumn('maintenance_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors_service_uptime', function (Blueprint $table) {
            // Revert back to time
            $table->time('maintenance_start_time')->nullable()->change();
            $table->time('maintenance_end_time')->nullable()->change();
            // Add back maintenance_days
            $table->json('maintenance_days')->nullable()->after('maintenance_end_time');
        });
    }
};
