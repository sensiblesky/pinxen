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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            // Note: user_subscription_id foreign key will be added after user_subscriptions table has payment_id column
            $table->unsignedBigInteger('user_subscription_id')->nullable();
            $table->string('payment_gateway'); // stripe, paypal, razorpay, square, authorize_net, mollie
            $table->string('gateway_transaction_id')->nullable(); // Transaction ID from payment gateway
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->text('gateway_response')->nullable(); // JSON response from gateway
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('metadata')->nullable(); // JSON for additional data
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('payment_gateway');
            $table->index('gateway_transaction_id');
            $table->index('user_subscription_id');
        });

        // Add foreign key constraint after user_subscriptions table exists
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('user_subscription_id')->references('id')->on('user_subscriptions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

