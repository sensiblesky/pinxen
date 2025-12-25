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
        Schema::table('ssl_monitors', function (Blueprint $table) {
            $table->timestamp('next_check_at')->nullable()->after('last_checked_at')->index();
        });

        // Backfill next_check_at for existing records
        // If last_checked_at exists, set next_check_at = last_checked_at + check_interval minutes
        // Otherwise, set next_check_at = now() (so they get checked immediately)
        DB::statement("
            UPDATE ssl_monitors 
            SET next_check_at = CASE 
                WHEN last_checked_at IS NOT NULL THEN 
                    DATE_ADD(last_checked_at, INTERVAL check_interval MINUTE)
                ELSE 
                    NOW()
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ssl_monitors', function (Blueprint $table) {
            $table->dropColumn('next_check_at');
        });
    }
};
