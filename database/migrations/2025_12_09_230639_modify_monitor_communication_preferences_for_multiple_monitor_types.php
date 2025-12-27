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
        // Check if table exists
        if (!Schema::hasTable('monitor_communication_preferences')) {
            return;
        }

        // Drop foreign key and unique constraint if they exist
        try {
            Schema::table('monitor_communication_preferences', function (Blueprint $table) {
                $table->dropForeign(['monitor_id']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue
        }
        
        try {
            Schema::table('monitor_communication_preferences', function (Blueprint $table) {
                $table->dropUnique('monitor_comm_pref_unique');
            });
        } catch (\Exception $e) {
            // Try alternative name
            try {
                Schema::table('monitor_communication_preferences', function (Blueprint $table) {
                    $table->dropUnique('monitor_comm_prefs_unique');
                });
            } catch (\Exception $e2) {
                // Neither exists, continue
            }
        }

        // Modify monitor_id column to be a regular integer (not foreign key)
        DB::statement('ALTER TABLE monitor_communication_preferences MODIFY monitor_id BIGINT UNSIGNED NOT NULL');

        // Add monitor_type column
        Schema::table('monitor_communication_preferences', function (Blueprint $table) {
            $table->string('monitor_type', 50)->nullable()->after('id');
        });

        // Update existing records to have monitor_type = 'monitor'
        DB::table('monitor_communication_preferences')
            ->whereNull('monitor_type')
            ->update(['monitor_type' => 'monitor']);

        // Make monitor_type NOT NULL and set default
        Schema::table('monitor_communication_preferences', function (Blueprint $table) {
            $table->string('monitor_type', 50)->default('monitor')->change();
            
            // Add new unique constraint that includes monitor_type
            $table->unique(['monitor_id', 'monitor_type', 'communication_channel'], 'monitor_comm_pref_unique');
            
            // Add index for monitor_type
            $table->index(['monitor_type', 'monitor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('monitor_communication_preferences')) {
            return;
        }

        Schema::table('monitor_communication_preferences', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('monitor_comm_pref_unique');
            
            // Drop monitor_type column
            $table->dropColumn('monitor_type');
        });

        // Restore foreign key constraint (only if monitors table exists)
        if (Schema::hasTable('monitors')) {
            Schema::table('monitor_communication_preferences', function (Blueprint $table) {
                $table->foreign('monitor_id')->references('id')->on('monitors')->onDelete('cascade');
                $table->unique(['monitor_id', 'communication_channel'], 'monitor_comm_pref_unique');
            });
        }
    }
};
