<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProfileBoost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'boost_type',
        'status',
        'started_at',
        'expires_at',
        'visibility_multiplier',
        'views_generated',
        'likes_generated',
        'matches_generated'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'visibility_multiplier' => 'float',
        'views_generated' => 'integer',
        'likes_generated' => 'integer',
        'matches_generated' => 'integer'
    ];

    /**
     * Get the user that owns the boost
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if boost is currently active
     */
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->expires_at > now();
    }

    /**
     * Get boost effectiveness (percentage)
     */
    public function getEffectivenessAttribute()
    {
        if (!$this->started_at || !$this->expires_at) {
            return 0;
        }
        
        $totalDuration = $this->started_at->diffInMinutes($this->expires_at);
        $elapsed = $this->started_at->diffInMinutes(now());
        
        return min(100, ($elapsed / $totalDuration) * 100);
    }

    /**
     * Get remaining time in minutes
     */
    public function getRemainingTimeAttribute()
    {
        if (!$this->isActive()) {
            return 0;
        }
        
        return max(0, $this->expires_at->diffInMinutes(now()));
    }

    /**
     * Scope for active boosts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired boosts
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Automatically expire old boosts
     */
    public static function expireOldBoosts()
    {
        return self::where('status', 'active')
                   ->where('expires_at', '<=', now())
                   ->update(['status' => 'expired']);
    }

    /**
     * Get boost statistics
     */
    public function getStatsAttribute()
    {
        return [
            'duration_minutes' => $this->started_at ? $this->started_at->diffInMinutes($this->expires_at) : 0,
            'elapsed_minutes' => $this->started_at ? $this->started_at->diffInMinutes(now()) : 0,
            'remaining_minutes' => $this->remaining_time,
            'effectiveness_percentage' => $this->effectiveness,
            'views_generated' => $this->views_generated ?? 0,
            'likes_generated' => $this->likes_generated ?? 0,
            'matches_generated' => $this->matches_generated ?? 0
        ];
    }
}
