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
        Schema::create('dns_monitor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_monitor_id')->constrained('dns_monitors')->onDelete('cascade');
            $table->enum('record_type', ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA']);
            $table->json('records')->nullable(); // Current DNS records for this type
            $table->json('previous_records')->nullable(); // Previous records for comparison
            $table->boolean('has_changes')->default(false); // Whether records changed
            $table->boolean('is_missing')->default(false); // Whether expected records are missing
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable(); // Store full DNS response
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['dns_monitor_id', 'checked_at']);
            $table->index('record_type');
            $table->index('has_changes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_monitor_checks');
    }
};
