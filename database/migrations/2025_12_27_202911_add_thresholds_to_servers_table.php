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
            $table->decimal('cpu_threshold', 5, 2)->nullable()->after('is_active')->comment('CPU usage threshold percentage (0-100)');
            $table->decimal('memory_threshold', 5, 2)->nullable()->after('cpu_threshold')->comment('Memory usage threshold percentage (0-100)');
            $table->decimal('disk_threshold', 5, 2)->nullable()->after('memory_threshold')->comment('Disk usage threshold percentage (0-100)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['cpu_threshold', 'memory_threshold', 'disk_threshold']);
        });
    }
};
