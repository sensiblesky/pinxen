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
            $table->string('failure_type', 50)->nullable()->after('error_message');
            $table->string('failure_classification', 100)->nullable()->after('failure_type');
            $table->index('failure_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uptime_monitor_checks', function (Blueprint $table) {
            $table->dropIndex(['failure_type']);
            $table->dropColumn(['failure_type', 'failure_classification']);
        });
    }
};
