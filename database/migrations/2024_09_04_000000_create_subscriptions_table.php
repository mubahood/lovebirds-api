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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('plan_id'); // weekly, monthly, quarterly
            $table->string('plan_name')->nullable();
            $table->string('plan_duration')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('cad');
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired', 'failed'])->default('pending');
            
            // Stripe fields
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->text('stripe_url')->nullable();
            $table->enum('stripe_paid', ['Yes', 'No'])->default('No');
            
            // Payment details
            $table->string('payment_method')->default('stripe');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('trial_end_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->enum('payment_confirmation', ['PENDING', 'PAID', 'FAILED'])->default('PENDING');
            
            // Additional data
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['stripe_subscription_id']);
            $table->index(['status', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
