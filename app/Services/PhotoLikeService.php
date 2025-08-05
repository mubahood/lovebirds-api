<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLike;
use App\Models\UserMatch;
use App\Models\ChatHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PhotoLikeService
{
    /**
     * Process a user's swipe action (like, super like, or pass)
     */
    public function processSwipeAction(User $currentUser, $targetUserId, $action, $message = null)
    {
        // Validate target user
        $targetUser = User::find($targetUserId);
        if (!$targetUser || $targetUser->id === $currentUser->id) {
            throw new \Exception('Invalid target user.');
        }

        // Check if user has already acted on this profile
        $existingAction = UserLike::where('liker_id', $currentUser->id)
            ->where('liked_user_id', $targetUserId)
            ->first();

        if ($existingAction) {
            throw new \Exception('You have already acted on this profile.');
        }

        // Check daily limits for free users
        if (!$currentUser->hasActiveSubscription() && $action !== 'pass') {
            $this->checkDailyLimits($currentUser, $action);
        }

        // Create the like/pass record
        $userLike = $this->createSwipeRecord($currentUser, $targetUser, $action, $message);

        // Check for mutual like (match)
        $isMatch = false;
        if ($action === 'like' || $action === 'super_like') {
            $isMatch = $this->checkForMatch($currentUser, $targetUser, $userLike);
        }

        return [
            'success' => true,
            'action' => $action,
            'is_match' => $isMatch,
            'like_id' => $userLike->id,
            'target_user' => $this->formatUserForResponse($targetUser),
            'daily_likes_remaining' => $this->getRemainingDailyLikes($currentUser)
        ];
    }

    /**
     * Get users who liked the current user
     */
    public function getWhoLikedMe(User $currentUser, $limit = 20)
    {
        $likes = UserLike::where('liked_user_id', $currentUser->id)
            ->where('status', 'Active')
            ->whereIn('type', ['like', 'super_like'])
            ->with('liker')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $likes->map(function ($like) {
            $user = $like->liker;

            return $this->formatUserForResponse($user, [
                'like_type' => $like->type,
                'like_message' => $like->message,
                'liked_at' => $like->liked_at,
                'is_mutual' => $like->is_mutual === 'Yes'
            ]);
        });
    }

    /**
     * Get mutual likes (matches)
     */
    public function getMutualLikes(User $currentUser, $limit = 50)
    {
        $matches = UserMatch::where(function ($query) use ($currentUser) {
            $query->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })
            ->where('status', 'Active')
            ->with(['user', 'matchedUser'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $matches->map(function ($match) use ($currentUser) {
            $otherUser = $match->user_id === $currentUser->id
                ? $match->matchedUser
                : $match->user;

            return $this->formatUserForResponse($otherUser, [
                'match_id' => $match->id,
                'matched_at' => $match->created_at,
                'compatibility_score' => $match->compatibility_score
            ]);
        });
    }

    /**
     * Get filtered matches with detailed information
     */
    public function getFilteredMatches(User $currentUser, $filter = 'all', $limit = 50, $page = 1)
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
                // Matches from last 24 hours with no messages
                $query->where('created_at', '>=', now()->subDay())
                    ->whereNotExists(function ($q) use ($currentUser) {
                        $q->select(DB::raw(1))
                            ->from('chat_heads')
                            ->join('chat_messages', 'chat_heads.id', '=', 'chat_messages.chat_head_id')
                            ->where('chat_heads.type', 'dating')
                            ->where(function ($subQ) {
                                $subQ->whereRaw('(chat_heads.customer_id = user_matches.user_id AND chat_heads.product_owner_id = user_matches.matched_user_id)')
                                    ->orWhereRaw('(chat_heads.customer_id = user_matches.matched_user_id AND chat_heads.product_owner_id = user_matches.user_id)');
                            });
                    });
                break;

            case 'messaged':
                // Matches with existing conversation
                $query->whereExists(function ($q) use ($currentUser) {
                    $q->select(DB::raw(1))
                        ->from('chat_heads')
                        ->join('chat_messages', 'chat_heads.id', '=', 'chat_messages.chat_head_id')
                        ->where('chat_heads.type', 'dating')
                        ->where(function ($subQ) {
                            $subQ->whereRaw('(chat_heads.customer_id = user_matches.user_id AND chat_heads.product_owner_id = user_matches.matched_user_id)')
                                ->orWhereRaw('(chat_heads.customer_id = user_matches.matched_user_id AND chat_heads.product_owner_id = user_matches.user_id)');
                        });
                });
                break;

            case 'recent':
                // Matches from last 7 days
                $query->where('created_at', '>=', now()->subDays(7));
                break;

            case 'super_likes':
                // Matches that came from super likes
                $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('user_likes')
                        ->where('user_likes.type', 'super_like')
                        ->where(function ($subQ) {
                            $subQ->whereRaw('(user_likes.liker_id = user_matches.user_id AND user_likes.liked_user_id = user_matches.matched_user_id)')
                                ->orWhereRaw('(user_likes.liker_id = user_matches.matched_user_id AND user_likes.liked_user_id = user_matches.user_id)');
                        });
                });
                break;

            default: // 'all'
                // No additional filtering
                break;
        }

        $totalQuery = clone $query;
        $total = $totalQuery->count();

        $matches = $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $formattedMatches = $matches->map(function ($match) use ($currentUser) {
            $otherUser = $match->user_id === $currentUser->id
                ? $match->matchedUser
                : $match->user;

            // Get chat head for this match
            $chatHead = ChatHead::where('match_id', $match->id)->first();
            $hasMessage = $chatHead && $chatHead->messages()->exists();
            $lastMessage = $hasMessage ? $chatHead->messages()->latest()->first() : null;

            // Determine match type based on the like that created the match
            $userLike = UserLike::where(function ($q) use ($currentUser, $otherUser) {
                $q->where('liker_id', $currentUser->id)->where('liked_user_id', $otherUser->id);
            })->orWhere(function ($q) use ($currentUser, $otherUser) {
                $q->where('liker_id', $otherUser->id)->where('liked_user_id', $currentUser->id);
            })->whereIn('type', ['like', 'super_like'])->first();

            return [
                'match_id' => $match->id,
                'user' => $this->formatUserForResponse($otherUser),
                'matched_at' => $match->created_at->toISOString(),
                'has_message' => $hasMessage,
                'last_message' => $lastMessage ? $lastMessage->message : null,
                'last_message_at' => $lastMessage ? $lastMessage->created_at->toISOString() : null,
                'match_type' => $userLike ? $userLike->type : 'like',
                'compatibility_score' => $match->compatibility_score
            ];
        });

        return [
            'matches' => $formattedMatches,
            'has_more' => ($offset + $limit) < $total
        ];
    }

    /**
     * Get filter counts for match categories
     */
    public function getMatchFilterCounts(User $currentUser)
    {
        $baseQuery = UserMatch::where(function ($q) use ($currentUser) {
            $q->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })->where('status', 'Active');

        return [
            'all' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->where('created_at', '>=', now()->subDay())
                ->whereNotExists(function ($q) use ($currentUser) {
                    $q->select(DB::raw(1))
                        ->from('chat_heads')
                        ->join('chat_messages', 'chat_heads.id', '=', 'chat_messages.chat_head_id')
                        ->where('chat_heads.type', 'dating')
                        ->where(function ($subQ) {
                            $subQ->whereRaw('(chat_heads.customer_id = user_matches.user_id AND chat_heads.product_owner_id = user_matches.matched_user_id)')
                                ->orWhereRaw('(chat_heads.customer_id = user_matches.matched_user_id AND chat_heads.product_owner_id = user_matches.user_id)');
                        });
                })->count(),
            'messaged' => (clone $baseQuery)->whereExists(function ($q) use ($currentUser) {
                $q->select(DB::raw(1))
                    ->from('chat_heads')
                    ->join('chat_messages', 'chat_heads.id', '=', 'chat_messages.chat_head_id')
                    ->where('chat_heads.type', 'dating')
                    ->where(function ($subQ) {
                        $subQ->whereRaw('(chat_heads.customer_id = user_matches.user_id AND chat_heads.product_owner_id = user_matches.matched_user_id)')
                            ->orWhereRaw('(chat_heads.customer_id = user_matches.matched_user_id AND chat_heads.product_owner_id = user_matches.user_id)');
                    });
            })->count(),
            'recent' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(),
            'super_likes' => (clone $baseQuery)->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('user_likes')
                    ->where('user_likes.type', 'super_like')
                    ->where(function ($subQ) {
                        $subQ->whereRaw('(user_likes.liker_id = user_matches.user_id AND user_likes.liked_user_id = user_matches.matched_user_id)')
                            ->orWhereRaw('(user_likes.liker_id = user_matches.matched_user_id AND user_likes.liked_user_id = user_matches.user_id)');
                    });
            })->count(),
        ];
    }

    /**
     * Undo last swipe action (premium feature)
     */
    public function undoLastSwipe(User $currentUser)
    {
        if (!$currentUser->hasActiveSubscription()) {
            throw new \Exception('Undo feature requires an active subscription!');
        }

        $lastSwipe = UserLike::where('liker_id', $currentUser->id)
            ->where('status', 'Active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastSwipe) {
            throw new \Exception('No recent swipe to undo.');
        }

        // Check if it was within the last 5 minutes
        if ($lastSwipe->created_at < now()->subMinutes(5)) {
            throw new \Exception('Can only undo swipes within 5 minutes.');
        }

        // If it was a match, remove the match
        if ($lastSwipe->is_mutual === 'Yes') {
            UserMatch::where(function ($query) use ($lastSwipe) {
                $query->where('user_id', $lastSwipe->liker_id)
                    ->where('matched_user_id', $lastSwipe->liked_user_id);
            })->orWhere(function ($query) use ($lastSwipe) {
                $query->where('user_id', $lastSwipe->liked_user_id)
                    ->where('matched_user_id', $lastSwipe->liker_id);
            })->delete();

            // Update mutual like flag on both sides
            UserLike::where('liker_id', $lastSwipe->liked_user_id)
                ->where('liked_user_id', $lastSwipe->liker_id)
                ->update(['is_mutual' => 'No']);
        }

        // Delete the swipe record
        $undoneUser = User::find($lastSwipe->liked_user_id);
        $lastSwipe->delete();

        return [
            'success' => true,
            'undone_user' => $this->formatUserForResponse($undoneUser),
            'was_match' => $lastSwipe->is_mutual === 'Yes'
        ];
    }

    /**
     * Get user's swipe statistics
     */
    public function getSwipeStats(User $currentUser)
    {
        $stats = [
            'total_likes_sent' => UserLike::where('liker_id', $currentUser->id)
                ->whereIn('type', ['like', 'super_like'])
                ->where('status', 'Active')
                ->count(),

            'total_likes_received' => UserLike::where('liked_user_id', $currentUser->id)
                ->whereIn('type', ['like', 'super_like'])
                ->where('status', 'Active')
                ->count(),

            'total_passes' => UserLike::where('liker_id', $currentUser->id)
                ->where('type', 'pass')
                ->where('status', 'Active')
                ->count(),

            'total_matches' => UserMatch::where(function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id)
                    ->orWhere('matched_user_id', $currentUser->id);
            })
                ->where('status', 'Active')
                ->count(),

            'super_likes_sent' => UserLike::where('liker_id', $currentUser->id)
                ->where('type', 'super_like')
                ->where('status', 'Active')
                ->count(),

            'daily_likes_used' => $this->getDailyLikesUsed($currentUser),
            'daily_likes_remaining' => $this->getRemainingDailyLikes($currentUser),

            'match_rate' => $this->calculateMatchRate($currentUser)
        ];

        return $stats;
    }

    /**
     * Get detailed profile statistics
     */
    public function getProfileStats(User $currentUser)
    {
        // Get profile views (simulated based on likes received - you'd want real view tracking)
        $profileViews = UserLike::where('liked_user_id', $currentUser->id)
            ->where('status', 'Active')
            ->count() * 3; // Estimated 3:1 view to like ratio

        // Get weekly stats
        $weekStart = now()->startOfWeek();
        $weeklyLikesReceived = UserLike::where('liked_user_id', $currentUser->id)
            ->whereIn('type', ['like', 'super_like'])
            ->where('status', 'Active')
            ->where('created_at', '>=', $weekStart)
            ->count();

        $weeklyMatches = UserMatch::where(function ($query) use ($currentUser) {
            $query->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })
            ->where('status', 'Active')
            ->where('created_at', '>=', $weekStart)
            ->count();

        // Get monthly stats
        $monthStart = now()->startOfMonth();
        $monthlyLikesReceived = UserLike::where('liked_user_id', $currentUser->id)
            ->whereIn('type', ['like', 'super_like'])
            ->where('status', 'Active')
            ->where('created_at', '>=', $monthStart)
            ->count();

        $monthlyMatches = UserMatch::where(function ($query) use ($currentUser) {
            $query->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })
            ->where('status', 'Active')
            ->where('created_at', '>=', $monthStart)
            ->count();

        // Calculate profile completion
        $profileCompletion = $this->calculateProfileCompletion($currentUser);

        // Get like distribution by hour (for optimal posting times)
        $likesByHour = UserLike::where('liked_user_id', $currentUser->id)
            ->whereIn('type', ['like', 'super_like'])
            ->where('status', 'Active')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        return [
            'profile_views' => $profileViews,
            'weekly_likes_received' => $weeklyLikesReceived,
            'weekly_matches' => $weeklyMatches,
            'monthly_likes_received' => $monthlyLikesReceived,
            'monthly_matches' => $monthlyMatches,
            'profile_completion' => $profileCompletion,
            'optimal_hours' => $this->getOptimalHours($likesByHour),
            'likes_by_hour' => $likesByHour,
            'popularity_trend' => $this->getPopularityTrend($currentUser),
            'upgrade_recommendations' => $this->getUpgradeRecommendations($currentUser),
        ];
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(User $currentUser)
    {
        $fields = [
            'name' => !empty($currentUser->name),
            'bio' => !empty($currentUser->bio),
            'date_of_birth' => !empty($currentUser->date_of_birth),
            'primary_photo' => !empty($currentUser->primary_photo),
            'job_title' => !empty($currentUser->job_title),
            'education' => !empty($currentUser->education),
            'location' => !empty($currentUser->location_lat) && !empty($currentUser->location_lng),
            'interests' => !empty($currentUser->interests),
        ];

        $completed = array_sum($fields);
        $total = count($fields);

        return round(($completed / $total) * 100);
    }

    /**
     * Get optimal hours for profile activity
     */
    private function getOptimalHours($likesByHour)
    {
        if (empty($likesByHour)) {
            return [19, 20, 21]; // Default evening hours
        }

        arsort($likesByHour);
        return array_keys(array_slice($likesByHour, 0, 3, true));
    }

    /**
     * Get popularity trend over time
     */
    private function getPopularityTrend(User $currentUser)
    {
        $dailyLikes = UserLike::where('liked_user_id', $currentUser->id)
            ->whereIn('type', ['like', 'super_like'])
            ->where('status', 'Active')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $trend = 'stable';
        $values = array_values($dailyLikes);

        if (count($values) >= 3) {
            $recent = array_sum(array_slice($values, -3)) / 3;
            $older = array_sum(array_slice($values, 0, 3)) / 3;

            if ($recent > $older * 1.2) {
                $trend = 'increasing';
            } elseif ($recent < $older * 0.8) {
                $trend = 'decreasing';
            }
        }

        return [
            'direction' => $trend,
            'daily_data' => $dailyLikes
        ];
    }

    /**
     * Get personalized upgrade recommendations
     */
    private function getUpgradeRecommendations(User $currentUser)
    {
        $recommendations = [];

        // Check if user is hitting daily limits
        $dailyLikes = $this->getDailyLikesUsed($currentUser);
        if ($dailyLikes >= 40) { // Close to 50 limit
            $recommendations[] = [
                'title' => 'Unlimited Likes',
                'description' => 'You\'re almost at your daily limit! Upgrade for unlimited likes.',
                'priority' => 'high'
            ];
        }

        // Check profile completion
        $completion = $this->calculateProfileCompletion($currentUser);
        if ($completion < 80) {
            $recommendations[] = [
                'title' => 'Complete Your Profile',
                'description' => 'Complete your profile to get 3x more matches!',
                'priority' => 'medium'
            ];
        }

        // Check if user has few matches
        $totalMatches = UserMatch::where(function ($query) use ($currentUser) {
            $query->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })
            ->where('status', 'Active')
            ->count();

        if ($totalMatches < 5) {
            $recommendations[] = [
                'title' => 'Boost Your Profile',
                'description' => 'Get 10x more visibility with our boost feature!',
                'priority' => 'high'
            ];
        }

        return $recommendations;
    }

    /**
     * Block user and remove any existing likes/matches
     */
    public function blockAndCleanup(User $currentUser, $targetUserId)
    {
        // Remove all likes between users
        UserLike::where(function ($query) use ($currentUser, $targetUserId) {
            $query->where('liker_id', $currentUser->id)
                ->where('liked_user_id', $targetUserId);
        })->orWhere(function ($query) use ($currentUser, $targetUserId) {
            $query->where('liker_id', $targetUserId)
                ->where('liked_user_id', $currentUser->id);
        })->delete();

        // Remove any matches
        UserMatch::where(function ($query) use ($currentUser, $targetUserId) {
            $query->where('user_id', $currentUser->id)
                ->where('matched_user_id', $targetUserId);
        })->orWhere(function ($query) use ($currentUser, $targetUserId) {
            $query->where('user_id', $targetUserId)
                ->where('matched_user_id', $currentUser->id);
        })->delete();

        return true;
    }

    // =================== PRIVATE HELPER METHODS ===================

    private function checkDailyLimits(User $currentUser, $action)
    {
        $dailyLimit = $action === 'super_like' ? 1 : 50; // Free users: 50 likes, 1 super like per day
        $usedToday = $this->getDailyLikesUsed($currentUser, $action);

        if ($usedToday >= $dailyLimit) {
            $actionName = $action === 'super_like' ? 'super likes' : 'likes';
            throw new \Exception("Daily {$actionName} limit reached. Upgrade to premium for unlimited {$actionName}.");
        }
    }

    private function getDailyLikesUsed(User $currentUser, $specificType = null)
    {
        $query = UserLike::where('liker_id', $currentUser->id)
            ->where('status', 'Active')
            ->where('created_at', '>=', now()->startOfDay());

        if ($specificType) {
            $query->where('type', $specificType);
        } else {
            $query->whereIn('type', ['like', 'super_like']);
        }

        return $query->count();
    }

    private function getRemainingDailyLikes(User $currentUser)
    {
        if ($currentUser->hasActiveSubscription()) {
            return 'unlimited';
        }

        $dailyLimit = 50;
        $used = $this->getDailyLikesUsed($currentUser, 'like');
        return max(0, $dailyLimit - $used);
    }

    private function createSwipeRecord(User $currentUser, User $targetUser, $action, $message = null)
    {
        return UserLike::create([
            'liker_id' => $currentUser->id,
            'liked_user_id' => $targetUser->id,
            'type' => $action,
            'status' => 'Active',
            'message' => $message,
            'liked_at' => now(),
            'metadata' => json_encode([
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'platform' => request()->header('Platform', 'unknown')
            ])
        ]);
    }

    private function checkForMatch(User $currentUser, User $targetUser, UserLike $userLike)
    {
        $mutualLike = UserLike::where('liker_id', $targetUser->id)
            ->where('liked_user_id', $currentUser->id)
            ->whereIn('type', ['like', 'super_like'])
            ->where('status', 'Active')
            ->first();

        if ($mutualLike) {
            // Mark both likes as mutual
            $userLike->is_mutual = 'Yes';
            $userLike->save();

            $mutualLike->is_mutual = 'Yes';
            $mutualLike->save();

            // Create match record
            $match = UserMatch::createMatch($currentUser->id, $targetUser->id);

            // Create chat head for the match
            ChatHead::createDatingChat($currentUser->id, $targetUser->id, $match->id);

            // Send notifications to both users
            try {
                $this->sendMatchNotifications($currentUser, $targetUser);
            } catch (\Exception $e) {
                // Log error but don't fail the match creation
                Log::error('Failed to send match notifications: ' . $e->getMessage());
            }

            return true;
        }

        return false;
    }

    private function sendMatchNotifications(User $user1, User $user2)
    {
        // Send notification to user1
        $user1->sendNotification([
            'title' => 'New Match! 💕',
            'body' => "You and {$user2->name} liked each other!",
            'type' => 'match',
            'data' => [
                'user_id' => $user2->id,
                'user_name' => $user2->name,
                'user_photo' => $user2->primary_photo
            ]
        ]);

        // Send notification to user2
        $user2->sendNotification([
            'title' => 'New Match! 💕',
            'body' => "You and {$user1->name} liked each other!",
            'type' => 'match',
            'data' => [
                'user_id' => $user1->id,
                'user_name' => $user1->name,
                'user_photo' => $user1->primary_photo
            ]
        ]);
    }

    private function calculateMatchRate(User $currentUser)
    {
        $totalLikes = UserLike::where('liker_id', $currentUser->id)
            ->whereIn('type', ['like', 'super_like'])
            ->where('status', 'Active')
            ->count();

        if ($totalLikes === 0) {
            return 0;
        }

        $totalMatches = UserMatch::where(function ($query) use ($currentUser) {
            $query->where('user_id', $currentUser->id)
                ->orWhere('matched_user_id', $currentUser->id);
        })
            ->where('status', 'Active')
            ->count();

        return round(($totalMatches / $totalLikes) * 100, 1);
    }

    private function formatUserForResponse(User $user, $extraData = [])
    {
        // Convert to array to avoid model modification
        $userData = $user->toArray();

        // Remove sensitive information
        unset(
            $userData['email'],
            $userData['phone_number'],
            $userData['password'],
            $userData['verification_code'],
            $userData['remember_token']
        );

        // Add computed attributes
        $userData['age'] = $user->age;
        $userData['primary_photo'] = $user->primary_photo;
        $userData['profile_completion'] = $user->profile_completion;
        $userData['is_online'] = $user->online_status === 'Online';

        // Merge any extra data
        return array_merge($userData, $extraData);
    }
}
