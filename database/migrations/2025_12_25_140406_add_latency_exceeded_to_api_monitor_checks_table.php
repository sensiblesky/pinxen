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
        if (Schema::hasTable('api_monitor_checks')) {
            if (!Schema::hasColumn('api_monitor_checks', 'latency_exceeded')) {
                Schema::table('api_monitor_checks', function (Blueprint $table) {
                    $table->boolean('latency_exceeded')->default(false)->after('validation_errors');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('api_monitor_checks')) {
            if (Schema::hasColumn('api_monitor_checks', 'latency_exceeded')) {
                Schema::table('api_monitor_checks', function (Blueprint $table) {
                    $table->dropColumn('latency_exceeded');
                });
            }
        }
    }
};
