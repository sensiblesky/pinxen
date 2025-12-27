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
        Schema::table('servers', function (Blueprint $table) {
            // Status detection thresholds (in minutes)
            // null = use system defaults
            $table->integer('online_threshold_minutes')->nullable()->after('disk_threshold')->comment('Minutes since last_seen_at to consider server online (default: 5)');
            $table->integer('warning_threshold_minutes')->nullable()->after('online_threshold_minutes')->comment('Minutes since last_seen_at to show warning status (default: 60)');
            $table->integer('offline_threshold_minutes')->nullable()->after('warning_threshold_minutes')->comment('Minutes since last_seen_at to consider server offline (default: 120)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['online_threshold_minutes', 'warning_threshold_minutes', 'offline_threshold_minutes']);
        });
    }
};
