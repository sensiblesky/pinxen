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
        // Add uid column if it doesn't exist
        if (!Schema::hasColumn('users', 'uid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('uid', 36)->unique()->after('id');
            });
        }
        
        // Add phone column if it doesn't exist
        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('email');
            });
        }
        
        // Add language_id column if it doesn't exist
        if (!Schema::hasColumn('users', 'language_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('language_id')->nullable()->after('phone');
            });
        }
        
        // Add timezone_id column if it doesn't exist
        if (!Schema::hasColumn('users', 'timezone_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('timezone_id')->nullable()->after('language_id');
            });
        }
        
        // Add notification fields only if they don't exist
        if (!Schema::hasColumn('users', 'notify_in_app')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'timezone_id')) {
                    $table->boolean('notify_in_app')->default(true)->after('timezone_id');
                } else {
                    $table->boolean('notify_in_app')->default(true);
                }
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
        
        // Ensure uid is unique (if it exists but isn't unique)
        if (Schema::hasColumn('users', 'uid')) {
            try {
                // Check if unique constraint exists, if not add it
                Schema::table('users', function (Blueprint $table) {
                    $table->string('uid', 36)->unique()->change();
                });
            } catch (\Exception $e) {
                // Unique constraint might already exist or column might already be unique
            }
        }
        
        // Add foreign keys if they don't exist and the referenced tables exist
        // Note: Foreign keys will be added after languages and timezones tables are created
        // This is handled in a separate migration or can be added manually if needed
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
