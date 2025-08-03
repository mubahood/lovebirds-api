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
        Schema::create('profile_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('boost_type', ['profile_visibility', 'super_boost', 'premium_boost'])->default('profile_visibility');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('visibility_multiplier', 3, 1)->default(3.0); // 3x visibility
            $table->integer('views_generated')->default(0);
            $table->integer('likes_generated')->default(0);
            $table->integer('matches_generated')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_boosts');
    }
};
