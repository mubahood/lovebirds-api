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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('stripe_product_id')->nullable()->after('stripe_url');
            $table->text('stripe_price_id')->nullable()->after('stripe_product_id');
            $table->text('total_amount')->nullable()->after('order_total'); // For storing calculated total
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stripe_product_id', 'stripe_price_id', 'total_amount']);
        });
    }
};
