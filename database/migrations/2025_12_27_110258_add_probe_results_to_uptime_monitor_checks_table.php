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
        Schema::table('uptime_monitor_checks', function (Blueprint $table) {
            $table->json('probe_results')->nullable()->after('layer_checks');
            $table->boolean('is_confirmed')->default(false)->after('probe_results');
            $table->integer('probes_failed')->default(0)->after('is_confirmed');
            $table->integer('probes_total')->default(1)->after('probes_failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uptime_monitor_checks', function (Blueprint $table) {
            $table->dropColumn(['probe_results', 'is_confirmed', 'probes_failed', 'probes_total']);
        });
    }
};
