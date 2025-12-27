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
        Schema::table('server_stats', function (Blueprint $table) {
            // Add processes JSON column to store detailed process information
            $table->json('processes')->nullable()->after('processes_sleeping');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_stats', function (Blueprint $table) {
            $table->dropColumn('processes');
        });
    }
};
