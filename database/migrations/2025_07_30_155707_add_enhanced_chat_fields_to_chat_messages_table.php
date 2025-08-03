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
        Schema::table('chat_messages', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('chat_messages', 'message_reactions')) {
                $table->json('message_reactions')->nullable(); // Store emoji reactions
            }
            
            if (!Schema::hasColumn('chat_messages', 'reply_to_message_id')) {
                $table->bigInteger('reply_to_message_id')->nullable(); // Reply to message feature
            }
            
            if (!Schema::hasColumn('chat_messages', 'is_forwarded')) {
                $table->string('is_forwarded')->default('No')->nullable(); // Forward message feature
            }
            
            if (!Schema::hasColumn('chat_messages', 'delivery_status')) {
                $table->string('delivery_status')->default('sent')->nullable(); // sent, delivered, read
            }
            
            if (!Schema::hasColumn('chat_messages', 'read_at')) {
                $table->timestamp('read_at')->nullable(); // When message was read
            }
            
            if (!Schema::hasColumn('chat_messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable(); // When message was edited
            }
            
            if (!Schema::hasColumn('chat_messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable(); // Soft delete for messages
            }
            
            if (!Schema::hasColumn('chat_messages', 'message_metadata')) {
                $table->json('message_metadata')->nullable(); // Additional metadata
            }
            
            // Enhanced media support
            if (!Schema::hasColumn('chat_messages', 'media_duration')) {
                $table->integer('media_duration')->nullable(); // Duration for audio/video in seconds
            }
            
            if (!Schema::hasColumn('chat_messages', 'media_size')) {
                $table->bigInteger('media_size')->nullable(); // File size in bytes
            }
            
            if (!Schema::hasColumn('chat_messages', 'media_thumbnail')) {
                $table->text('media_thumbnail')->nullable(); // Thumbnail for videos/images
            }
            
            // Location details
            if (!Schema::hasColumn('chat_messages', 'location_name')) {
                $table->string('location_name')->nullable(); // Human readable location name
            }
            
            if (!Schema::hasColumn('chat_messages', 'location_address')) {
                $table->text('location_address')->nullable(); // Full address
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $columns = [
                'message_reactions', 'reply_to_message_id', 'is_forwarded', 
                'delivery_status', 'read_at', 'edited_at', 'deleted_at',
                'message_metadata', 'media_duration', 'media_size', 
                'media_thumbnail', 'location_name', 'location_address'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('chat_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
