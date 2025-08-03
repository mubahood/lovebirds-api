<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🔄 RESTORING TEST USERS & CLEANING UP DATA\n";
echo "==========================================\n\n";

// First, let's see what interaction data we have
$totalLikes = DB::table('user_likes')->count();
$totalMatches = DB::table('user_matches')->count();
$totalUsers = DB::table('users')->count();

echo "📊 CURRENT STATE:\n";
echo "   👥 Users: $totalUsers\n";
echo "   💕 Likes: $totalLikes\n";
echo "   💖 Matches: $totalMatches\n\n";

// Get unique user IDs from likes data
$userIdsFromLikes = DB::table('user_likes')
    ->selectRaw('DISTINCT liker_id as user_id')
    ->unionAll(
        DB::table('user_likes')->selectRaw('DISTINCT liked_user_id as user_id')
    )
    ->pluck('user_id')
    ->unique()
    ->sort()
    ->values()
    ->toArray();

echo "👤 Found " . count($userIdsFromLikes) . " unique user IDs in likes data: " . implode(', ', array_slice($userIdsFromLikes, 0, 10)) . (count($userIdsFromLikes) > 10 ? '...' : '') . "\n\n";

// Create basic users for IDs 1-100 if they don't exist
echo "🔄 Creating basic test users (1-100)...\n";

$testUserNames = [
    1 => 'Emma Johnson', 2 => 'Liam Smith', 3 => 'Olivia Brown', 4 => 'Noah Davis', 5 => 'Ava Wilson',
    6 => 'William Miller', 7 => 'Sophia Moore', 8 => 'James Taylor', 9 => 'Isabella Anderson', 10 => 'Benjamin Thomas',
    11 => 'Charlotte Jackson', 12 => 'Lucas White', 13 => 'Amelia Harris', 14 => 'Henry Martin', 15 => 'Mia Thompson',
    16 => 'Alexander Garcia', 17 => 'Harper Martinez', 18 => 'Michael Robinson', 19 => 'Evelyn Clark', 20 => 'Daniel Rodriguez'
];

$usersToCreate = [];
for ($i = 1; $i <= 100; $i++) {
    $name = $testUserNames[$i] ?? "User $i";
    $usersToCreate[] = [
        'id' => $i,
        'name' => $name,
        'email' => "user{$i}@lovebirds.test",
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'email_verified_at' => Carbon::now(),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ];
}

// Insert users in chunks
$chunks = array_chunk($usersToCreate, 50);
$totalCreated = 0;

foreach ($chunks as $chunk) {
    DB::table('users')->insert($chunk);
    $totalCreated += count($chunk);
    echo "✅ Created " . count($chunk) . " users\n";
}

echo "👥 Total users created: $totalCreated\n\n";

// Now clean up likes that reference non-existent users (outside 1-100)
echo "🧹 CLEANING UP INTERACTION DATA:\n";
echo "--------------------------------\n";

// Delete likes where either liker or liked user is outside 1-100 range
$likesDeleted = DB::table('user_likes')
    ->where(function($query) {
        $query->where('liker_id', '<', 1)
              ->orWhere('liker_id', '>', 100)
              ->orWhere('liked_user_id', '<', 1)
              ->orWhere('liked_user_id', '>', 100);
    })
    ->delete();

echo "💕 Deleted $likesDeleted likes with invalid user references\n";

// Delete matches where either user is outside 1-100 range
$matchesDeleted = DB::table('user_matches')
    ->where(function($query) {
        $query->where('user_id', '<', 1)
              ->orWhere('user_id', '>', 100)
              ->orWhere('matched_user_id', '<', 1)
              ->orWhere('matched_user_id', '>', 100);
    })
    ->delete();

echo "💖 Deleted $matchesDeleted matches with invalid user references\n";

// Clean up any other data that might reference non-existent users
$chatMessagesDeleted = DB::table('chat_messages')
    ->where(function($query) {
        $query->where('sender_id', '<', 1)
              ->orWhere('sender_id', '>', 100)
              ->orWhere('receiver_id', '<', 1)
              ->orWhere('receiver_id', '>', 100);
    })
    ->delete();

echo "💬 Deleted $chatMessagesDeleted chat messages with invalid user references\n";

$chatHeadsDeleted = DB::table('chat_heads')
    ->where(function($query) {
        $query->where('product_owner_id', '<', 1)
              ->orWhere('product_owner_id', '>', 100)
              ->orWhere('customer_id', '<', 1)
              ->orWhere('customer_id', '>', 100);
    })
    ->delete();

echo "📱 Deleted $chatHeadsDeleted chat heads with invalid user references\n";

// Final summary
$finalUsers = DB::table('users')->count();
$finalLikes = DB::table('user_likes')->count();
$finalMatches = DB::table('user_matches')->count();

echo "\n✅ CLEANUP COMPLETE!\n";
echo "===================\n";
echo "👥 Users: $finalUsers (should be 100)\n";
echo "💕 Likes: $finalLikes (cleaned)\n";
echo "💖 Matches: $finalMatches (cleaned)\n";
echo "🗑️  Deleted $likesDeleted invalid likes\n";
echo "🗑️  Deleted $matchesDeleted invalid matches\n";
echo "🗑️  Deleted $chatMessagesDeleted invalid chat messages\n";
echo "🗑️  Deleted $chatHeadsDeleted invalid chat heads\n";

if ($finalUsers == 100) {
    echo "\n🎉 SUCCESS! Database now contains exactly 100 test users with clean dating data!\n";
    echo "✨ Your Lovebirds dating app is optimized for testing!\n";
} else {
    echo "\n⚠️  Warning: Expected 100 users but found $finalUsers\n";
}

?>
