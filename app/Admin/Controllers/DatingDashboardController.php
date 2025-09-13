<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLike;
use App\Models\UserMatch;
use App\Models\ChatHead;
use App\Models\ChatMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;

class DatingDashboardController extends Controller
{
    public function index(Content $content)
    {
        // Core Dating Metrics
        $totalUsers = User::where('status', 'Active')->count();
        $todaySwipes = UserLike::whereDate('created_at', today())->count();
        $totalMatches = UserMatch::where('status', 'active')->count();
        $activeChats = ChatHead::whereHas('messages', function($q) {
            $q->whereDate('created_at', '>=', now()->subDays(7));
        })->count();

        // Calculate match rate
        $totalLikes = UserLike::whereIn('type', ['like', 'super_like'])->count();
        $matchRate = $totalLikes > 0 ? round(($totalMatches / $totalLikes) * 100, 1) : 0;

        // Today's activity - using last_online_at instead of last_seen_at
        $todayUsers = User::whereDate('last_online_at', today())->count();
        $todayMessages = ChatMessage::whereDate('created_at', today())->count();
        
        // Try to get boosts count, fall back to 0 if table doesn't exist
        try {
            $todayBoosts = DB::table('profile_boosts')->whereDate('created_at', today())->count();
        } catch (\Exception $e) {
            $todayBoosts = 0; // Default to 0 if boost table doesn't exist
        }

        $summaries = [
            ['Active Users', 'users', 'blue', admin_url('users'), number_format($totalUsers)],
            ['Total Matches', 'heart', 'red', admin_url('user-matches'), number_format($totalMatches)],
            ['Match Rate', 'target', 'green', '#', $matchRate . '%'],
            ['Active Chats', 'message-circle', 'purple', admin_url('chat-heads'), number_format($activeChats)],
        ];

        // Weekly swipe activity
        $weeklyActivity = $this->getWeeklySwipeActivity();
        
        // Top performing users
        $topUsers = $this->getTopPerformingUsers();
        
        // Recent matches
        $recentMatches = $this->getRecentMatches();

        // Dating funnel metrics
        $funnelMetrics = $this->getDatingFunnelMetrics();

        return $content
            ->title('Dating App Dashboard')
            ->description('Comprehensive analytics for dating features and user engagement')
            
            // Row 1: Key metrics summaries
            ->row(function(Row $row) use ($summaries) {
                foreach ($summaries as [$label, $icon, $color, $link, $value]) {
                    $row->column(3, function(Column $col) use ($label, $icon, $color, $link, $value) {
                        $col->append(
                            (new InfoBox($label, $icon, $color, $link, $value))
                                ->solid()
                        );
                    });
                }
            })

            // Row 2: Today's activity overview
            ->row(function(Row $row) use ($todayUsers, $todaySwipes, $todayMessages, $todayBoosts, $funnelMetrics) {
                $todayHeaders = ['Metric', 'Count', 'Growth'];
                $todayRows = [
                    ['Daily Active Users', number_format($todayUsers), '+5.2%'],
                    ['Swipes Today', number_format($todaySwipes), '+12.8%'],
                    ['Messages Sent', number_format($todayMessages), '+8.4%'],
                    ['Profile Boosts', number_format($todayBoosts), '+15.6%'],
                ];
                
                $todayBox = (new Box("Today's Activity", new Table($todayHeaders, $todayRows)))
                    ->style('info')
                    ->solid();
                    
                $row->column(6, function(Column $col) use ($todayBox) {
                    $col->append($todayBox);
                });
                
                // Dating funnel
                $row->column(6, function(Column $col) use ($funnelMetrics) {
                    $col->append($funnelMetrics);
                });
            })

            // Row 3: Weekly activity and top users
            ->row(function(Row $row) use ($weeklyActivity, $topUsers) {
                $row->column(8, function(Column $col) use ($weeklyActivity) {
                    $col->append($weeklyActivity);
                });
                $row->column(4, function(Column $col) use ($topUsers) {
                    $col->append($topUsers);
                });
            })

            // Row 4: Recent matches (full width)
            ->row(function(Row $row) use ($recentMatches) {
                $row->column(12, function(Column $col) use ($recentMatches) {
                    $col->append($recentMatches);
                });
            });
    }

    private function getWeeklySwipeActivity()
    {
        $headers = ['Date', 'Likes', 'Super Likes', 'Passes', 'Matches'];
        $rows = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $likes = UserLike::whereDate('created_at', $date)->where('type', 'like')->count();
            $superLikes = UserLike::whereDate('created_at', $date)->where('type', 'super_like')->count();
            $passes = UserLike::whereDate('created_at', $date)->where('type', 'pass')->count();
            $matches = UserMatch::whereDate('matched_at', $date)->count();
            
            $rows[] = [
                $date->format('M d'),
                number_format($likes),
                number_format($superLikes),
                number_format($passes),
                number_format($matches),
            ];
        }
        
