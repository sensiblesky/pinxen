<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('api_monitor_checks')) {
            return;
        }

        // Drop the incorrect foreign key if it exists
        try {
            DB::statement('ALTER TABLE `api_monitor_checks` DROP FOREIGN KEY `api_monitor_checks_api_monitor_id_foreign`');
        } catch (\Exception $e) {
            // Foreign key might not exist or have a different name
        }

        // Check if the correct foreign key already exists
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'api_monitor_checks' 
            AND COLUMN_NAME = 'api_monitor_id' 
            AND REFERENCED_TABLE_NAME = 'api_monitors'
        ");

        if (empty($foreignKeys)) {
            // Add the correct foreign key
            Schema::table('api_monitor_checks', function (Blueprint $table) {
                $table->foreign('api_monitor_id')
                    ->references('id')
                    ->on('api_monitors')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('api_monitor_checks')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `api_monitor_checks` DROP FOREIGN KEY `api_monitor_checks_api_monitor_id_foreign`');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }
    }
};
