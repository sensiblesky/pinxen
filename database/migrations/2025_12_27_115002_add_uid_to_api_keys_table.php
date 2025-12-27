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
        if (!Schema::hasColumn('api_keys', 'uid')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->string('uid', 36)->nullable()->after('id');
            });
        }

        // Generate UIDs for existing records
        $apiKeys = \App\Models\ApiKey::whereNull('uid')->get();
        foreach ($apiKeys as $apiKey) {
            $apiKey->uid = \Illuminate\Support\Str::uuid()->toString();
            $apiKey->save();
        }

        // Add unique constraint and make not nullable after populating
        Schema::table('api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('api_keys', 'uid')) {
                // Try to add unique constraint (will fail silently if exists)
                try {
                    $table->unique('uid', 'api_keys_uid_unique');
                } catch (\Exception $e) {
                    // Index might already exist, continue
                }
                $table->string('uid', 36)->nullable(false)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
