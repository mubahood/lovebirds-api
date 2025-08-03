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
        Schema::create('user_likes', function (Blueprint $table) {
            $table->id();
            $table->integer('liker_id')->nullable(); // User who likes
            $table->integer('liked_user_id')->nullable(); // User being liked
            $table->string('type')->nullable()->default('Like'); // Like, Super_Like, Pass
            $table->string('status')->nullable()->default('Active'); // Active, Deleted
            $table->text('message')->nullable(); // Optional message with super like
            $table->datetime('liked_at')->nullable();
            $table->datetime('expires_at')->nullable(); // For super likes
            $table->string('is_mutual')->nullable()->default('No'); // Yes, No (becomes match)
            $table->text('metadata')->nullable(); // JSON for additional data
            $table->timestamps();
            
            // Simple indexes (no foreign key constraints)
            $table->index('liker_id');
            $table->index('liked_user_id');
            $table->index(['liker_id', 'liked_user_id']);
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_likes');
    }
};
