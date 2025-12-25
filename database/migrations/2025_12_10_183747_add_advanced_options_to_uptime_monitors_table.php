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
        Schema::table('monitors_service_uptime', function (Blueprint $table) {
            $table->string('request_method', 10)->default('GET')->after('url');
            $table->string('basic_auth_username', 255)->nullable()->after('request_method');
            $table->string('basic_auth_password', 255)->nullable()->after('basic_auth_username');
            $table->json('custom_headers')->nullable()->after('basic_auth_password');
            $table->boolean('cache_buster')->default(false)->after('custom_headers');
            $table->time('maintenance_start_time')->nullable()->after('cache_buster');
            $table->time('maintenance_end_time')->nullable()->after('maintenance_start_time');
            $table->json('maintenance_days')->nullable()->after('maintenance_end_time'); // Array of day numbers (0=Sunday, 6=Saturday)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors_service_uptime', function (Blueprint $table) {
            $table->dropColumn([
                'request_method',
                'basic_auth_username',
                'basic_auth_password',
                'custom_headers',
                'cache_buster',
                'maintenance_start_time',
                'maintenance_end_time',
                'maintenance_days',
            ]);
        });
    }
};
