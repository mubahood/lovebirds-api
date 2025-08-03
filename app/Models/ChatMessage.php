<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_head_id', 'sender_id', 'receiver_id', 'sender_name', 'sender_photo',
        'receiver_name', 'receiver_photo', 'body', 'type', 'status', 'audio',
        'video', 'document', 'photo', 'longitude', 'latitude', 'message_reactions',
        'reply_to_message_id', 'is_forwarded', 'delivery_status', 'read_at'
    ];

    protected $casts = [
        'message_reactions' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    //created
    //boot
    protected static function boot()
    {
        parent::boot();

        //created
        static::creating(function ($model) {
            try {
                // Set delivery status and timestamp
                if (!$model->delivery_status) {
                    $model->delivery_status = 'sent';
                }
                
                // Update chat head last message info
                $model->updateChatHead();
                
                // Send notifications
                $model->send_notification();
            } catch (\Throwable $th) {
                //throw $th;
            }
        });

        static::updating(function ($model) {
            try {
                // Update chat head when message status changes
                if ($model->isDirty('status') && $model->status === 'read') {
                    $model->read_at = now();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }

    //function send_notification
    public function send_notification()
    {
        $user = User::find($this->receiver_id);
        if ($user != null) {
            $user->send_notification($this);
            //IF SENDER IS NOT NULL
            if ($this->sender == null) {
                return;
            }
            
            // Enhanced notification for dating context
            $senderName = $this->sender->name;
            $messageType = $this->getMessageTypeForNotification();
            
            Utils::sendNotificationToUser($user,[
                'title' => "New message from {$senderName}",
                'body' => $this->getNotificationBody(),
                'type' => 'chat_message',
                'chat_head_id' => $this->chat_head_id,
                'sender_id' => $this->sender_id
            ]);
        }
    }

    private function updateChatHead()
    {
        $chatHead = ChatHead::find($this->chat_head_id);
        if ($chatHead) {
            $chatHead->last_message_body = $this->getDisplayBody();
            $chatHead->last_message_time = now();
            $chatHead->last_message_status = $this->status;
            
            // Update unread counts
            if ($this->receiver_id == $chatHead->customer_id) {
                $chatHead->increment('receiver_unread_count');
            } else {
                $chatHead->increment('sender_unread_count');
            }
            
            $chatHead->save();
        }
    }

    private function getNotificationBody()
    {
        switch ($this->type) {
            case 'photo':
                return '📸 Sent a photo';
            case 'video':
                return '🎥 Sent a video';
            case 'audio':
                return '🎵 Sent an audio message';
            case 'document':
                return '📄 Sent a document';
            case 'location':
                return '📍 Shared their location';
            case 'text':
            default:
                return $this->body ? substr($this->body, 0, 50) . (strlen($this->body) > 50 ? '...' : '') : 'Sent a message';
        }
    }

    private function getDisplayBody()
    {
        switch ($this->type) {
            case 'photo':
                return '📸 Photo';
            case 'video':
                return '🎥 Video';
            case 'audio':
                return '🎵 Audio message';
            case 'document':
                return '📄 Document';
            case 'location':
                return '📍 Location';
            case 'text':
            default:
                return $this->body;
        }
    }

    private function getMessageTypeForNotification()
    {
        return $this->type ?: 'text';
    }

    //Sender
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    //Receiver
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Chat Head relationship
    public function chatHead()
    {
        return $this->belongsTo(ChatHead::class, 'chat_head_id');
    }

    // Replied to message relationship
    public function replyToMessage()
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_message_id');
    }

    // Replies to this message
    public function replies()
    {
        return $this->hasMany(ChatMessage::class, 'reply_to_message_id');
    }

    /**
     * Dating-specific methods
     */
    
    // Check if users are matched and can chat
    public function canUsersChat()
    {
        $sender = $this->sender;
        $receiver = $this->receiver;
        
        if (!$sender || !$receiver) {
            return false;
        }
        
        // Check if users are matched
        $match = \App\Models\UserMatch::where(function($query) use ($sender, $receiver) {
            $query->where('user_id', $sender->id)
                  ->where('matched_user_id', $receiver->id);
        })->orWhere(function($query) use ($sender, $receiver) {
            $query->where('user_id', $receiver->id)
                  ->where('matched_user_id', $sender->id);
        })->where('status', 'active')->first();
        
        return $match !== null;
    }

    // Add reaction to message
    public function addReaction($userId, $emoji)
    {
        $reactions = $this->message_reactions ?: [];
        
        // Remove existing reaction from this user
        $reactions = array_filter($reactions, function($reaction) use ($userId) {
            return $reaction['user_id'] != $userId;
        });
        
        // Add new reaction
        $reactions[] = [
            'user_id' => $userId,
            'emoji' => $emoji,
            'created_at' => now()->toISOString()
        ];
        
        $this->message_reactions = $reactions;
        $this->save();
        
        return $this;
    }

    // Remove reaction from message
    public function removeReaction($userId)
    {
        $reactions = $this->message_reactions ?: [];
        
        $reactions = array_filter($reactions, function($reaction) use ($userId) {
            return $reaction['user_id'] != $userId;
        });
        
        $this->message_reactions = array_values($reactions);
        $this->save();
        
        return $this;
    }

    // Get reaction summary
    public function getReactionSummary()
    {
        $reactions = $this->message_reactions ?: [];
        $summary = [];
        
        foreach ($reactions as $reaction) {
            $emoji = $reaction['emoji'];
            if (!isset($summary[$emoji])) {
                $summary[$emoji] = ['count' => 0, 'users' => []];
            }
            $summary[$emoji]['count']++;
            $summary[$emoji]['users'][] = $reaction['user_id'];
        }
        
        return $summary;
    }

    /**
     * Scopes
     */
    
    public function scopeUnread($query)
    {
        return $query->where('status', '!=', 'read');
    }
    
    public function scopeForChat($query, $chatHeadId)
    {
        return $query->where('chat_head_id', $chatHeadId);
    }
    
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    public function scopeWithMedia($query)
    {
        return $query->whereIn('type', ['photo', 'video', 'audio', 'document']);
    }

    /**
     * Accessors & Mutators
     */
    
    public function getIsReadAttribute()
    {
        return $this->status === 'read';
    }
    
    public function getHasReactionsAttribute()
    {
        return !empty($this->message_reactions);
    }
    
    public function getMediaUrlAttribute()
    {
        switch ($this->type) {
            case 'photo':
                return $this->photo;
            case 'video':
                return $this->video;
            case 'audio':
                return $this->audio;
            case 'document':
                return $this->document;
            default:
                return null;
        }
    }
}
