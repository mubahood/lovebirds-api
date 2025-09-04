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
            if (!Schema::hasColumn('admin_users', 'pending_subscription_plan')) {
                $table->enum('pending_subscription_plan', ['weekly', 'monthly', 'quarterly'])->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'pending_stripe_payment_url')) {
                $table->text('pending_stripe_payment_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'pending_subscription_plan')) {
                $table->dropColumn('pending_subscription_plan');
            }
            if (Schema::hasColumn('admin_users', 'pending_stripe_payment_url')) {
                $table->dropColumn('pending_stripe_payment_url');
            }
        });
    }
};
