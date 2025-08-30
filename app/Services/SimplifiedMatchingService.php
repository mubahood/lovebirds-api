<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLike;
use App\Models\UserMatch;
use App\Models\UserBlock;
use App\Models\ChatHead;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimplifiedMatchingService
{
    /**
     * Simplified Compatibility Scoring Algorithm
     * Total: 100 points distributed across 5 key factors
     */
    public function calculateCompatibilityScore(User $user1, User $user2)
    {
        $score = 0;

        // 1. Location Proximity (30 points) - Most important for real connections
        $score += $this->calculateLocationScore($user1, $user2);

        // 2. Shared Interests (25 points) - Common activities and hobbies
        $score += $this->calculateInterestScore($user1, $user2);

        // 3. Age Compatibility (20 points) - Within preferred age ranges
        $score += $this->calculateAgeScore($user1, $user2);

        // 4. Activity Level (15 points) - Recent app usage indicates engagement
        $score += $this->calculateActivityScore($user2);

        // 5. Profile Quality (10 points) - Complete profiles get slight boost
        $score += $this->calculateProfileScore($user2);

        return min(100, round($score));
    }

    /**
     * Location Proximity Score (30 points max)
     * Closer distance = higher score
     */
    private function calculateLocationScore(User $user1, User $user2)
    {
        if (!$user1->latitude || !$user1->longitude || !$user2->latitude || !$user2->longitude) {
            return 15; // Higher base score for users without location - be more generous!
        }

        $distance = $this->calculateDistance(
            $user1->latitude, $user1->longitude,
            $user2->latitude, $user2->longitude
        );

        // More generous distance scoring: closer = better but still good scores for far users
        if ($distance <= 5) return 30;      // Very close
        if ($distance <= 15) return 25;     // Close
        if ($distance <= 30) return 20;     // Nearby  
        if ($distance <= 60) return 15;     // Moderate distance
        if ($distance <= 100) return 12;    // Far but still decent score
        if ($distance <= 200) return 8;     // Very far but acceptable
        return 5; // Even very far gets some score
    }

    /**
     * Shared Interests Score (25 points max)
     * More shared interests = higher score
     */
    private function calculateInterestScore(User $user1, User $user2)
    {
        $interests1 = $this->parseInterests($user1->interests);
        $interests2 = $this->parseInterests($user2->interests);

        if (empty($interests1) || empty($interests2)) {
            return 10; // Higher base score for incomplete interest data - be generous!
        }

        $sharedInterests = array_intersect($interests1, $interests2);
        $sharedCount = count($sharedInterests);

        // More generous interest scoring
        if ($sharedCount >= 5) return 25;      // Excellent match
        if ($sharedCount >= 3) return 20;      // Great match
        if ($sharedCount >= 2) return 15;      // Good match
        if ($sharedCount >= 1) return 12;      // Some compatibility - higher score
        return 8; // Even no shared interests gets decent score
    }

    /**
     * Age Compatibility Score (20 points max)
     * Within preferred age range = full score
     */
    private function calculateAgeScore(User $user1, User $user2)
    {
        if (!$user2->dob) {
            return 10; // Higher base score for users without age info - be more generous!
        }

        $user2Age = Carbon::parse($user2->dob)->age;

        // Check if user2's age falls within user1's preferences - be more lenient
        if ($user1->age_range_min && $user1->age_range_max) {
            if ($user2Age >= $user1->age_range_min && $user2Age <= $user1->age_range_max) {
                return 20; // Perfect age match
            } else {
                // Be more forgiving for age differences
                $deviation = min(
                    abs($user2Age - $user1->age_range_min),
                    abs($user2Age - $user1->age_range_max)
                );
                
                if ($deviation <= 3) return 18;    // Very close to range - higher score
                if ($deviation <= 7) return 15;    // Close to range - higher score  
                if ($deviation <= 10) return 12;   // Somewhat outside range - still good
                return 8;                           // Far from preferred range but still decent
            }
        }

        // If no age preferences set, be very generous
        $ageDiff = abs($user1->dob ? Carbon::parse($user1->dob)->age - $user2Age : 3);
        if ($ageDiff <= 5) return 20;      // Very compatible
        if ($ageDiff <= 10) return 17;     // Good compatibility
        if ($ageDiff <= 15) return 14;     // Decent compatibility
        return 10;                          // Still some compatibility
    }

    /**
     * Activity Level Score (15 points max)
     * More recent activity = higher score
     */
    private function calculateActivityScore(User $user)
    {
        if (!$user->last_online_at) {
            return 0; // No activity data
        }

        $hoursOffline = Carbon::parse($user->last_online_at)->diffInHours(now());

        if ($hoursOffline <= 1) return 15;      // Online recently
        if ($hoursOffline <= 6) return 12;      // Active today
        if ($hoursOffline <= 24) return 10;     // Active yesterday
        if ($hoursOffline <= 72) return 7;      // Active this week
        if ($hoursOffline <= 168) return 5;     // Active this month
        return 2; // Inactive user
    }

    /**
     * Profile Quality Score (10 points max)
     * Complete profiles get slight boost
     */
    private function calculateProfileScore(User $user)
    {
        $score = 0;

        // Has profile photo
        if ($user->avatar && !empty($user->avatar)) $score += 3;

        // Has bio
        if ($user->bio && strlen($user->bio) > 20) $score += 2;

        // Has basic info
        if ($user->occupation && $user->education_level) $score += 2;

        // Has lifestyle info
        if ($user->interests || $user->smoking_habit || $user->drinking_habit) $score += 2;

        // Verified user
        if ($user->email_verified === 'Yes' || $user->phone_verified === 'Yes') $score += 1;

        return $score;
    }

    /**
     * Get potential matches with simplified discovery - FIXED: Always return multiple users!
     */
    public function getDiscoveryUsers(User $currentUser, Request $request)
    {
        $limit = $request->get('limit', 6); // FIXED: Reduced default to 6 for cleaner orbital UI
        
        // FIXED: Super simple query - no complex filtering that might eliminate users
        $users = User::query()
            ->where('id', '!=', $currentUser->id)
            ->where('account_status', 'Active')
            ->inRandomOrder() // Randomize to get different users each time
            ->limit($limit * 2) // Get twice as many for variety
            ->get();

        $scoredUsers = [];
        foreach ($users as $user) {
            $score = $this->calculateCompatibilityScore($currentUser, $user);
            
            $scoredUsers[] = [
                'user' => $user,
                'compatibility_score' => max($score, 25), // Ensure minimum decent score
                'shared_interests' => $this->getSharedInterests($currentUser, $user),
                'distance' => $this->getDistanceKm($currentUser, $user),
            ];
        }

        // Sort by compatibility score but keep randomness
        usort($scoredUsers, function($a, $b) {
            // Add some randomness to avoid always showing same order
            if (abs($a['compatibility_score'] - $b['compatibility_score']) <= 10) {
                return rand(-1, 1); // Random order for similar scores
            }
            return $b['compatibility_score'] <=> $a['compatibility_score'];
        });

        return array_slice($scoredUsers, 0, $limit);
    }

    /**
     * Get enhanced match data for mobile app
     */
    public function getEnhancedMatches(User $currentUser, $filter = 'all', $limit = 20, $page = 1)
    {
        $offset = ($page - 1) * $limit;

        $query = UserMatch::where(function ($q) use ($currentUser) {
            $q->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })
        ->where('status', 'Active')
        ->with(['user', 'matchedUser']);

        // Apply filters
        switch ($filter) {
            case 'new':
                $query->where('created_at', '>=', now()->subHours(24))
                      ->whereNull('last_message_at');
                break;

            case 'recent':
                $query->where('created_at', '>=', now()->subDays(7));
                break;

            case 'unread':
                $query->whereHas('chatHead', function($q) use ($currentUser) {
                    $q->whereHas('messages', function($msgQ) use ($currentUser) {
                        $msgQ->where('sender_user_id', '!=', $currentUser->id)
                             ->whereNull('read_at');
                    });
                });
                break;
        }

        // Get total count for pagination
        $total = $query->count();

        // Get matches with enhanced data
        $matches = $query->orderBy('created_at', 'desc')
                        ->offset($offset)
                        ->limit($limit)
                        ->get();

        $enhancedMatches = $matches->map(function ($match) use ($currentUser) {
            $otherUser = $match->user_id === $currentUser->id 
                ? $match->matchedUser 
                : $match->user;

            // Get chat data
            $chatHead = $match->getChatHead();
            $lastMessage = $chatHead ? $chatHead->messages()->latest()->first() : null;
            $unreadCount = $chatHead ? $chatHead->messages()
                ->where('sender_user_id', '!=', $currentUser->id)
                ->whereNull('read_at')
                ->count() : 0;

            // Recalculate compatibility with current algorithm
            $compatibilityScore = $this->calculateCompatibilityScore($currentUser, $otherUser);

            return [
                'id' => $match->id,
                'user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'age' => $otherUser->dob ? Carbon::parse($otherUser->dob)->age : null,
                    'location' => $otherUser->city,
                    'bio' => $otherUser->bio ? substr($otherUser->bio, 0, 80) . '...' : null,
                    'last_online' => $otherUser->last_online_at,
                ],
                'match_data' => [
                    'matched_at' => $match->created_at->toISOString(),
                    'compatibility_score' => $compatibilityScore,
                    'shared_interests' => $this->getSharedInterests($currentUser, $otherUser),
                    'distance_km' => $this->getDistanceKm($currentUser, $otherUser),
                ],
                'conversation' => [
                    'has_messages' => $lastMessage !== null,
                    'last_message' => $lastMessage ? [
                        'text' => $lastMessage->body,
                        'sent_at' => $lastMessage->created_at->toISOString(),
                        'is_from_me' => $lastMessage->sender_user_id === $currentUser->id,
                    ] : null,
                    'unread_count' => $unreadCount,
                ],
                'conversation_starter' => $this->generateConversationStarter($currentUser, $otherUser),
            ];
        });

        // Calculate filter counts
        $filterCounts = $this->calculateFilterCounts($currentUser);

        return [
            'matches' => $enhancedMatches->toArray(),
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $limit,
                'has_more' => ($offset + $limit) < $total,
            ],
            'filter_counts' => $filterCounts,
        ];
    }

    /**
     * Generate conversation starter suggestions
     */
    private function generateConversationStarter(User $user1, User $user2)
    {
        $sharedInterests = $this->getSharedInterests($user1, $user2);
        
        if (!empty($sharedInterests)) {
            $interest = $sharedInterests[0];
            return "I see you're into {$interest} too! What got you started with that?";
        }

        if ($user2->bio && strlen($user2->bio) > 10) {
            return "Hey {$user2->name}! I'd love to know more about your story.";
        }

        return "Hey {$user2->name}! How's your day going?";
    }

    /**
     * Calculate filter counts for UI
     */
    private function calculateFilterCounts(User $currentUser)
    {
        $baseQuery = UserMatch::where(function ($q) use ($currentUser) {
            $q->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })->where('status', 'Active');

        return [
            'all' => $baseQuery->count(),
            'new' => (clone $baseQuery)->where('created_at', '>=', now()->subHours(24))
                                     ->whereNull('last_message_at')
                                     ->count(),
            'recent' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(),
            'unread' => (clone $baseQuery)->whereHas('chatHead', function($q) use ($currentUser) {
                $q->whereHas('messages', function($msgQ) use ($currentUser) {
                    $msgQ->where('sender_user_id', '!=', $currentUser->id)
                         ->whereNull('read_at');
                });
            })->count(),
        ];
    }

    // Helper methods
    private function applyBasicFilters($query, User $currentUser)
    {
        // Gender preference
        if ($currentUser->interested_in && $currentUser->interested_in !== 'both') {
            $query->where('sex', $currentUser->interested_in);
        }

        // Age range (if set)
        if ($currentUser->age_range_min && $currentUser->age_range_max) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?', 
                [$currentUser->age_range_min, $currentUser->age_range_max]);
        }

        // Distance filter (if location available)
        if ($currentUser->latitude && $currentUser->longitude && $currentUser->max_distance_km) {
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
                $currentUser->max_distance_km
            ]);
        }
    }

    /**
     * Apply more lenient filters for discovery - show more users!
     */
    private function applyLenientFilters($query, User $currentUser)
    {
        // Only apply gender preference if explicitly set (not 'both') - WITH PROPER MAPPING
        if ($currentUser->interested_in && $currentUser->interested_in !== 'both' && $currentUser->interested_in !== '') {
            // Map common gender terms to database values
            $genderMapping = [
                'Women' => 'Female',
                'Woman' => 'Female', 
                'women' => 'Female',
                'woman' => 'Female',
                'Men' => 'Male',
                'Man' => 'Male',
                'men' => 'Male', 
                'man' => 'Male',
                'Female' => 'Female',
                'Male' => 'Male',
                'Other' => 'Other'
            ];
            
            $mappedGender = $genderMapping[$currentUser->interested_in] ?? $currentUser->interested_in;
            $query->where('sex', $mappedGender);
        }

        // Much more lenient age range - expand by 5 years on each side
        if ($currentUser->age_range_min && $currentUser->age_range_max) {
            $expandedMin = max(18, $currentUser->age_range_min - 5); // Don't go below 18
            $expandedMax = min(80, $currentUser->age_range_max + 5); // Don't go above 80
            $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?', 
                [$expandedMin, $expandedMax]);
        } else {
            // If no age preference set, show adults only (18-65)
            $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 65');
        }

        // Much more lenient distance filter - double the range or use 100km default
        if ($currentUser->latitude && $currentUser->longitude && $currentUser->max_distance_km) {
            $expandedDistance = min(200, $currentUser->max_distance_km * 2); // Double distance but cap at 200km
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
                $expandedDistance
            ]);
        } elseif ($currentUser->latitude && $currentUser->longitude) {
            // If location available but no distance preference, use 100km default
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) <= 100
            ", [
                $currentUser->latitude, 
                $currentUser->longitude, 
                $currentUser->latitude
            ]);
        }
        // If no location data, show everyone - very lenient!
    }

    private function excludeBlockedAndInteractedUsers($query, User $currentUser)
    {
        // Exclude blocked users
        $blockedUserIds = UserBlock::where('blocker_id', $currentUser->id)
            ->pluck('blocked_user_id')
            ->merge(
                UserBlock::where('blocked_user_id', $currentUser->id)
                         ->pluck('blocker_id')
            )
            ->toArray();

        if (!empty($blockedUserIds)) {
            $query->whereNotIn('id', $blockedUserIds);
        }

        // Exclude already liked/passed users
        $interactedUserIds = UserLike::where('liker_id', $currentUser->id)
            ->pluck('liked_user_id')
            ->toArray();

        if (!empty($interactedUserIds)) {
            $query->whereNotIn('id', $interactedUserIds);
        }

        // Exclude already matched users
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

    private function parseInterests($interests)
    {
        if (!$interests) return [];
        
        if (is_string($interests)) {
            $decoded = json_decode($interests, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($interests) ? $interests : [];
    }

    private function getSharedInterests(User $user1, User $user2)
    {
        $interests1 = $this->parseInterests($user1->interests);
        $interests2 = $this->parseInterests($user2->interests);
        
        return array_values(array_intersect($interests1, $interests2));
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    private function getDistanceKm(User $user1, User $user2)
    {
        if (!$user1->latitude || !$user1->longitude || !$user2->latitude || !$user2->longitude) {
            return null;
        }

        return round($this->calculateDistance(
            $user1->latitude, $user1->longitude,
            $user2->latitude, $user2->longitude
        ));
    }
}
