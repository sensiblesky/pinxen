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
        // Skip if table already exists (created by previous migration)
        if (Schema::hasTable('monitor_communication_preferences')) {
            return;
        }
        
        Schema::create('monitor_communication_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->enum('communication_channel', ['email', 'sms', 'whatsapp', 'telegram', 'discord']);
            $table->string('channel_value', 500); // Email address, phone number, chat ID, webhook URL, etc.
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['monitor_id', 'communication_channel'], 'monitor_comm_pref_unique');
            $table->index('monitor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_communication_preferences');
    }
};
