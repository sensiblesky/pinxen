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
        Schema::table('api_monitors', function (Blueprint $table) {
            // Auto-Auth Configuration
            $table->boolean('auto_auth_enabled')->default(false)->after('auth_type');
            $table->enum('auto_auth_type', ['oauth2_client_credentials', 'oauth2_password', 'oauth2_refresh_token', 'jwt', 'apikey_rotation'])->nullable()->after('auto_auth_enabled');
            
            // OAuth2 Configuration
            $table->string('oauth2_token_url', 500)->nullable()->after('auto_auth_type');
            $table->string('oauth2_client_id', 255)->nullable()->after('oauth2_token_url');
            $table->text('oauth2_client_secret')->nullable()->after('oauth2_client_id');
            $table->string('oauth2_username', 255)->nullable()->after('oauth2_client_secret'); // For password grant
            $table->text('oauth2_password')->nullable()->after('oauth2_username');
            $table->string('oauth2_scope', 500)->nullable()->after('oauth2_password');
            $table->text('oauth2_refresh_token')->nullable()->after('oauth2_scope');
            
            // Token Storage
            $table->text('current_access_token')->nullable()->after('oauth2_refresh_token');
            $table->timestamp('token_expires_at')->nullable()->after('current_access_token');
            $table->timestamp('token_refreshed_at')->nullable()->after('token_expires_at');
            
            // JWT Configuration
            $table->string('jwt_token_path', 255)->nullable()->after('token_refreshed_at'); // JSON path to extract JWT from response
            $table->integer('jwt_expiration_buffer_seconds')->default(300)->after('jwt_token_path'); // Refresh 5 min before expiry
            
            // API Key Rotation
            $table->timestamp('apikey_rotation_reminder_at')->nullable()->after('jwt_expiration_buffer_seconds');
            $table->integer('apikey_rotation_days')->nullable()->after('apikey_rotation_reminder_at'); // Days before rotation reminder
            
            // Auto-refresh settings
            $table->boolean('auto_refresh_on_expiry')->default(true)->after('apikey_rotation_days');
            $table->boolean('retry_after_refresh')->default(true)->after('auto_refresh_on_expiry');
            $table->integer('max_refresh_attempts')->default(3)->after('retry_after_refresh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_monitors', function (Blueprint $table) {
            $table->dropColumn([
                'auto_auth_enabled',
                'auto_auth_type',
                'oauth2_token_url',
                'oauth2_client_id',
                'oauth2_client_secret',
                'oauth2_username',
                'oauth2_password',
                'oauth2_scope',
                'oauth2_refresh_token',
                'current_access_token',
                'token_expires_at',
                'token_refreshed_at',
                'jwt_token_path',
                'jwt_expiration_buffer_seconds',
                'apikey_rotation_reminder_at',
                'apikey_rotation_days',
                'auto_refresh_on_expiry',
                'retry_after_refresh',
                'max_refresh_attempts',
            ]);
        });
    }
};
