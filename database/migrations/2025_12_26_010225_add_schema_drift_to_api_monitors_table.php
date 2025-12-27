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
            // Schema Drift Detection
            $table->boolean('schema_drift_enabled')->default(false)->after('max_refresh_attempts');
            $table->enum('schema_source_type', ['upload', 'url'])->nullable()->after('schema_drift_enabled');
            $table->text('schema_content')->nullable()->after('schema_source_type'); // OpenAPI/Swagger spec content
            $table->string('schema_url', 500)->nullable()->after('schema_content'); // URL to fetch spec
            $table->json('schema_parsed')->nullable()->after('schema_url'); // Parsed schema structure
            
            // Detection Rules
            $table->boolean('detect_missing_fields')->default(true)->after('schema_parsed');
            $table->boolean('detect_type_changes')->default(true)->after('detect_missing_fields');
            $table->boolean('detect_breaking_changes')->default(true)->after('detect_type_changes');
            $table->boolean('detect_enum_violations')->default(true)->after('detect_breaking_changes');
            
            // Schema Version Tracking
            $table->string('schema_version', 50)->nullable()->after('detect_enum_violations');
            $table->timestamp('schema_last_validated_at')->nullable()->after('schema_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_monitors', function (Blueprint $table) {
            $table->dropColumn([
                'schema_drift_enabled',
                'schema_source_type',
                'schema_content',
                'schema_url',
                'schema_parsed',
                'detect_missing_fields',
                'detect_type_changes',
                'detect_breaking_changes',
                'detect_enum_violations',
                'schema_version',
                'schema_last_validated_at',
            ]);
        });
    }
};
