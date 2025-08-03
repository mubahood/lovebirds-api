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
        Schema::create('user_matches', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // First user in match
            $table->integer('matched_user_id')->nullable(); // Second user in match
            $table->string('status')->nullable()->default('Active'); // Active, Unmatched, Blocked
            $table->datetime('matched_at')->nullable();
            $table->datetime('last_message_at')->nullable();
            $table->string('match_type')->nullable()->default('Mutual'); // Mutual, Super_Like
            $table->integer('messages_count')->nullable()->default(0);
            $table->string('conversation_starter')->nullable(); // Who started conversation
            $table->text('match_reason')->nullable(); // Algorithm reason for match
            $table->decimal('compatibility_score', 3, 2)->nullable(); // 0.00 to 1.00
            $table->string('is_conversation_started')->nullable()->default('No'); // Yes, No
            $table->datetime('unmatched_at')->nullable();
            $table->integer('unmatched_by')->nullable(); // User ID who unmatched
            $table->text('unmatch_reason')->nullable();
            $table->text('metadata')->nullable(); // JSON for additional data
            $table->timestamps();
            
            // Simple indexes (no foreign key constraints)
            $table->index('user_id');
            $table->index('matched_user_id');
            $table->index(['user_id', 'matched_user_id']);
            $table->index('status');
            $table->index('matched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_matches');
    }
};
