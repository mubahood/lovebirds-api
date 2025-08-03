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
        Schema::table('chat_heads', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('chat_heads', 'is_typing_customer')) {
                $table->boolean('is_typing_customer')->default(false)->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'is_typing_owner')) {
                $table->boolean('is_typing_owner')->default(false)->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'match_id')) {
                $table->bigInteger('match_id')->nullable(); // Link to UserMatch
            }
            
            if (!Schema::hasColumn('chat_heads', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'blocked_by_customer')) {
                $table->boolean('blocked_by_customer')->default(false)->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'blocked_by_owner')) {
                $table->boolean('blocked_by_owner')->default(false)->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'conversation_started_at')) {
                $table->timestamp('conversation_started_at')->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'last_typing_activity')) {
                $table->timestamp('last_typing_activity')->nullable();
            }
            
            if (!Schema::hasColumn('chat_heads', 'chat_metadata')) {
                $table->json('chat_metadata')->nullable(); // Additional metadata
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_heads', function (Blueprint $table) {
            $columns = [
                'is_typing_customer', 'is_typing_owner', 'match_id', 
                'is_blocked', 'blocked_by_customer', 'blocked_by_owner',
                'conversation_started_at', 'last_typing_activity', 'chat_metadata'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('chat_heads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
