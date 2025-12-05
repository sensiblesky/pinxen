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
        if (!Schema::hasColumn('plan_features', 'uid')) {
            Schema::table('plan_features', function (Blueprint $table) {
                $table->string('uid')->nullable()->after('id');
            });
        }

        // Generate UUIDs for existing records that don't have one
        $features = \App\Models\PlanFeature::whereNull('uid')->get();
        foreach ($features as $feature) {
            $feature->uid = \Illuminate\Support\Str::uuid()->toString();
            $feature->save();
        }

        // Make uid not nullable and unique after populating
        if (Schema::hasColumn('plan_features', 'uid')) {
            // Check if unique index already exists
            $indexExists = \DB::select("SHOW INDEX FROM plan_features WHERE Key_name = 'plan_features_uid_unique'");
            
            if (empty($indexExists)) {
                Schema::table('plan_features', function (Blueprint $table) {
                    $table->string('uid')->nullable(false)->unique()->change();
                });
            } else {
                // Just make it not nullable if unique already exists
                Schema::table('plan_features', function (Blueprint $table) {
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
        Schema::table('plan_features', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};

