<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLike;
use App\Models\UserMatch;
use App\Models\UserBlock;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DatingDiscoveryService
{
    /**
     * Discover potential matches for a user based on comprehensive filters
     */
    public function discoverUsers(User $currentUser, Request $request)
    {
        $query = User::query()
            ->where('id', '!=', $currentUser->id)
            ->where('account_status', 'Active');

        // Apply all discovery filters
        // $this->applyBasicFilters($query, $currentUser, $request);
        // $this->applyLocationFilters($query, $currentUser, $request);
        // $this->applyPreferenceFilters($query, $currentUser, $request);
        // $this->applyCompatibilityFilters($query, $currentUser, $request);
        $this->excludeBlockedAndLikedUsers($query, $currentUser);
        
        // Apply sorting
        $this->applySorting($query, $currentUser, $request);

        return $query;
    }

    /**
     * Apply basic demographic and verification filters
     */
    private function applyBasicFilters($query, User $currentUser, Request $request)
    {
        // Gender preference filtering
        if ($currentUser->interested_in && $currentUser->interested_in !== 'both') {
            $query->where('gender', $currentUser->interested_in);
        }

        // Age range filtering
        if ($currentUser->age_range_min && $currentUser->age_range_max) {
            $minAge = $currentUser->age_range_min;
            $maxAge = $currentUser->age_range_max;
            
            $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?', 
                [$minAge, $maxAge]);
        }

        // Verification status filter
        if ($request->verified_only) {
            $query->where('email_verified', 'Yes')
                  ->where('phone_verified', 'Yes');
        }

        // Profile completeness filter
        if ($request->complete_profiles_only) {
            $query->whereNotNull('bio')
                  ->whereNotNull('dob')
                  ->whereNotNull('occupation')
                  ->where('profile_photos', '!=', '[]')
                  ->where('profile_photos', '!=', null);
        }

        // Recently active filter
        if ($request->recently_active) {
            $query->where('last_online_at', '>=', now()->subDays(7));
        }

        // Online status filter
        if ($request->online_only) {
            $query->where('last_online_at', '>=', now()->subMinutes(15));
        }
    }

    /**
     * Apply location-based filters
     */
    private function applyLocationFilters($query, User $currentUser, Request $request)
    {
        // Distance filter (requires user to have location)
        if ($currentUser->latitude && $currentUser->longitude) {
            $maxDistance = $request->max_distance ?? $currentUser->max_distance_km ?? 50;
            
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [
                $currentUser->latitude, 
                $currentUser->longitude, 
                $currentUser->latitude, 
                $maxDistance
            ]);
        }

        // City filter
        if ($request->city) {
            $query->where('city', 'LIKE', "%{$request->city}%");
        }

        // Country filter
        if ($request->country) {
            $query->where('country', $request->country);
        }

        // State/Province filter
        if ($request->state) {
            $query->where('state', $request->state);
        }
    }

    /**
     * Apply preference-based filters
     */
    private function applyPreferenceFilters($query, User $currentUser, Request $request)
    {
        // Interests matching
        if ($request->shared_interests && $currentUser->interests) {
            $userInterests = is_string($currentUser->interests) 
                ? json_decode($currentUser->interests, true) 
                : $currentUser->interests;
                
            if (is_array($userInterests) && !empty($userInterests)) {
                $query->where(function($q) use ($userInterests) {
                    foreach ($userInterests as $interest) {
                        $q->orWhereJsonContains('interests', $interest);
                    }
                });
            }
        }

        // Education level filter
        if ($request->education_level) {
            $query->where('education_level', $request->education_level);
        }

        // Religion filter
        if ($request->religion) {
            $query->where('religion', $request->religion);
        }

        // Lifestyle filters
        if ($request->smoking_habit) {
            $query->where('smoking_habit', $request->smoking_habit);
        }

        if ($request->drinking_habit) {
            $query->where('drinking_habit', $request->drinking_habit);
        }

        // Pet preference
        if ($request->pet_preference) {
            $query->where('pet_preference', $request->pet_preference);
        }

        // Looking for filter
        if ($request->looking_for) {
            $query->where('looking_for', $request->looking_for);
        }

        // Height range filter
        if ($request->min_height || $request->max_height) {
            if ($request->min_height) {
                $query->where('height', '>=', $request->min_height);
            }
            if ($request->max_height) {
                $query->where('height', '<=', $request->max_height);
            }
        }

        // Languages spoken filter
        if ($request->languages) {
            $languages = is_array($request->languages) 
                ? $request->languages 
                : explode(',', $request->languages);
                
            $query->where(function($q) use ($languages) {
                foreach ($languages as $language) {
                    $q->orWhereJsonContains('languages_spoken', trim($language));
                }
            });
        }
    }

    /**
     * Apply compatibility-based filters
     */
    private function applyCompatibilityFilters($query, User $currentUser, Request $request)
    {
        // Mutual interest check (both users match each other's preferences)
        if ($request->mutual_interest_only) {
            $query->where(function($q) use ($currentUser) {
                // Other user should be interested in current user's gender
                if ($currentUser->gender) {
                    $q->where('interested_in', $currentUser->gender)
                      ->orWhere('interested_in', 'both');
                }
            });
        }

        // Age compatibility (other user's age preference should include current user)
        if ($request->age_compatible_only && $currentUser->dob) {
            $currentUserAge = Carbon::parse($currentUser->dob)->age;
            
            $query->where(function($q) use ($currentUserAge) {
                $q->whereNull('age_range_min')
                  ->orWhere('age_range_min', '<=', $currentUserAge);
            })->where(function($q) use ($currentUserAge) {
                $q->whereNull('age_range_max')
                  ->orWhere('age_range_max', '>=', $currentUserAge);
            });
        }
    }

    /**
     * Exclude blocked and already liked/matched users
     */
    private function excludeBlockedAndLikedUsers($query, User $currentUser)
    {
        // Exclude blocked users
        $blockedUserIds = UserBlock::where('blocker_id', $currentUser->id)
            ->pluck('blocked_user_id')
            ->toArray();
            
        $blockedByUserIds = UserBlock::where('blocked_user_id', $currentUser->id)
            ->pluck('blocker_id')
            ->toArray();
            
        $allBlockedIds = array_merge($blockedUserIds, $blockedByUserIds);
        
        if (!empty($allBlockedIds)) {
            $query->whereNotIn('id', $allBlockedIds);
        }

        // Exclude already liked users (unless showing mutual likes)
        $likedUserIds = UserLike::where('liker_id', $currentUser->id)
            ->where('status', 'Active')
            ->pluck('liked_user_id')
            ->toArray();
            
        if (!empty($likedUserIds)) {
            $query->whereNotIn('id', $likedUserIds);
        }

        // Exclude already matched users (unless showing matches)
        $matchedUserIds = UserMatch::where(function($q) use ($currentUser) {
            $q->where('user_id', $currentUser->id)
              ->orWhere('matched_user_id', $currentUser->id);
        })->where('status', 'Active')
          ->get()
          ->map(function($match) use ($currentUser) {
              return $match->user_id == $currentUser->id 
                  ? $match->matched_user_id 
                  : $match->user_id;
          })
          ->toArray();
          
        if (!empty($matchedUserIds)) {
            $query->whereNotIn('id', $matchedUserIds);
        }
    }

    /**
     * Apply sorting logic
     */
    private function applySorting($query, User $currentUser, Request $request)
    {
        $sortBy = $request->sort_by ?? 'smart';

        switch ($sortBy) {
            case 'distance':
                if ($currentUser->latitude && $currentUser->longitude) {
                    $query->selectRaw("*, 
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + 
                            sin(radians(?)) * sin(radians(latitude))
                        )) as distance", [
                            $currentUser->latitude,
                            $currentUser->longitude,
                            $currentUser->latitude
                        ])
                        ->orderBy('distance', 'asc');
                }
                break;

            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;

            case 'most_active':
                $query->orderBy('last_online_at', 'desc');
                break;

            case 'profile_complete':
                $query->orderByRaw('completed_profile_pct DESC');
                break;

            case 'age_asc':
                $query->orderBy('dob', 'desc'); // Younger first
                break;

            case 'age_desc':
                $query->orderBy('dob', 'asc'); // Older first
                break;

            case 'smart':
            default:
                // Smart sorting: combine multiple factors
                if ($currentUser->latitude && $currentUser->longitude) {
                    $query->selectRaw("*, 
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + 
                            sin(radians(?)) * sin(radians(latitude))
                        )) as distance,
                        CASE 
                            WHEN last_online_at > ? THEN 3
                            WHEN last_online_at > ? THEN 2
                            WHEN last_online_at > ? THEN 1
                            ELSE 0
                        END as activity_score", [
                            $currentUser->latitude,
                            $currentUser->longitude,
                            $currentUser->latitude,
                            now()->subHours(1), // Online in last hour
                            now()->subDays(1),  // Online in last day
                            now()->subDays(7)   // Online in last week
                        ])
                        ->orderByRaw('(activity_score * 10 + (100 - LEAST(distance, 100))) DESC');
                } else {
                    $query->orderBy('last_online_at', 'desc');
                }
                break;
        }
    }

    /**
     * Calculate compatibility score between two users
     */
    public function calculateCompatibilityScore(User $user1, User $user2)
    {
        $score = 0;
        $maxScore = 100;

        // Age compatibility (20 points)
        if ($user1->age_range_min && $user1->age_range_max && $user2->dob) {
            $user2Age = Carbon::parse($user2->dob)->age;
            if ($user2Age >= $user1->age_range_min && $user2Age <= $user1->age_range_max) {
                $score += 20;
            }
        }

        // Location proximity (25 points)
        if ($user1->latitude && $user1->longitude && $user2->latitude && $user2->longitude) {
            $distance = $user1->getDistanceFrom($user2);
            if ($distance <= 10) $score += 25;
            elseif ($distance <= 25) $score += 20;
            elseif ($distance <= 50) $score += 15;
            elseif ($distance <= 100) $score += 10;
            elseif ($distance <= 200) $score += 5;
        }

        // Shared interests (20 points)
        $user1Interests = is_string($user1->interests) 
            ? json_decode($user1->interests, true) 
            : ($user1->interests ?? []);
        $user2Interests = is_string($user2->interests) 
            ? json_decode($user2->interests, true) 
            : ($user2->interests ?? []);

        if (!empty($user1Interests) && !empty($user2Interests)) {
            $sharedInterests = array_intersect($user1Interests, $user2Interests);
            $interestScore = min(count($sharedInterests) * 4, 20);
            $score += $interestScore;
        }

        // Lifestyle compatibility (15 points)
        $lifestyleFactors = ['smoking_habit', 'drinking_habit', 'religion', 'education_level'];
        $compatibleFactors = 0;
        
        foreach ($lifestyleFactors as $factor) {
            if ($user1->$factor && $user2->$factor && $user1->$factor === $user2->$factor) {
                $compatibleFactors++;
            }
        }
        
        $score += ($compatibleFactors / count($lifestyleFactors)) * 15;

        // Profile completeness (10 points)
        $completeness1 = $user1->calculateProfileCompleteness();
        $completeness2 = $user2->calculateProfileCompleteness();
        $avgCompleteness = ($completeness1 + $completeness2) / 2;
        $score += ($avgCompleteness / 100) * 10;

        // Activity level (10 points)
        if ($user2->last_online_at) {
            $hoursOffline = Carbon::parse($user2->last_online_at)->diffInHours(now());
            if ($hoursOffline <= 1) $score += 10;
            elseif ($hoursOffline <= 24) $score += 7;
            elseif ($hoursOffline <= 168) $score += 4; // 1 week
            elseif ($hoursOffline <= 720) $score += 2; // 1 month
        }

        return round(($score / $maxScore) * 100);
    }

    /**
     * Get user discovery statistics
     */
    public function getDiscoveryStats(User $user)
    {
        return [
            'total_potential_matches' => $this->discoverUsers($user, new Request())->count(),
            'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())
                ->where('id', '!=', $user->id)
                ->where('account_status', 'Active')
                ->count(),
            'online_users_now' => User::where('last_online_at', '>=', now()->subMinutes(15))
                ->where('id', '!=', $user->id)
                ->where('account_status', 'Active')
                ->count(),
            'nearby_users' => $user->latitude && $user->longitude 
                ? User::whereRaw("
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitude))
                    )) <= ?
                ", [$user->latitude, $user->longitude, $user->latitude, 25])
                ->where('id', '!=', $user->id)
                ->where('account_status', 'Active')
                ->count()
                : 0
        ];
    }
}
