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
        Schema::table('admin_users', function (Blueprint $table) {
            $table->enum('subscription_status', ['free', 'pending', 'active', 'expired', 'cancelled'])->default('free');
            $table->enum('subscription_plan', ['weekly', 'monthly', 'quarterly'])->nullable();
            $table->datetime('subscription_expires_at')->nullable();
            $table->string('pending_stripe_payment_id')->nullable();
            $table->timestamp('subscription_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'subscription_plan', 
                'subscription_expires_at',
                'pending_stripe_payment_id',
                'subscription_updated_at'
            ]);
        });
    }
};
