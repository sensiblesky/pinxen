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
        if (Schema::hasTable('api_monitor_dependencies')) {
            return;
        }
        
        Schema::create('api_monitor_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_monitor_id')->constrained('api_monitors')->onDelete('cascade');
            $table->foreignId('depends_on_monitor_id')->nullable()->constrained('api_monitors')->onDelete('cascade');
            $table->string('dependency_type', 50)->default('api'); // api, database, external_service
            $table->string('dependency_name', 255); // Name of the dependency (e.g., "Auth Service", "Orders API", "PostgreSQL")
            $table->string('dependency_url', 500)->nullable(); // URL if it's an API dependency
            $table->string('discovery_method', 50)->default('auto'); // auto, manual
            $table->json('discovery_evidence')->nullable(); // Evidence of dependency (response headers, error messages, etc.)
            $table->integer('confidence_score')->default(0); // 0-100, how confident we are about this dependency
            $table->boolean('is_confirmed')->default(false); // User confirmed this dependency
            $table->boolean('suppress_child_alerts')->default(true); // Suppress alerts when parent fails
            $table->timestamps();
            
            $table->index(['api_monitor_id', 'depends_on_monitor_id'], 'api_deps_monitor_idx');
            $table->index('dependency_type', 'api_deps_type_idx');
            $table->index('is_confirmed', 'api_deps_confirmed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_monitor_dependencies');
    }
};
