<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎯 LOVEBIRDS DATING APP - TEST DATA SUMMARY\n";
echo "===========================================\n\n";

// Count data
$totalUsers = DB::table('users')->count();
$top100Users = DB::table('users')->whereBetween('id', [1, 100])->count();
$totalLikes = DB::table('user_likes')->count();
$totalMatches = DB::table('user_matches')->count();

echo "📊 DATA OVERVIEW:\n";
echo "   👥 Total users in system: $totalUsers\n";
echo "   🎭 Users in range 1-100: $top100Users\n";
echo "   💕 User likes/swipes: $totalLikes\n";
echo "   💖 Mutual matches: $totalMatches\n\n";

// Show some sample users
echo "👤 SAMPLE USERS:\n";
$sampleUsers = DB::table('users')
    ->whereBetween('id', [1, 10])
    ->get(['id', 'name', 'email']);

foreach ($sampleUsers as $user) {
    echo "   ID {$user->id}: {$user->name} ({$user->email})\n";
}

echo "\n💕 SAMPLE INTERACTIONS:\n";
$interactions = DB::table('user_likes')
    ->join('users as liker', 'user_likes.liker_id', '=', 'liker.id')
    ->join('users as liked', 'user_likes.liked_user_id', '=', 'liked.id')
    ->select('liker.name as liker_name', 'liked.name as liked_name', 'user_likes.message')
    ->limit(5)
    ->get();

foreach ($interactions as $like) {
    echo "   👤 {$like->liker_name} liked {$like->liked_name}";
    if ($like->message) {
        echo " - \"{$like->message}\"";
    }
    echo "\n";
}

echo "\n💑 SAMPLE MATCHES:\n";
$matches = DB::table('user_matches')
    ->join('users as u1', 'user_matches.user_id', '=', 'u1.id')
    ->join('users as u2', 'user_matches.matched_user_id', '=', 'u2.id')
    ->select('u1.name as user1', 'u2.name as user2', 'user_matches.compatibility_score')
    ->limit(5)
    ->get();

foreach ($matches as $match) {
    $score = round($match->compatibility_score * 100);
    echo "   💕 {$match->user1} ↔️ {$match->user2} ({$score}% compatibility)\n";
}

echo "\n✨ SUCCESS! Your Lovebirds dating app is ready for comprehensive testing!\n";
echo "\n🚀 NEXT STEPS:\n";
echo "   1. Open your Flutter app\n";
echo "   2. Test user discovery/swiping features\n";
echo "   3. Verify match functionality\n";
echo "   4. Test profile viewing and interactions\n";
echo "\n💝 Happy testing!\n";

?>
