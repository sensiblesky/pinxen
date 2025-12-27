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
        if (!Schema::hasColumn('subscription_plans', 'uid')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->string('uid')->nullable()->after('id');
            });
        }

        // Generate UUIDs for existing records that don't have one
        // Use DB facade directly to avoid SoftDeletes issue
        $plans = \DB::table('subscription_plans')->whereNull('uid')->get();
        foreach ($plans as $plan) {
            \DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update(['uid' => \Illuminate\Support\Str::uuid()->toString()]);
        }

        // Make uid not nullable and unique after populating
        if (Schema::hasColumn('subscription_plans', 'uid')) {
            // Check if unique index already exists
            $indexExists = \DB::select("SHOW INDEX FROM subscription_plans WHERE Key_name = 'subscription_plans_uid_unique'");
            
            if (empty($indexExists)) {
                Schema::table('subscription_plans', function (Blueprint $table) {
                    $table->string('uid')->nullable(false)->unique()->change();
                });
            } else {
                // Just make it not nullable if unique already exists
                Schema::table('subscription_plans', function (Blueprint $table) {
                    $table->string('uid')->nullable(false)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};

