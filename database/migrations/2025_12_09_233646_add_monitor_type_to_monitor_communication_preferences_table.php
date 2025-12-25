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

        // Check if column already exists
        if (Schema::hasColumn('monitor_communication_preferences', 'monitor_type')) {
            return;
        }

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
            $table->string('monitor_type', 50)->default('monitor')->nullable(false)->change();
        });

        // Drop the old unique constraint if it exists (without monitor_type)
        try {
            Schema::table('monitor_communication_preferences', function (Blueprint $table) {
                $table->dropUnique(['monitor_id', 'communication_channel']);
            });
        } catch (\Exception $e) {
            // Constraint might not exist, that's okay
        }

        // Remove duplicates before adding unique constraint
        // Keep the first record of each duplicate group
        $duplicates = DB::table('monitor_communication_preferences')
            ->select('monitor_id', 'monitor_type', 'communication_channel', DB::raw('MIN(id) as min_id'))
            ->groupBy('monitor_id', 'monitor_type', 'communication_channel')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            // Delete all but the first record
            DB::table('monitor_communication_preferences')
                ->where('monitor_id', $duplicate->monitor_id)
                ->where('monitor_type', $duplicate->monitor_type)
                ->where('communication_channel', $duplicate->communication_channel)
                ->where('id', '!=', $duplicate->min_id)
                ->delete();
        }

        // Add new unique constraint that includes monitor_type
        Schema::table('monitor_communication_preferences', function (Blueprint $table) {
            $table->unique(['monitor_id', 'monitor_type', 'communication_channel'], 'monitor_comm_pref_unique');
        });

        // Add index for monitor_type if it doesn't exist
        try {
            Schema::table('monitor_communication_preferences', function (Blueprint $table) {
                $table->index(['monitor_type', 'monitor_id'], 'monitor_comm_pref_type_id_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, that's okay
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('monitor_communication_preferences')) {
            return;
        }

        if (!Schema::hasColumn('monitor_communication_preferences', 'monitor_type')) {
            return;
        }

        Schema::table('monitor_communication_preferences', function (Blueprint $table) {
            // Drop the unique constraint that includes monitor_type
            try {
                $table->dropUnique('monitor_comm_pref_unique');
            } catch (\Exception $e) {
                // Might not exist
            }
            
            // Drop index
            try {
                $table->dropIndex('monitor_comm_pref_type_id_index');
            } catch (\Exception $e) {
                // Might not exist
            }
            
            // Drop monitor_type column
            $table->dropColumn('monitor_type');
        });
    }
};
