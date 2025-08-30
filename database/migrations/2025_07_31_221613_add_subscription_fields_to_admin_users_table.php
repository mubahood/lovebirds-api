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
            if (!Schema::hasColumn('admin_users', 'subscription_status')) {
            $table->enum('subscription_status', ['free', 'pending', 'active', 'expired', 'cancelled'])->default('free');
            }
            if (!Schema::hasColumn('admin_users', 'subscription_plan')) {
            $table->enum('subscription_plan', ['weekly', 'monthly', 'quarterly'])->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'subscription_expires_at')) {
            $table->datetime('subscription_expires_at')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'pending_stripe_payment_id')) {
            $table->string('pending_stripe_payment_id')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'subscription_updated_at')) {
            $table->timestamp('subscription_updated_at')->nullable();
            }
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
