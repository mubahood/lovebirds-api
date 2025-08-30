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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->text('user')->nullable();
            $table->text('order_state')->nullable();
            $table->text('temporary_id')->nullable();
            $table->text('amount')->nullable();
            $table->text('order_total')->nullable();
            $table->text('payment_confirmation')->nullable();
            $table->text('description')->nullable();
            $table->text('mail')->nullable();
            $table->text('order_details')->nullable();
            $table->text('date_created')->nullable();
            $table->text('date_updated')->nullable();
            $table->text('delivery_district')->nullable();
            $table->text('customer_name')->nullable();
            $table->text('customer_phone_number_1')->nullable();
            $table->text('customer_phone_number_2')->nullable();
            $table->text('customer_address')->nullable();
            $table->text('stripe_id')->nullable();
            $table->text('stripe_text')->nullable();
            $table->text('stripe_url')->nullable();
            $table->text('stripe_paid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
