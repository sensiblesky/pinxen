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
        if (!Schema::hasColumn('payments', 'uid')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('uid', 36)->nullable()->after('id');
            });
        }

        // Generate UUIDs for existing records that don't have one
        $payments = \App\Models\Payment::whereNull('uid')->get();
        foreach ($payments as $payment) {
            $payment->uid = \Illuminate\Support\Str::uuid()->toString();
            $payment->save();
        }

        // Make uid not nullable and unique after populating
        if (Schema::hasColumn('payments', 'uid')) {
            // Check if unique index already exists
            $indexExists = \DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_uid_unique'");
            
            if (empty($indexExists)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('uid', 36)->nullable(false)->unique()->change();
                });
            } else {
                // Just make it not nullable if unique already exists
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('uid', 36)->nullable(false)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'uid')) {
                $table->dropUnique(['uid']);
                $table->dropColumn('uid');
            }
        });
    }
};


