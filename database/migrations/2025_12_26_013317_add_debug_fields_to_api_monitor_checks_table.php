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
        Schema::table('api_monitor_checks', function (Blueprint $table) {
            // Store full request details for replay functionality
            $table->string('request_method', 10)->nullable()->after('api_monitor_id');
            $table->string('request_url', 500)->nullable()->after('request_method');
            $table->json('request_headers')->nullable()->after('request_url');
            $table->text('request_body')->nullable()->after('request_headers');
            $table->string('request_content_type', 100)->nullable()->after('request_body');
            
            // Store full response details
            $table->json('response_headers')->nullable()->after('response_body');
            
            // Replay tracking
            $table->boolean('is_replay')->default(false)->after('response_headers');
            $table->foreignId('replay_of_check_id')->nullable()->after('is_replay')->constrained('api_monitor_checks')->onDelete('set null');
            $table->timestamp('replayed_at')->nullable()->after('replay_of_check_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_monitor_checks', function (Blueprint $table) {
            $table->dropForeign(['replay_of_check_id']);
            $table->dropColumn([
                'request_method',
                'request_url',
                'request_headers',
                'request_body',
                'request_content_type',
                'response_headers',
                'is_replay',
                'replay_of_check_id',
                'replayed_at',
            ]);
        });
    }
};
