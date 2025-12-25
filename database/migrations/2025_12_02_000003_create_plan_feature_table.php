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
        Schema::create('plan_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('plan_feature_id')->constrained('plan_features')->onDelete('cascade');
            $table->integer('limit')->nullable(); // null = unlimited, number = specific limit
            $table->string('limit_type')->nullable(); // 'count', 'duration', 'size', etc.
            $table->text('value')->nullable(); // Additional value/description for this feature in this plan
            $table->timestamps();
            
            $table->unique(['subscription_plan_id', 'plan_feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_feature');
    }
};






