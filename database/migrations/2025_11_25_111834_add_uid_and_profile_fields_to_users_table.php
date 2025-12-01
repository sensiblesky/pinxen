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
        // Add notification fields only if they don't exist
        if (!Schema::hasColumn('users', 'notify_in_app')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_in_app')->default(true)->after('timezone_id');
            });
        }
        if (!Schema::hasColumn('users', 'notify_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_email')->default(true)->after('notify_in_app');
            });
        }
        if (!Schema::hasColumn('users', 'notify_push')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_push')->default(false)->after('notify_email');
            });
        }
        if (!Schema::hasColumn('users', 'notify_sms')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notify_sms')->default(false)->after('notify_push');
            });
        }
        if (!Schema::hasColumn('users', 'require_password_verification')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('require_password_verification')->default(false)->after('notify_sms');
            });
        }
        
        // Ensure uid is unique
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('uid', 36)->unique()->change();
            });
        } catch (\Exception $e) {
            // Unique constraint might already exist
        }
        
        // Add foreign keys if they don't exist
        try {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'language_id') && !Schema::hasColumn('users', 'language_id')) {
                    $table->foreign('language_id')->references('id')->on('languages')->onDelete('set null');
                }
            });
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
        
        try {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'timezone_id')) {
                    $table->foreign('timezone_id')->references('id')->on('timezones')->onDelete('set null');
                }
            });
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropForeign(['timezone_id']);
            $table->dropColumn([
                'uid',
                'phone',
                'language_id',
                'timezone_id',
                'notify_in_app',
                'notify_email',
                'notify_push',
                'notify_sms',
                'require_password_verification'
            ]);
        });
    }
};
