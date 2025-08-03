<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'product_name', 'product_photo', 'product_owner_id',
        'product_owner_name', 'product_owner_photo', 'product_owner_last_seen',
        'customer_id', 'customer_name', 'customer_photo', 'customer_last_seen',
        'last_message_body', 'last_message_time', 'last_message_status',
        'type', 'sender_unread_count', 'receiver_unread_count',
        'is_typing_customer', 'is_typing_owner', 'match_id', 'is_blocked',
        'blocked_by_customer', 'blocked_by_owner', 'conversation_started_at'
    ];

    protected $casts = [
        'last_message_time' => 'datetime',
        'conversation_started_at' => 'datetime',
        'is_typing_customer' => 'boolean',
        'is_typing_owner' => 'boolean',
        'is_blocked' => 'boolean',
        'blocked_by_customer' => 'boolean',
        'blocked_by_owner' => 'boolean'
    ];

    public function getCustomerUnreadMessagesCountAttribute()
    {
        return ChatMessage::where('chat_head_id', $this->id)
            ->where('receiver_id', $this->customer_id)
            ->where('status', '!=', 'read')
            ->count();
    }
    
    public function getProductOwnerUnreadMessagesCountAttribute()
    {
        return ChatMessage::where('chat_head_id', $this->id)
            ->where('receiver_id', $this->product_owner_id)
            ->where('status', '!=', 'read')
            ->count();
    }

    //boot on delete
    public static function boot()
    {
        parent::boot();
        
        self::creating(function ($model) {
            if (!$model->conversation_started_at) {
                $model->conversation_started_at = now();
            }
            if (!$model->type) {
                $model->type = 'dating';
            }
        });
        
        self::deleting(function ($m) {
            try {
                $sql = "DELETE FROM chat_messages WHERE chat_head_id = " . $m->id;
                DB::delete($sql); 
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }

    /**
     * Relationships
     */
    
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_head_id');
    }
    
    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'chat_head_id')->latest();
    }
    
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    
    public function productOwner()
    {
        return $this->belongsTo(User::class, 'product_owner_id');
    }
    
    public function match()
    {
        return $this->belongsTo(\App\Models\UserMatch::class, 'match_id');
    }
    
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * Dating-specific methods
     */
    
    public function getOtherUser($userId)
    {
        if ($this->customer_id == $userId) {
            return $this->productOwner;
        } elseif ($this->product_owner_id == $userId) {
            return $this->customer;
        }
        return null;
    }
    
    public function isUserBlocked($userId)
    {
        if ($this->customer_id == $userId) {
            return $this->blocked_by_owner;
        } elseif ($this->product_owner_id == $userId) {
            return $this->blocked_by_customer;
        }
        return false;
    }
    
    public function blockUser($blockerId, $blockedId)
    {
        if ($this->customer_id == $blockerId) {
            $this->blocked_by_customer = true;
        } elseif ($this->product_owner_id == $blockerId) {
            $this->blocked_by_owner = true;
        }
        
        if ($this->blocked_by_customer || $this->blocked_by_owner) {
            $this->is_blocked = true;
        }
        
        $this->save();
        return $this;
    }
    
    public function unblockUser($unblockerId)
    {
        if ($this->customer_id == $unblockerId) {
            $this->blocked_by_customer = false;
        } elseif ($this->product_owner_id == $unblockerId) {
            $this->blocked_by_owner = false;
        }
        
        $this->is_blocked = $this->blocked_by_customer || $this->blocked_by_owner;
        $this->save();
        return $this;
    }
    
    public function setTypingStatus($userId, $isTyping = true)
    {
        if ($this->customer_id == $userId) {
            $this->is_typing_customer = $isTyping;
        } elseif ($this->product_owner_id == $userId) {
            $this->is_typing_owner = $isTyping;
        }
        
        $this->save();
        return $this;
    }
    
    public function getTypingStatus($userId)
    {
        $otherUser = $this->getOtherUser($userId);
        if (!$otherUser) {
            return false;
        }
        
        if ($this->customer_id == $otherUser->id) {
            return $this->is_typing_customer;
        } elseif ($this->product_owner_id == $otherUser->id) {
            return $this->is_typing_owner;
        }
        
        return false;
    }
    
    public function markMessagesAsRead($userId)
    {
        $unreadMessages = ChatMessage::where('chat_head_id', $this->id)
            ->where('receiver_id', $userId)
            ->where('status', '!=', 'read')
            ->get();
            
        foreach ($unreadMessages as $message) {
            $message->status = 'read';
            $message->read_at = now();
            $message->save();
        }
        
        // Reset unread count
        if ($this->customer_id == $userId) {
            $this->receiver_unread_count = 0;
        } elseif ($this->product_owner_id == $userId) {
            $this->sender_unread_count = 0;
        }
        
        $this->save();
        return $this;
    }
    
    public function updateLastActivity()
    {
        $this->last_message_time = now();
        $this->save();
        return $this;
    }

    /**
     * Static methods for dating chat creation
     */
    
    public static function createDatingChat($userId1, $userId2, $matchId = null)
    {
        // Check if chat already exists
        $existingChat = self::where(function($query) use ($userId1, $userId2) {
            $query->where('customer_id', $userId1)
                  ->where('product_owner_id', $userId2);
        })->orWhere(function($query) use ($userId1, $userId2) {
            $query->where('customer_id', $userId2)
                  ->where('product_owner_id', $userId1);
        })->where('type', 'dating')->first();
        
        if ($existingChat) {
            return $existingChat;
        }
        
        $user1 = User::find($userId1);
        $user2 = User::find($userId2);
        
        if (!$user1 || !$user2) {
            return null;
        }
        
        $chatHead = new self();
        $chatHead->customer_id = $user1->id;
        $chatHead->customer_name = $user1->name;
        $chatHead->customer_photo = $user1->avatar;
        $chatHead->product_owner_id = $user2->id;
        $chatHead->product_owner_name = $user2->name;
        $chatHead->product_owner_photo = $user2->avatar;
        $chatHead->type = 'dating';
        $chatHead->match_id = $matchId;
        $chatHead->last_message_body = '';
        $chatHead->last_message_time = now();
        $chatHead->last_message_status = 'sent';
        $chatHead->conversation_started_at = now();
        $chatHead->save();
        
        return $chatHead;
    }

    /**
     * Scopes
     */
    
    public function scopeDatingChats($query)
    {
        return $query->where('type', 'dating');
    }
    
    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('customer_id', $userId)
                    ->orWhere('product_owner_id', $userId);
    }
    
    public function scopeWithRecentActivity($query, $days = 7)
    {
        return $query->where('last_message_time', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Accessors
     */
    
    public function getIsDatingChatAttribute()
    {
        return $this->type === 'dating';
    }
    
    public function getConversationAgeAttribute()
    {
        return $this->conversation_started_at ? 
               $this->conversation_started_at->diffForHumans() : 
               null;
    }
    
    public function getLastActivityAttribute()
    {
        return $this->last_message_time ? 
               $this->last_message_time->diffForHumans() : 
               null;
    }

    protected $appends = [
        'customer_unread_messages_count', 
        'product_owner_unread_messages_count',
        'is_dating_chat',
        'conversation_age',
        'last_activity'
    ];
}
