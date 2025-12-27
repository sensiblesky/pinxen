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
        if (!Schema::hasTable('api_monitor_dependencies')) {
            return;
        }

        Schema::table('api_monitor_dependencies', function (Blueprint $table) {
            try {
                $table->index(['api_monitor_id', 'depends_on_monitor_id'], 'api_deps_monitor_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index('dependency_type', 'api_deps_type_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index('is_confirmed', 'api_deps_confirmed_idx');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_monitor_dependencies', function (Blueprint $table) {
            try {
                $table->dropIndex('api_deps_monitor_idx');
            } catch (\Exception $e) {}
            try {
                $table->dropIndex('api_deps_type_idx');
            } catch (\Exception $e) {}
            try {
                $table->dropIndex('api_deps_confirmed_idx');
            } catch (\Exception $e) {}
        });
    }
};
