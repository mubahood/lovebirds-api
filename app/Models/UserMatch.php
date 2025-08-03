<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserLike;
use App\Models\ChatHead;
use Carbon\Carbon;

class UserMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'matched_user_id', 'status', 'matched_at', 'last_message_at',
        'match_type', 'messages_count', 'conversation_starter', 'match_reason',
        'compatibility_score', 'is_conversation_started', 'unmatched_at',
        'unmatched_by', 'unmatch_reason', 'metadata'
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'last_message_at' => 'datetime',
        'unmatched_at' => 'datetime',
        'compatibility_score' => 'decimal:2',
    ];

    /**
     * Boot method - Handle cascading operations in model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->matched_at) {
                $model->matched_at = now();
            }
        });

        static::deleting(function ($model) {
            // Mark related likes as not mutual
            UserLike::where(function($query) use ($model) {
                $query->where('liker_id', $model->user_id)
                      ->where('liked_user_id', $model->matched_user_id);
            })->orWhere(function($query) use ($model) {
                $query->where('liker_id', $model->matched_user_id)
                      ->where('liked_user_id', $model->user_id);
            })->update(['is_mutual' => 'No']);
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    public function unmatchedBy()
    {
        return $this->belongsTo(User::class, 'unmatched_by');
    }

    public function getChatHead()
    {
        return ChatHead::where(function($query) {
            $query->where('customer_id', $this->user_id)
                  ->where('product_owner_id', $this->matched_user_id);
        })->orWhere(function($query) {
            $query->where('customer_id', $this->matched_user_id)
                  ->where('product_owner_id', $this->user_id);
        })->where('type', 'dating')->first();
    }

    public function userLikes()
    {
        return UserLike::where(function($query) {
            $query->where('liker_id', $this->user_id)
                  ->where('liked_user_id', $this->matched_user_id);
        })->orWhere(function($query) {
            $query->where('liker_id', $this->matched_user_id)
                  ->where('liked_user_id', $this->user_id);
        });
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeUnmatched($query)
    {
        return $query->where('status', 'Unmatched');
    }

    public function scopeWithConversation($query)
    {
        return $query->where('is_conversation_started', 'Yes');
    }

    public function scopeRecentMatches($query, $days = 30)
    {
        return $query->where('matched_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Helper methods
     */
    public static function createMatch($userId1, $userId2, $matchType = 'Mutual')
    {
        // Ensure consistent ordering (smaller ID first)
        $userA = min($userId1, $userId2);
        $userB = max($userId1, $userId2);

        // Check if match already exists
        $existingMatch = self::where('user_id', $userA)
                            ->where('matched_user_id', $userB)
                            ->first();
        
        if ($existingMatch) {
            return $existingMatch;
        }

        $match = self::create([
            'user_id' => $userA,
            'matched_user_id' => $userB,
            'match_type' => $matchType,
            'status' => 'Active'
        ]);

        // Update user match counts
        User::whereIn('id', [$userA, $userB])->increment('matches_count');

        // Send notifications
        try {
            $user1 = User::find($userA);
            $user2 = User::find($userB);
            
            $user1->sendNotification([
                'title' => 'New Match! 💕',
                'body' => "You matched with {$user2->first_name}!",
                'type' => 'new_match',
                'match_id' => $match->id
            ]);
            
            $user2->sendNotification([
                'title' => 'New Match! 💕',
                'body' => "You matched with {$user1->first_name}!",
                'type' => 'new_match',
                'match_id' => $match->id
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the match creation
        }

        return $match;
    }

    public function unmatch($unmatchedBy, $reason = null)
    {
        $this->update([
            'status' => 'Unmatched',
            'unmatched_at' => now(),
            'unmatched_by' => $unmatchedBy,
            'unmatch_reason' => $reason
        ]);

        // Update user match counts
        User::whereIn('id', [$this->user_id, $this->matched_user_id])->decrement('matches_count');

        return $this;
    }

    public function updateLastMessage()
    {
        $this->update([
            'last_message_at' => now(),
            'is_conversation_started' => 'Yes'
        ]);
        
        $this->increment('messages_count');
    }

    public function getOtherUser($currentUserId)
    {
        return $currentUserId == $this->user_id ? $this->matchedUser : $this->user;
    }

    public function calculateCompatibilityScore()
    {
        $user1 = $this->user;
        $user2 = $this->matchedUser;
        
        $score = 0.0;
        $factors = 0;

        // Age compatibility (closer ages = higher score)
        if ($user1->age && $user2->age) {
            $ageDiff = abs($user1->age - $user2->age);
            $ageScore = max(0, 1 - ($ageDiff / 20)); // 20 year max difference
            $score += $ageScore;
            $factors++;
        }

        // Location compatibility
        if ($user1->latitude && $user2->latitude) {
            $distance = $user1->getDistanceFrom($user2);
            if ($distance !== null) {
                $distanceScore = max(0, 1 - ($distance / 100)); // 100km max
                $score += $distanceScore;
                $factors++;
            }
        }

        // Interest compatibility
        $interests1 = $user1->interests ?? [];
        $interests2 = $user2->interests ?? [];
        if (!empty($interests1) && !empty($interests2)) {
            $commonInterests = array_intersect($interests1, $interests2);
            $interestScore = count($commonInterests) / max(count($interests1), count($interests2));
            $score += $interestScore;
            $factors++;
        }

        // Education compatibility
        if ($user1->education_level && $user2->education_level) {
            $educationScore = $user1->education_level === $user2->education_level ? 1.0 : 0.5;
            $score += $educationScore;
            $factors++;
        }

        $finalScore = $factors > 0 ? $score / $factors : 0.5;
        
        $this->update(['compatibility_score' => round($finalScore, 2)]);
        
        return $finalScore;
    }

    public function isActive()
    {
        return $this->status === 'Active';
    }

    public function hasConversation()
    {
        return $this->is_conversation_started === 'Yes';
    }

    /**
     * Accessors & Mutators
     */
    public function getMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Events and Observers
     */
    public function sendMatchNotification()
    {
        try {
            $user1 = $this->user;
            $user2 = $this->matchedUser;
            
            $user1->sendNotification([
                'title' => 'It\'s a Match! 💕',
                'body' => "You and {$user2->first_name} liked each other!",
                'type' => 'match',
                'match_id' => $this->id
            ]);
            
            $user2->sendNotification([
                'title' => 'It\'s a Match! 💕',
                'body' => "You and {$user1->first_name} liked each other!",
                'type' => 'match',
                'match_id' => $this->id
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send match notification: ' . $e->getMessage());
        }
    }

    /**
     * Analytics & Insights
     */
    public function getDaysActive()
    {
        return $this->matched_at ? $this->matched_at->diffInDays(now()) + 1 : 0;
    }

    public function getActivityLevel()
    {
        $days = $this->getDaysActive();
        $messagesPerDay = $days > 0 ? $this->messages_count / $days : 0;
        
        if ($messagesPerDay >= 5) return 'High';
        if ($messagesPerDay >= 2) return 'Medium';
        if ($messagesPerDay > 0) return 'Low';
        return 'None';
    }

    /**
     * Query helpers
     */
    public static function getRecentMatches($userId, $limit = 10)
    {
        return self::where(function($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhere('matched_user_id', $userId);
        })
        ->where('status', 'Active')
        ->orderBy('matched_at', 'desc')
        ->limit($limit)
        ->get();
    }

    public static function getActiveMatchesCount($userId)
    {
        return self::where(function($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhere('matched_user_id', $userId);
        })
        ->where('status', 'Active')
        ->count();
    }

    public static function getConversationMatches($userId)
    {
        return self::where(function($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhere('matched_user_id', $userId);
        })
        ->where('status', 'Active')
        ->where('is_conversation_started', 'Yes')
        ->orderBy('last_message_at', 'desc')
        ->get();
    }
}
