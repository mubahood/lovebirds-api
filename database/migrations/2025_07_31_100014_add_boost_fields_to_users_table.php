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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_boosted')->default(false);
            $table->timestamp('boost_expires_at')->nullable();
            $table->integer('boost_credits')->default(0); // For non-premium users
            $table->timestamp('last_boosted_at')->nullable();
            $table->integer('total_boosts_used')->default(0);
            
            // Index for boost queries
            $table->index(['is_boosted', 'boost_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_boosted',
                'boost_expires_at', 
                'boost_credits',
                'last_boosted_at',
                'total_boosts_used'
            ]);
        });
    }
};
