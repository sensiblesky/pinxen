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
            $table->json('layer_checks')->nullable()->after('failure_classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uptime_monitor_checks', function (Blueprint $table) {
            $table->dropColumn('layer_checks');
        });
    }
};
