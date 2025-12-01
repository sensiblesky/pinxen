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
        // Only drop columns if they exist
        // SQLite has limitations with DROP COLUMN, so we wrap in try-catch
        try {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('users', 'email_otp')) {
                $columnsToDrop[] = 'email_otp';
            }
            
            if (Schema::hasColumn('users', 'email_otp_expires_at')) {
                $columnsToDrop[] = 'email_otp_expires_at';
            }
            
            if (!empty($columnsToDrop)) {
                Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                    $table->dropColumn($columnsToDrop);
                });
            }
        } catch (\Exception $e) {
            // If dropping columns fails (e.g., SQLite index issues), log and continue
            // The columns will be ignored in the application logic anyway
            \Log::warning('Failed to drop OTP columns from users table: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_otp', 6)->nullable()->after('email_verified_at');
            $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp');
        });
    }
};
