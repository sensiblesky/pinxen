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
            // Variable extraction rules: JSON array defining how to extract variables from responses
            // Example: [{"name": "token", "path": "$.data.token", "step": 1}, {"name": "order_id", "path": "$.data.order.id", "step": 2}]
            $table->json('variable_extraction_rules')->nullable()->after('response_assertions');
            
            // Multi-step monitoring flow: JSON array of API calls to execute in sequence
            // Example: [
            //   {"step": 1, "name": "Login", "url": "/login", "method": "POST", "body": {...}, "extract_variables": [{"name": "token", "path": "$.token"}]},
            //   {"step": 2, "name": "Get Orders", "url": "/orders?token={{token}}", "method": "GET", "extract_variables": [{"name": "order_id", "path": "$.orders[0].id"}]},
            //   {"step": 3, "name": "Checkout", "url": "/checkout", "method": "POST", "body": {"order_id": "{{order_id}}"}}
            // ]
            $table->json('monitoring_steps')->nullable()->after('variable_extraction_rules');
            
            // Enable stateful monitoring (multi-step flows)
            $table->boolean('is_stateful')->default(false)->after('monitoring_steps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_monitors', function (Blueprint $table) {
            $table->dropColumn(['variable_extraction_rules', 'monitoring_steps', 'is_stateful']);
        });
    }
};
