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
        // First, update existing string values to integers
        DB::table('users')->where('role', 'admin')->orWhere('role', 'Admin')->update(['role' => '1']);
        DB::table('users')->where('role', 'user')->orWhere('role', 'User')->orWhereNull('role')->update(['role' => '2']);
        
        // Then modify the column type
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('role')->default(2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to strings if needed
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
        
        DB::table('users')->where('role', '1')->update(['role' => 'admin']);
        DB::table('users')->where('role', '2')->update(['role' => 'user']);
    }
};