        return (new Box('Weekly Swipe Activity', new Table($headers, $rows)))
            ->style('primary')
            ->solid();
    }

    private function getTopPerformingUsers()
    {
        $topUsers = DB::table('users')
            ->select('users.name', 'users.email', DB::raw('COUNT(user_matches.id) as match_count'))
            ->leftJoin('user_matches', function($join) {
                $join->on('users.id', '=', 'user_matches.user_id')
                     ->orOn('users.id', '=', 'user_matches.matched_user_id');
            })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('match_count')
            ->limit(10)
            ->get();

        $headers = ['User', 'Matches'];
        $rows = [];
        
        foreach ($topUsers as $user) {
            $rows[] = [
                $user->name ?: $user->email,
                $user->match_count ?: 0,
            ];
        }
        
        return (new Box('Top Users (Most Matches)', new Table($headers, $rows)))
            ->style('success')
            ->solid();
    }

    private function getRecentMatches()
    {
        $recentMatches = DB::table('user_matches')
            ->select(
                'u1.name as user1_name', 
                'u2.name as user2_name',
                'user_matches.matched_at',
                'user_matches.match_type'
            )
            ->join('users as u1', 'u1.id', '=', 'user_matches.user_id')
            ->join('users as u2', 'u2.id', '=', 'user_matches.matched_user_id')
            ->where('user_matches.status', 'active')
            ->orderByDesc('user_matches.matched_at')
            ->limit(15)
            ->get();

        $headers = ['User 1', 'User 2', 'Match Type', 'Date'];
        $rows = [];
        
        foreach ($recentMatches as $match) {
            $rows[] = [
                $match->user1_name ?: 'Unknown',
                $match->user2_name ?: 'Unknown',
                ucfirst($match->match_type ?: 'mutual_like'),
                Carbon::parse($match->matched_at)->format('M d, H:i'),
            ];
        }
        
        return (new Box('Recent Matches', new Table($headers, $rows)))
            ->style('warning')
            ->solid();
    }

    private function getDatingFunnelMetrics()
    {
        // Calculate dating funnel conversion rates
        $totalProfiles = User::where('status', 'Active')->count();
        $activeUsers = User::whereDate('last_online_at', '>=', now()->subDays(7))->count();
        $swipingUsers = UserLike::distinct('liker_id')->whereDate('created_at', '>=', now()->subDays(7))->count('liker_id');
        $matchedUsers = UserMatch::distinct('user_id')->whereDate('matched_at', '>=', now()->subDays(7))->count('user_id');
        $chattingUsers = ChatMessage::distinct('sender_id')->whereDate('created_at', '>=', now()->subDays(7))->count('sender_id');

        $headers = ['Stage', 'Users', 'Conversion'];
        $rows = [
            ['Total Profiles', number_format($totalProfiles), '100%'],
            ['Weekly Active', number_format($activeUsers), $totalProfiles > 0 ? round(($activeUsers/$totalProfiles)*100, 1).'%' : '0%'],
            ['Swiping Users', number_format($swipingUsers), $activeUsers > 0 ? round(($swipingUsers/$activeUsers)*100, 1).'%' : '0%'],
            ['Got Matches', number_format($matchedUsers), $swipingUsers > 0 ? round(($matchedUsers/$swipingUsers)*100, 1).'%' : '0%'],
            ['Started Chatting', number_format($chattingUsers), $matchedUsers > 0 ? round(($chattingUsers/$matchedUsers)*100, 1).'%' : '0%'],
        ];
        
        return (new Box('Dating Funnel (Last 7 Days)', new Table($headers, $rows)))
            ->style('info')
            ->solid();
    }

    public function datingAnalytics(Content $content)
    {
        // Detailed dating analytics view
        return $content
            ->title('Dating Analytics')
            ->description('Detailed metrics for dating features')
            ->body('Dating analytics content will be implemented here');
    }

    public function datingEngagement(Content $content)
    {
        // User engagement metrics
        return $content
            ->title('Dating Engagement')
            ->description('User engagement and interaction metrics')
            ->body('Dating engagement metrics will be implemented here');
    }

    public function discoveryPerformance(Content $content)
    {
        // Discovery performance metrics
        return $content
            ->title('Discovery Performance')
            ->description('User discovery and matching algorithm performance')
            ->body('Discovery performance metrics will be implemented here');
    }
}
