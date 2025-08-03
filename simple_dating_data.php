<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🔄 Creating simple dating test data...\n";

// Clear existing data
DB::table('chat_messages')->truncate();
DB::table('chat_heads')->truncate();
DB::table('user_matches')->truncate();
DB::table('user_likes')->truncate();

// Get users 1-100
$users = User::whereBetween('id', [1, 100])->get();
echo "📊 Found " . $users->count() . " users to work with\n";

// Create user likes (simple approach)
$userLikes = [];
foreach ($users as $user) {
    // Each user likes 5-15 random other users
    $likeCount = rand(5, 15);
    $otherUsers = $users->where('id', '!=', $user->id)->shuffle()->take($likeCount);
    
    foreach ($otherUsers as $likedUser) {
        $userLikes[] = [
            'liker_id' => $user->id,
            'liked_user_id' => $likedUser->id,
            'type' => 'standard',
            'status' => 'active',
            'message' => rand(1, 100) <= 20 ? 'Hey! I really liked your profile 😊' : null,
            'liked_at' => Carbon::now()->subDays(rand(0, 30)),
            'is_mutual' => 'pending',
            'metadata' => json_encode(['swipe_location' => 'discovery']),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
    echo "👤 User {$user->id} ({$user->name}): liked {$likeCount} users\n";
}

// Insert user likes in batches
echo "\n📱 Inserting " . count($userLikes) . " user likes...\n";
$likeBatches = array_chunk($userLikes, 500);
foreach ($likeBatches as $batch) {
    DB::table('user_likes')->insert($batch);
}

// Find mutual likes and create matches
echo "💕 Finding mutual likes...\n";
$mutualMatches = [];
$likes = DB::table('user_likes')->get();

foreach ($likes as $like) {
    // Check if there's a mutual like
    $mutualLike = $likes->where('liker_id', $like->liked_user_id)
                       ->where('liked_user_id', $like->liker_id)
                       ->first();
    
    if ($mutualLike && $like->liker_id < $like->liked_user_id) { // Avoid duplicates
        $mutualMatches[] = [
            'user_id' => $like->liker_id,
            'matched_user_id' => $like->liked_user_id,
            'status' => 'active',
            'matched_at' => Carbon::now()->subDays(rand(0, 15)),
            'match_type' => 'mutual_like',
            'compatibility_score' => rand(70, 99) / 100,
            'is_conversation_started' => 'no',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

// Insert matches
if (!empty($mutualMatches)) {
    echo "💖 Inserting " . count($mutualMatches) . " mutual matches...\n";
    $matchBatches = array_chunk($mutualMatches, 100);
    foreach ($matchBatches as $batch) {
        DB::table('user_matches')->insert($batch);
    }
}

echo "\n🎉 Simple dating test data creation complete!\n";
echo "📊 Summary:\n";
echo "   👥 " . $users->count() . " user profiles\n";
echo "   💕 " . count($userLikes) . " user likes\n";
echo "   💖 " . count($mutualMatches) . " mutual matches\n";
echo "\n✨ Your dating app now has realistic interaction data for testing!\n";
echo "💡 Note: Chat system uses product-based structure, not user-to-user dating chats\n";

?>
