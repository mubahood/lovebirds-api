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
            if (!Schema::hasColumn('admin_users', 'subscription_started_at')) {
                $table->timestamp('subscription_started_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'subscription_started_at')) {
                $table->dropColumn('subscription_started_at');
            }
        });
    }
};
