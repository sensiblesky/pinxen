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
        Schema::table('servers', function (Blueprint $table) {
            // Persistent agent identifiers for server identification
            $table->string('machine_id', 255)->nullable()->after('agent_version')->comment('Linux systemd machine-id or equivalent');
            $table->string('system_uuid', 255)->nullable()->after('machine_id')->comment('BIOS/UEFI system UUID');
            $table->string('disk_uuid', 255)->nullable()->after('system_uuid')->comment('Root filesystem/disk UUID');
            $table->string('agent_id', 255)->nullable()->after('disk_uuid')->comment('Persistent agent ID (hash of machine-id + system_uuid + disk_uuid)');
            
            // Index for faster lookups
            $table->index('agent_id');
            $table->index('machine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['machine_id']);
            $table->dropColumn(['machine_id', 'system_uuid', 'disk_uuid', 'agent_id']);
        });
    }
};
