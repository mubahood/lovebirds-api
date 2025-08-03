<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Log;


class User extends Administrator implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'admin_users';

    //company
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $name = "";
            if ($model->first_name != null && strlen($model->first_name) > 0) {
                $name = $model->first_name;
            }
            if ($model->last_name != null && strlen($model->last_name) > 0) {
                $name .= " " . $model->last_name;
            }
            $name = trim($name);

            if ($name != null && strlen($name) > 0) {
                $model->name = $name;
            }
            $model->username = $model->email;

            if ($model->password == null || strlen($model->password) < 3) {
                $model->password = bcrypt('admin');
            }

            if ($model->phone_number == null && strlen($model->phone_number) > 6) {
                $phone_number = $model->phone_number;
                $existing_user = User::where('phone_number', $phone_number)->first();
                if ($existing_user != null) {
                    throw new \Exception('Phone number already exists');
                }
            }

            if ($model->email == null && strlen($model->email) > 6) {
                $email = $model->email;
                $existing_user = User::where('email', $email)->first();
                if ($existing_user != null) {
                    throw new \Exception('Email already exists');
                }
            }

            //do the same for username
            if ($model->username == null && strlen($model->username) > 6) {
                $username = $model->username;
                $existing_user = User::where('username', $username)->first();
                if ($existing_user != null) {
                    throw new \Exception('Username already exists');
                }
            }

            return $model;
        });


        static::updating(function ($model) {
            $name = "";
            if ($model->first_name != null && strlen($model->first_name) > 0) {
                $name = $model->first_name;
            }
            if ($model->last_name != null && strlen($model->last_name) > 0) {
                $name .= " " . $model->last_name;
            }
            $name = trim($name);

            if ($name != null && strlen($name) > 0) {
                $model->name = $name;
            }

            if ($model->phone_number == null && strlen($model->phone_number) > 6) {
                $phone_number = $model->phone_number;
                $existing_user = User::where('phone_number', $phone_number)->where('id', '!=', $model->id)->first();
                if ($existing_user != null) {
                    throw new \Exception('Phone number already exists');
                }
            }
            if ($model->email == null && strlen($model->email) > 6) {
                $email = $model->email;
                $existing_user = User::where('email', $email)->where('id', '!=', $model->id)->first();
                if ($existing_user != null) {
                    throw new \Exception('Email already exists');
                }
            }
            //do the same for username
            if ($model->username == null && strlen($model->username) > 6) {
                $username = $model->username;
                $existing_user = User::where('username', $username)->where('id', '!=', $model->id)->first();
                if ($existing_user != null) {
                    throw new \Exception('Username already exists');
                }
            }

            $model->username = $model->email;
            return $model;
        });

        // Handle manual cascade deletes for moderation-related data
        static::deleting(function ($user) {
            // Delete content reports where user is reporter or reported user
            \App\Models\ContentReport::where('reporter_id', $user->id)->delete();
            \App\Models\ContentReport::where('reported_user_id', $user->id)->delete();
            
            // Delete user blocks where user is blocker or blocked
            \App\Models\UserBlock::where('blocker_id', $user->id)->delete();
            \App\Models\UserBlock::where('blocked_user_id', $user->id)->delete();
            
            // Delete moderation logs for this user
            \App\Models\ContentModerationLog::where('user_id', $user->id)->delete();
            
            // Set moderator_id to null for logs where this user was the moderator
            \App\Models\ContentModerationLog::where('moderator_id', $user->id)
                ->update(['moderator_id' => null]);
                
            // Set moderator_id to null for reports where this user was the moderator
            \App\Models\ContentReport::where('moderator_id', $user->id)
                ->update(['moderator_id' => null]);
        });
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Basic auth fields
        'name',
        'email',
        'password',
        
        // Basic profile fields
        'first_name',
        'last_name',
        'phone_number',
        'phone_number_2',
        'address',
        'sex',
        'dob',
        'status',
        'avatar',
        'bio',
        'tagline',
        
        // Location fields
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
        'hometown',
        'current_city',
        
        // Physical attributes
        'height_cm',
        'body_type',
        'eye_color',
        'hair_color',
        'ethnicity',
        'sexual_orientation',
        
        // Kids & Family Planning
        'wants_kids',
        'has_kids',
        'kids_count',
        
        // Interests & Lifestyle
        'interests',
        'lifestyle',
        'relationship_type',
        'relationship_status',
        'smoking_habit',
        'drinking_habit',
        'exercise_frequency',
        'pet_preference',
        'religion',
        'political_views',
        'education_level',
        'occupation',
        
        // Dating preferences
        'looking_for',
        'interested_in',
        'age_range_min',
        'age_range_max',
        'max_distance_km',
        'deal_breakers',
        'ideal_partner',
        
        // Social & Personality
        'personality_type',
        'social_media_links',
        'communication_style',
        'languages_spoken',
        'languages_fluent',
        'cultural_background',
        'zodiac_sign',
        
        // Dating specific
        'first_date_preference',
        'date_ideas',
        'travel_frequency',
        'distance_preference',
        
        // Verification & Safety
        'photo_verified',
        'identity_verified',
        'verification_documents',
        'email_verified',
        'phone_verified',
        
        // Activity & Engagement
        'profile_created_at',
        'last_profile_update',
        'total_likes_sent',
        'total_messages_sent',
        'total_profile_visits',
        'last_online_at',
        'online_status',
        
        // Premium Features
        'boost_active',
        'boost_expires_at',
        'super_likes_remaining',
        'premium_features_expire',
        'subscription_tier',
        'subscription_expires',
        'credits_balance',
        'profile_views',
        'likes_received',
        'matches_count',
        'completed_profile_pct',
        
        // Matching Algorithm Data
        'matching_preferences',
        'matching_score_threshold',
        'show_me',
        'show_age',
        'show_distance',
        
        // Privacy & Visibility
        'profile_visibility',
        'last_seen_visibility',
        'read_receipts',
        'typing_indicator',
        
        // Moderation & Safety
        'account_status',
        'suspension_reason',
        'suspension_expires_at',
        'reports_count',
        'blocks_count',
        
        // Profile photos
        'profile_photos',
        
        // Additional Metadata
        'profile_completion_steps',
        'onboarding_completed',
        'app_preferences',
        'notification_settings',
        
        // Legal consent
        'terms_of_service_accepted',
        'privacy_policy_accepted',
        'community_guidelines_accepted',
        'marketing_emails_consent',
        
        // Other fields
        'company_id',
        'verification_code',
        'failed_login_attempts',
        'last_password_change',
        'secret_code',
        'phone_country_name',
        'phone_country_code',
        'phone_country_international',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    // getter for avatar
    public function getAvatarAttribute($value)
    {
        return $value;
        if ($value == null || strlen($value) < 3) {
            return url('logo.png');
        }
        $path = public_path('storage/' . $value);
        if (!file_exists($path)) {
            return url('logo.png');
        }
        return $value;
    }

    //getter for online_status
    public function getOnlineStatusAttribute($value)
    {
        $last_online_at = $this->last_online_at;
        if ($last_online_at == null || strlen($last_online_at) < 3) {
            $this->last_online_at = $this->updated_at;
            $this->save();
        }
        $last_online_at = null;
        try {
            $last_online_at = \Carbon\Carbon::parse($this->last_online_at);
        } catch (\Exception $e) {
            return 'Offline';
        }
        $now = \Carbon\Carbon::now();
        //mins ago
        $diff = $last_online_at->diffInMinutes($now);
        if ($diff < 25) {
            return 'Online';
        }
        return Utils::time_ago($last_online_at) . ' ago';
    }

    //setter for languages_spoken if is array to json

    /**
     * Moderation-related relationships
     */
    
    // Content reports made by this user
    public function contentReports()
    {
        return $this->hasMany(\App\Models\ContentReport::class, 'reporter_id');
    }
    
    // Content reports about this user
    public function reportsAgainst()
    {
        return $this->hasMany(\App\Models\ContentReport::class, 'reported_user_id');
    }
    
    // Reports moderated by this user (if admin/moderator)
    public function moderatedReports()
    {
        return $this->hasMany(\App\Models\ContentReport::class, 'moderator_id');
    }
    
    // Users blocked by this user
    public function blockedUsers()
    {
        return $this->hasMany(\App\Models\UserBlock::class, 'blocker_id');
    }
    
    // Users who blocked this user
    public function blockedBy()
    {
        return $this->hasMany(\App\Models\UserBlock::class, 'blocked_user_id');
    }
    
    // Moderation logs for this user
    public function moderationLogs()
    {
        return $this->hasMany(\App\Models\ContentModerationLog::class, 'user_id');
    }
    
    // Moderation actions taken by this user (if admin/moderator)
    public function moderationActions()
    {
        return $this->hasMany(\App\Models\ContentModerationLog::class, 'moderator_id');
    }
    
    /**
     * Check if user is blocked by another user
     */
    public function isBlockedBy($userId)
    {
        return \App\Models\UserBlock::where('blocker_id', $userId)
            ->where('blocked_user_id', $this->id)
            ->active()
            ->exists();
    }
    
    /**
     * Check if user has blocked another user  
     */
    public function hasBlocked($userId)
    {
        return \App\Models\UserBlock::where('blocker_id', $this->id)
            ->where('blocked_user_id', $userId)
            ->active()
            ->exists();
    }

    /**
     * ===============================================
     * DATING-SPECIFIC FUNCTIONALITY
     * ===============================================
     */

    /**
     * Dating Relationships
     */
    
    // Likes sent by this user
    public function likesSent()
    {
        return $this->hasMany(\App\Models\UserLike::class, 'liker_id');
    }
    
    // Likes received by this user
    public function likesReceived()
    {
        return $this->hasMany(\App\Models\UserLike::class, 'liked_user_id');
    }
    
    // Matches where this user is involved
    public function matches()
    {
        return $this->hasMany(\App\Models\UserMatch::class, 'user_id')
            ->orWhere('matched_user_id', $this->id);
    }
    
    // Chat heads where this user is involved
    public function chatHeads()
    {
        return $this->hasMany(\App\Models\ChatHead::class, 'customer_id')
            ->orWhere('product_owner_id', $this->id);
    }
    
    // Messages sent by this user
    public function messagesSent()
    {
        return $this->hasMany(\App\Models\ChatMessage::class, 'sender_id');
    }
    
    // Messages received by this user
    public function messagesReceived()
    {
        return $this->hasMany(\App\Models\ChatMessage::class, 'receiver_id');
    }

    /**
     * Dating Profile Accessors & Mutators
     */
    
    // Profile Photos accessor (JSON to array)
    public function getProfilePhotosAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return is_string($value) ? json_decode($value, true) : $value;
    }
    
    // Profile Photos mutator (array to JSON)
    public function setProfilePhotosAttribute($value)
    {
        $this->attributes['profile_photos'] = is_array($value) ? json_encode($value) : $value;
    }
    
    // Interests accessor (JSON to array)
    public function getInterestsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return is_string($value) ? json_decode($value, true) : $value;
    }
    
    // Interests mutator (array to JSON)
    public function setInterestsAttribute($value)
    {
        $this->attributes['interests'] = is_array($value) ? json_encode($value) : $value;
    }
    
    // Lifestyle accessor
    public function getLifestyleAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return is_string($value) ? json_decode($value, true) : $value;
    }
    
    // Lifestyle mutator
    public function setLifestyleAttribute($value)
    {
        $this->attributes['lifestyle'] = is_array($value) ? json_encode($value) : $value;
    }
    
    // Languages spoken accessor
    public function getLanguagesSpokenAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return is_string($value) ? json_decode($value, true) : $value;
    }
    
    // Languages spoken mutator
    public function setLanguagesSpokenAttribute($value)
    {
        $this->attributes['languages_spoken'] = is_array($value) ? json_encode($value) : $value;
    }
    
    // Age calculation from date of birth
    public function getAgeAttribute()
    {
        if (!$this->dob) {
            return null;
        }
        return \Carbon\Carbon::parse($this->dob)->age;
    }
    
    // Primary photo getter
    public function getPrimaryPhotoAttribute()
    {
        $photos = $this->profile_photos;
        if (empty($photos) || !is_array($photos)) {
            return $this->avatar ?? null;
        }
        
        // Find photo marked as primary
        foreach ($photos as $photo) {
            if (isset($photo['is_primary']) && $photo['is_primary'] === true) {
                return $photo['url'] ?? $photo['path'] ?? null;
            }
        }
        
        // Return first photo if no primary set
        return $photos[0]['url'] ?? $photos[0]['path'] ?? null;
    }
    
    // Profile completion percentage
    public function getProfileCompletionAttribute()
    {
        $fields = [
            'first_name', 'last_name', 'bio', 'dob', 'city', 'occupation',
            'education_level', 'interests', 'looking_for', 'profile_photos'
        ];
        
        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }
        
        return round(($completed / count($fields)) * 100);
    }

    /**
     * Dating-Specific Helper Methods
     */
    
    // Check if user has liked another user
    public function hasLiked($userId)
    {
        return $this->likesSent()->where('liked_user_id', $userId)->where('status', 'Active')->exists();
    }
    
    // Check if user has been liked by another user
    public function isLikedBy($userId)
    {
        return $this->likesReceived()->where('liker_id', $userId)->where('status', 'Active')->exists();
    }
    
    // Check if users are matched
    public function isMatchedWith($userId)
    {
        return \App\Models\UserMatch::where(function($query) use ($userId) {
            $query->where('user_id', $this->id)->where('matched_user_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('user_id', $userId)->where('matched_user_id', $this->id);
        })->where('status', 'Active')->exists();
    }

    // Like another user
    public function likeUser($userId, $type = 'like', $message = null)
    {
        return \App\Models\UserLike::createLike($this->id, $userId, $type, $message);
    }

    // Pass on another user
    public function passUser($userId)
    {
        return \App\Models\UserLike::createPass($this->id, $userId);
    }

    // Super like another user
    public function superLikeUser($userId, $message)
    {
        return \App\Models\UserLike::createSuperLike($this->id, $userId, $message);
    }

    // Get daily likes remaining
    public function getDailyLikesRemaining()
    {
        if ($this->hasActiveSubscription()) {
            return 'unlimited';
        }

        $dailyLimit = 50;
        $used = \App\Models\UserLike::getDailyLikesCount($this->id, 'like');
        return max(0, $dailyLimit - $used);
    }

    // Get daily super likes remaining
    public function getDailySuperLikesRemaining()
    {
        if ($this->hasActiveSubscription()) {
            return 5; // Premium users get 5 super likes per day
        }

        $dailyLimit = 1; // Free users get 1 super like per day
        $used = \App\Models\UserLike::getDailyLikesCount($this->id, 'super_like');
        return max(0, $dailyLimit - $used);
    }

    // Check if user has reached daily like limit
    public function hasReachedDailyLikeLimit()
    {
        if ($this->hasActiveSubscription()) {
            return false;
        }

        return $this->getDailyLikesRemaining() <= 0;
    }

    // Check if user has reached daily super like limit
    public function hasReachedDailySuperLikeLimit()
    {
        $remaining = $this->getDailySuperLikesRemaining();
        return is_numeric($remaining) && $remaining <= 0;
    }
    
    // Get distance between users (if both have coordinates)
    public function getDistanceFrom($otherUser)
    {
        if (!$this->latitude || !$this->longitude || !$otherUser->latitude || !$otherUser->longitude) {
            return null;
        }
        
        return $this->calculateDistance(
            $this->latitude, $this->longitude,
            $otherUser->latitude, $otherUser->longitude
        );
    }
    
    // Calculate distance between coordinates (Haversine formula)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c, 2);
    }
    
    // Check subscription status
    public function hasActiveSubscription()
    {
        if (!$this->subscription_expires) {
            return false;
        }
        
        return \Carbon\Carbon::parse($this->subscription_expires)->isFuture();
    }
    
    // Check if profile is verification complete
    public function isVerificationComplete()
    {
        return $this->email_verified === 'Yes' && 
               $this->phone_verified === 'Yes' && 
               $this->photo_verified === 'Yes';
    }
    
    // Get potential matches based on preferences
    public function getPotentialMatches($limit = 20)
    {
        $query = self::where('id', '!=', $this->id)
                    ->where('account_status', 'Active');
        
        // Age range filter
        if ($this->age_range_min && $this->age_range_max) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?', 
                [$this->age_range_min, $this->age_range_max]);
        }
        
        // Distance filter (if user has location)
        if ($this->latitude && $this->longitude && $this->max_distance_km) {
            $query->whereRaw("
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                sin(radians(latitude)))) <= ?
            ", [$this->latitude, $this->longitude, $this->latitude, $this->max_distance_km]);
        }
        
        // Exclude already liked/matched users
        $likedUserIds = $this->likesSent()->pluck('liked_user_id')->toArray();
        $blockedUserIds = $this->blockedUsers()->pluck('blocked_user_id')->toArray();
        $blockedByUserIds = $this->blockedBy()->pluck('blocker_id')->toArray();
        
        $excludeIds = array_merge($likedUserIds, $blockedUserIds, $blockedByUserIds);
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        return $query->inRandomOrder()->limit($limit)->get();
    }
    
    // Update last online status
    public function updateLastOnline()
    {
        $this->last_online_at = now();
        $this->save();
    }
    
    // Calculate profile completeness percentage
    public function calculateProfileCompleteness()
    {
        $requiredFields = [
            'name', 'email', 'bio', 'dob', 'sex', 'sexual_orientation',
            'height_cm', 'body_type', 'country', 'city', 'occupation',
            'education_level', 'looking_for', 'interested_in'
        ];
        
        $optionalFields = [
            'tagline', 'smoking_habit', 'drinking_habit', 'religion',
            'wants_kids', 'has_kids', 'exercise_frequency', 'eye_color',
            'hair_color', 'ethnicity'
        ];
        
        $completedRequired = 0;
        $completedOptional = 0;
        
        // Check required fields (70% weight)
        foreach ($requiredFields as $field) {
            if (!empty($this->$field) && $this->$field !== null) {
                $completedRequired++;
            }
        }
        
        // Check optional fields (20% weight)
        foreach ($optionalFields as $field) {
            if (!empty($this->$field) && $this->$field !== null) {
                $completedOptional++;
            }
        }
        
        // Check profile photos (10% weight)
        $photoScore = 0;
        if (!empty($this->avatar)) {
            $photoScore = 10;
        }
        
        // Calculate weighted score
        $requiredScore = ($completedRequired / count($requiredFields)) * 70;
        $optionalScore = ($completedOptional / count($optionalFields)) * 20;
        
        return min(100, round($requiredScore + $optionalScore + $photoScore));
    }
    
    // Send notification helper
    public function sendNotification($data)
    {
        // Implementation will depend on notification service (OneSignal, etc.)
        try {
            Utils::sendNotificationToUser($this, $data);
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user ' . $this->id . ': ' . $e->getMessage());
        }
    }
}
