<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🗑️  CLEANING UP NON-TEST USERS AND DATA\n";
echo "==========================================\n\n";

// First, let's see what we're working with
$totalUsers = DB::table('users')->count();
$testUsers = DB::table('users')->whereBetween('id', [1, 100])->count();
$nonTestUsers = $totalUsers - $testUsers;

echo "📊 CURRENT DATABASE STATE:\n";
echo "   👥 Total users: $totalUsers\n";
echo "   🎯 Test users (1-100): $testUsers\n";
echo "   🗑️  Users to delete: $nonTestUsers\n\n";

if ($nonTestUsers == 0) {
    echo "✅ No users to delete - database already contains only test users!\n";
    exit;
}

echo "🔄 Starting cleanup process...\n\n";

// Get all user IDs that are NOT in the 1-100 range
$usersToDelete = DB::table('users')
    ->whereNotBetween('id', [1, 100])
    ->pluck('id')
    ->toArray();

if (empty($usersToDelete)) {
    echo "✅ No users found outside the 1-100 range!\n";
    exit;
}

echo "👥 Found " . count($usersToDelete) . " users to delete\n";

// Delete associated data first (to maintain referential integrity)
echo "\n🗑️  DELETING ASSOCIATED DATA:\n";
echo "-----------------------------\n";

// Delete user likes involving these users
$likesDeleted = DB::table('user_likes')
    ->where(function($query) use ($usersToDelete) {
        $query->whereIn('liker_id', $usersToDelete)
              ->orWhereIn('liked_user_id', $usersToDelete);
    })
    ->delete();
echo "💕 Deleted $likesDeleted user likes\n";

// Delete user matches involving these users
$matchesDeleted = DB::table('user_matches')
    ->where(function($query) use ($usersToDelete) {
        $query->whereIn('user_id', $usersToDelete)
              ->orWhereIn('matched_user_id', $usersToDelete);
    })
    ->delete();
echo "💖 Deleted $matchesDeleted user matches\n";

// Delete chat messages involving these users
$messagesDeleted = DB::table('chat_messages')
    ->where(function($query) use ($usersToDelete) {
        $query->whereIn('sender_id', $usersToDelete)
              ->orWhereIn('receiver_id', $usersToDelete);
    })
    ->delete();
echo "💬 Deleted $messagesDeleted chat messages\n";

// Delete chat heads involving these users
$chatHeadsDeleted = DB::table('chat_heads')
    ->where(function($query) use ($usersToDelete) {
        $query->whereIn('product_owner_id', $usersToDelete)
              ->orWhereIn('customer_id', $usersToDelete);
    })
    ->delete();
echo "📱 Deleted $chatHeadsDeleted chat heads\n";

// Delete products owned by these users
$productsDeleted = DB::table('products')
    ->whereIn('user_id', $usersToDelete)
    ->delete();
echo "📦 Deleted $productsDeleted products\n";

// Delete any other user-related data (add more tables as needed)
$viewProgressDeleted = DB::table('view_progress')
    ->whereIn('user_id', $usersToDelete)
    ->delete();
echo "📺 Deleted $viewProgressDeleted view progress records\n";

// Delete reports made by or against these users
$reportsDeleted = DB::table('moderation_reports')
    ->where(function($query) use ($usersToDelete) {
        $query->whereIn('reporter_id', $usersToDelete)
              ->orWhereIn('reported_user_id', $usersToDelete);
    })
    ->delete();
echo "🚨 Deleted $reportsDeleted moderation reports\n";

// Finally, delete the users themselves
echo "\n👤 DELETING USERS:\n";
echo "------------------\n";

// Delete users in chunks to avoid memory issues
$chunks = array_chunk($usersToDelete, 1000);
$totalDeleted = 0;

foreach ($chunks as $chunk) {
    $deleted = DB::table('users')->whereIn('id', $chunk)->delete();
    $totalDeleted += $deleted;
    echo "🗑️  Deleted $deleted users (batch)\n";
}

echo "\n✅ CLEANUP COMPLETE!\n";
echo "===================\n";
echo "👤 Total users deleted: $totalDeleted\n";
echo "💕 User likes deleted: $likesDeleted\n";
echo "💖 User matches deleted: $matchesDeleted\n";
echo "💬 Chat messages deleted: $messagesDeleted\n";
echo "📱 Chat heads deleted: $chatHeadsDeleted\n";
echo "📦 Products deleted: $productsDeleted\n";
echo "📺 View progress deleted: $viewProgressDeleted\n";
echo "🚨 Reports deleted: $reportsDeleted\n";

// Show final state
$finalUsers = DB::table('users')->count();
$finalTestUsers = DB::table('users')->whereBetween('id', [1, 100])->count();

echo "\n📊 FINAL DATABASE STATE:\n";
echo "   👥 Total users remaining: $finalUsers\n";
echo "   🎯 Test users (1-100): $finalTestUsers\n";

if ($finalUsers == $finalTestUsers) {
    echo "\n🎉 SUCCESS! Database now contains only test users (1-100)!\n";
    echo "✨ Your Lovebirds dating app is optimized for testing!\n";
} else {
    echo "\n⚠️  Warning: Some users outside 1-100 range still exist\n";
}

?>
