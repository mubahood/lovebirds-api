<?php

namespace App\Services;

use App\Models\User;
use App\Models\ProfileBoost;
use Carbon\Carbon;

class SubscriptionManager
{
    /**
     * Check if user has active premium subscription
     */
    public function hasActiveSubscription($user)
    {
        // For now, we'll use a simple premium flag
        // In production, integrate with actual subscription system
        return $user->subscription_type === 'premium' && 
               $user->subscription_expires_at && 
               $user->subscription_expires_at > now();
    }

    /**
     * Check if user can use boost feature
     */
    public function canUseBoost($user)
    {
        // Premium users get unlimited boosts
        if ($this->hasActiveSubscription($user)) {
            return true;
        }
        
        // Non-premium users need boost credits
        return $user->boost_credits > 0;
    }

    /**
     * Use a boost (deduct credit or log premium usage)
     */
    public function useBoost($user)
    {
        if ($this->hasActiveSubscription($user)) {
            // Premium users - just log the usage
            $user->total_boosts_used++;
            $user->last_boosted_at = now();
            $user->save();
            return true;
        }
        
        // Non-premium users - deduct credit
        if ($user->boost_credits > 0) {
            $user->boost_credits--;
            $user->total_boosts_used++;
            $user->last_boosted_at = now();
            $user->save();
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can use super like feature
     */
    public function canUseSuperLike($user)
    {
        // Check daily limit for free users
        if (!$this->hasActiveSubscription($user)) {
            $todaySuperLikes = \App\Models\UserLike::where('user_id', $user->id)
                                                  ->where('type', 'super_like')
                                                  ->whereDate('created_at', today())
                                                  ->count();
            return $todaySuperLikes < 1; // 1 free super like per day
        }
        
        // Premium users get more super likes
        $todaySuperLikes = \App\Models\UserLike::where('user_id', $user->id)
                                              ->where('type', 'super_like')
                                              ->whereDate('created_at', today())
                                              ->count();
        return $todaySuperLikes < 5; // 5 super likes per day for premium
    }

    /**
     * Get available boosts for user
     */
    public function getAvailableBoosts($user)
    {
        if ($this->hasActiveSubscription($user)) {
            return [
                'type' => 'unlimited',
                'count' => 'unlimited',
                'source' => 'premium_subscription'
            ];
        }
        
        return [
            'type' => 'credits',
            'count' => $user->boost_credits,
            'source' => 'boost_credits'
        ];
    }

    /**
     * Get subscription status and limits
     */
    public function getSubscriptionStatus($user)
    {
        $isPremium = $this->hasActiveSubscription($user);
        
        // Get today's usage
        $todaySuperLikes = \App\Models\UserLike::where('user_id', $user->id)
                                              ->where('type', 'super_like')
                                              ->whereDate('created_at', today())
                                              ->count();
        
        $dailySwipes = \App\Models\UserLike::where('user_id', $user->id)
                                          ->whereDate('created_at', today())
                                          ->count();

        return [
            'is_premium' => $isPremium,
            'subscription_type' => $user->subscription_type ?? 'free',
            'subscription_expires_at' => $user->subscription_expires_at,
            'boost_credits' => $user->boost_credits,
            'limits' => [
                'daily_swipes' => $isPremium ? 'unlimited' : '50',
                'daily_super_likes' => $isPremium ? 5 : 1,
                'boosts' => $isPremium ? 'unlimited' : $user->boost_credits
            ],
            'usage_today' => [
                'swipes' => $dailySwipes,
                'super_likes' => $todaySuperLikes,
                'swipes_remaining' => $isPremium ? 'unlimited' : max(0, 50 - $dailySwipes),
                'super_likes_remaining' => $isPremium ? max(0, 5 - $todaySuperLikes) : max(0, 1 - $todaySuperLikes)
            ]
        ];
    }

    /**
     * Get premium subscription pricing (Canadian)
     */
    public function getSubscriptionPricing()
    {
        return [
            'currency' => 'CAD',
            'plans' => [
                'monthly' => [
                    'price' => 19.99,
                    'duration' => '1 month',
                    'save_percentage' => 0,
                    'features' => [
                        'Unlimited swipes',
                        '5 super likes per day',
                        'Unlimited boosts',
                        'See who liked you',
                        'Undo unlimited swipes',
                        'Advanced filters',
                        'Read receipts',
                        'Priority support'
                    ]
                ],
                'quarterly' => [
                    'price' => 49.99,
                    'monthly_equivalent' => 16.66,
                    'duration' => '3 months',
                    'save_percentage' => 17,
                    'features' => [
                        'All monthly features',
                        'Extra visibility boost',
                        'Priority matching'
                    ]
                ],
                'yearly' => [
                    'price' => 159.99,
                    'monthly_equivalent' => 13.33,
                    'duration' => '12 months',
                    'save_percentage' => 33,
                    'popular' => true,
                    'features' => [
                        'All quarterly features',
                        'Premium badge',
                        'Enhanced profile analytics',
                        'Exclusive events access'
                    ]
                ]
            ],
            'individual_features' => [
                'single_boost' => [
                    'price' => 2.99,
                    'duration' => '30 minutes',
                    'description' => '3x more profile visibility'
                ],
                'super_like_pack' => [
                    'price' => 4.99,
                    'quantity' => 5,
                    'description' => 'Stand out with super likes'
                ]
            ]
        ];
    }

    /**
     * Purchase boost credits
     */
    public function purchaseBoostCredits($user, $quantity = 1)
    {
        $user->boost_credits += $quantity;
        $user->save();
        
        return [
            'success' => true,
            'credits_added' => $quantity,
            'new_balance' => $user->boost_credits,
            'message' => "Successfully added {$quantity} boost credit(s) to your account."
        ];
    }

    /**
     * Grant free boost (promotional, etc.)
     */
    public function grantFreeBoost($user, $reason = 'promotional')
    {
        $user->boost_credits++;
        $user->save();
        
        return [
            'success' => true,
            'reason' => $reason,
            'new_balance' => $user->boost_credits,
            'message' => 'You received a free boost!'
        ];
    }
}
