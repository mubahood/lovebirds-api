<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'liker_id', 'liked_user_id', 'type', 'status', 'message', 
        'liked_at', 'expires_at', 'is_mutual', 'metadata'
    ];

    protected $casts = [
        'liked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot method - Handle cascading operations in model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->liked_at) {
                $model->liked_at = now();
            }
            
            // Check if this creates a mutual like (match)
            $mutualLike = self::where('liker_id', $model->liked_user_id)
                            ->where('liked_user_id', $model->liker_id)
                            ->where('status', 'Active')
                            ->first();
            
            if ($mutualLike) {
                // Mark both likes as mutual
                $model->is_mutual = 'Yes';
                $mutualLike->is_mutual = 'Yes';
                $mutualLike->save();
                
                // Create a match
                UserMatch::createMatch($model->liker_id, $model->liked_user_id);
            }
        });

        static::deleting(function ($model) {
            // Handle cleanup when like is deleted
            if ($model->is_mutual === 'Yes') {
                // Remove the match as well
                UserMatch::where(function($query) use ($model) {
                    $query->where('user_id', $model->liker_id)
                          ->where('matched_user_id', $model->liked_user_id);
                })->orWhere(function($query) use ($model) {
                    $query->where('user_id', $model->liked_user_id)
                          ->where('matched_user_id', $model->liker_id);
                })->delete();
            }
        });
    }

    /**
     * Relationships
     */
    public function liker()
    {
        return $this->belongsTo(User::class, 'liker_id');
    }

    public function likedUser()
    {
        return $this->belongsTo(User::class, 'liked_user_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeMutual($query)
    {
        return $query->where('is_mutual', 'Yes');
    }

    public function scopeSuperLikes($query)
    {
        return $query->where('type', 'Super_Like');
    }

    /**
     * Helper methods
     */
    public static function createLike($likerId, $likedUserId, $type = 'like', $message = null)
    {
        // Check if like already exists
        $existingLike = self::where('liker_id', $likerId)
                           ->where('liked_user_id', $likedUserId)
                           ->first();
        
        if ($existingLike) {
            return $existingLike;
        }

        return self::create([
            'liker_id' => $likerId,
            'liked_user_id' => $likedUserId,
            'type' => $type,
            'message' => $message,
            'status' => 'Active'
        ]);
    }

    public static function createPass($likerId, $likedUserId)
    {
        return self::createLike($likerId, $likedUserId, 'pass');
    }

    public static function createSuperLike($likerId, $likedUserId, $message)
    {
        return self::createLike($likerId, $likedUserId, 'super_like', $message);
    }

    public function isExpired()
    {
        return $this->expires_at && Carbon::parse($this->expires_at)->isPast();
    }

    public function isMutual()
    {
        return $this->is_mutual === 'Yes';
    }

    public function isPass()
    {
        return $this->type === 'pass';
    }

    public function isLike()
    {
        return in_array($this->type, ['like', 'super_like']);
    }

    public function isSuperLike()
    {
        return $this->type === 'super_like';
    }

    /**
     * Get daily likes count for a user
     */
    public static function getDailyLikesCount($userId, $type = null)
    {
        $query = self::where('liker_id', $userId)
                    ->where('status', 'Active')
                    ->where('created_at', '>=', now()->startOfDay());

        if ($type) {
            $query->where('type', $type);
        } else {
            $query->whereIn('type', ['like', 'super_like']);
        }

        return $query->count();
    }

    /**
     * Check if users have mutual likes
     */
    public static function hasMutualLike($userId1, $userId2)
    {
        $like1 = self::where('liker_id', $userId1)
                    ->where('liked_user_id', $userId2)
                    ->whereIn('type', ['like', 'super_like'])
                    ->where('status', 'Active')
                    ->first();

        $like2 = self::where('liker_id', $userId2)
                    ->where('liked_user_id', $userId1)
                    ->whereIn('type', ['like', 'super_like'])
                    ->where('status', 'Active')
                    ->first();

        return $like1 && $like2;
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
}
