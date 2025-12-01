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
        // Add foreign key for language_id if it doesn't exist
        if (Schema::hasTable('languages') && Schema::hasColumn('users', 'language_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('language_id')->references('id')->on('languages')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
        }
        
        // Add foreign key for timezone_id if it doesn't exist
        if (Schema::hasTable('timezones') && Schema::hasColumn('users', 'timezone_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('timezone_id')->references('id')->on('timezones')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'language_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['language_id']);
            });
        }
        
        if (Schema::hasColumn('users', 'timezone_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['timezone_id']);
            });
        }
    }
};
