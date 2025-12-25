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
        Schema::table('monitors', function (Blueprint $table) {
            if (!Schema::hasColumn('monitors', 'monitoring_service_id')) {
                $table->foreignId('monitoring_service_id')->nullable()->after('service_category_id')->constrained('monitoring_services')->onDelete('cascade');
            }
            if (!Schema::hasColumn('monitors', 'service_config')) {
                $table->json('service_config')->nullable()->after('monitoring_service_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            if (Schema::hasColumn('monitors', 'monitoring_service_id')) {
                $table->dropForeign(['monitoring_service_id']);
                $table->dropColumn('monitoring_service_id');
            }
            if (Schema::hasColumn('monitors', 'service_config')) {
                $table->dropColumn('service_config');
            }
        });
    }
};
