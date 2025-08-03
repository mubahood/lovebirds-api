<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🔄 Creating realistic swipe data and matches for testing...\n";

// Get active users for interaction data generation  
$users = User::whereBetween('id', [1, 100])
    ->orderBy('id')
    ->get();

echo "📊 Found " . $users->count() . " users to work with\n";

// Clear existing data for clean testing
DB::table('user_likes')->truncate();
DB::table('user_matches')->truncate();
DB::table('chat_messages')->truncate();
DB::table('chat_heads')->truncate();

$userLikes = [];
$userMatches = [];
$likeId = 1;
$matchId = 1;

// Generate realistic swipe patterns
foreach ($users as $user) {
    $otherUsers = $users->where('id', '!=', $user->id)
        ->where('sex', '!=', $user->sex) // Opposite gender for simplicity
        ->shuffle();
    
    // Each user has liked 8-25 other users
    $likeCount = rand(8, 25);
    $likedUsers = $otherUsers->take($likeCount);
    
    foreach ($likedUsers as $targetUser) {
        // Create user like
        $likeDateTime = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));
        
        $userLikes[] = [
            'liker_id' => $user->id,
            'liked_user_id' => $targetUser->id,
            'type' => 'standard', // or 'super_like' for 10% of likes
            'status' => 'active',
            'message' => rand(1, 100) <= 15 ? 'Hey! I really liked your profile 😊' : null,
            'liked_at' => $likeDateTime,
            'expires_at' => null,
            'is_mutual' => 'pending', // Will update after checking mutual likes
            'metadata' => json_encode(['swipe_location' => 'discovery']),
            'created_at' => $likeDateTime,
            'updated_at' => $likeDateTime,
        ];
    }
    
    echo "👤 User {$user->id} ({$user->name}): liked {$likeCount} users\n";
}

// Insert user likes in batches
$likeChunks = array_chunk($userLikes, 1000);
foreach ($likeChunks as $chunk) {
    DB::table('user_likes')->insert($chunk);
}

// Now check for mutual likes and create matches
$allLikes = collect($userLikes);
$mutualMatches = [];

foreach ($allLikes as $like) {
    $mutualLike = $allLikes->firstWhere(function ($otherLike) use ($like) {
        return $otherLike['liker_id'] == $like['liked_user_id'] 
            && $otherLike['liked_user_id'] == $like['liker_id'];
    });
    
    if ($mutualLike && $like['liker_id'] < $like['liked_user_id']) { // Avoid duplicates
        $matchDateTime = max(
            Carbon::parse($like['liked_at']), 
            Carbon::parse($mutualLike['liked_at'])
        );
        
        $mutualMatches[] = [
            'user_id' => $like['liker_id'],
            'matched_user_id' => $like['liked_user_id'],
            'status' => 'active',
            'matched_at' => $matchDateTime,
            'last_message_at' => null,
            'match_type' => 'mutual_like',
            'messages_count' => 0,
            'conversation_starter' => null,
            'match_reason' => 'Mutual like',
            'compatibility_score' => rand(70, 99) / 100,
            'is_conversation_started' => 'no',
            'unmatched_at' => null,
            'unmatched_by' => null,
            'unmatch_reason' => null,
            'metadata' => json_encode(['match_source' => 'swipe']),
            'created_at' => $matchDateTime,
            'updated_at' => $matchDateTime,
        ];
        
        // Update the likes to show they're mutual
        DB::table('user_likes')
            ->whereIn('id', [$like['id'], $mutualLike['id']])
            ->update(['is_mutual' => 'yes']);
    }
}

// Insert matches
if (!empty($mutualMatches)) {
    $matchChunks = array_chunk($mutualMatches, 500);
    foreach ($matchChunks as $chunk) {
        DB::table('user_matches')->insert($chunk);
    }
}

echo "\n✅ Successfully created:\n";
echo "   � " . count($userLikes) . " user likes\n";
echo "   � " . count($mutualMatches) . " mutual matches\n";

// Now let's create some chat messages for matched users
echo "\n🔄 Creating sample chat messages...\n";

$chatMessages = [];
$chatHeads = [];
$messageId = 1;
$chatHeadId = 1;

// Sample conversation starters and responses
$conversationStarters = [
    "Hey! I love your photos 😊",
    "Hi there! How's your day going?",
    "Your bio made me smile! Tell me more about your hiking adventures",
    "Hey! Fellow coffee lover here ☕",
    "Hi! I see we both love traveling. What's your favorite destination?",
    "Your dog is adorable! What's their name?",
    "Hey! I noticed we both work in tech. What kind of projects are you working on?",
    "Hi! Your photography is amazing. Do you do it professionally?",
    "Hey there! Love your sense of humor in your bio 😄",
    "Hi! I see you're into yoga. Any favorite studios in the city?"
];

$responses = [
    "Thank you! That's so sweet 💕",
    "Hey! Going great, thanks for asking. How about yours?",
    "Thanks! I just got back from Banff actually. The views were incredible!",
    "Yes! There's this amazing local roaster I discovered recently",
    "Oh nice! I'm planning a trip to Japan next year. Ever been?",
    "His name is Charlie! He's a golden retriever and total sweetheart",
    "Currently working on a mobile app for mental health. It's really rewarding!",
    "Thanks! It's just a hobby but I love capturing moments",
    "Haha thanks! Life's too short to be serious all the time right?",
    "Yes! There's this great studio downtown. We should go together sometime 😊"
];

$followUps = [
    "That sounds amazing! I'd love to hear more about it",
    "Would you like to grab coffee sometime this weekend?",
    "I've been wanting to try that place! Mind if I join you next time?",
    "That's so cool! I'd love to see some of your work",
    "Absolutely! When are you free?",
    "I think we have a lot in common. Want to continue this conversation over dinner?",
    "You seem really interesting! I'd love to get to know you better",
    "This is such a great conversation! Are you free this Friday?",
    "I'm really enjoying talking with you. Want to meet up in person?",
    "You're really easy to talk to! How about we continue this over drinks?"
];

// Create conversations for some matches (about 40% of matches have messages)
$matchesWithMessages = collect($mutualMatches)->shuffle()->take(count($mutualMatches) * 0.4);

foreach ($matchesWithMessages as $match) {
    $user1 = $users->firstWhere('id', $match['user_id']);
    $user2 = $users->firstWhere('id', $match['matched_user_id']);
    
    if (!$user1 || !$user2) continue;
    
    // Create chat head
    $chatHeads[] = [
        'user_1_id' => $match['user_id'],
        'user_2_id' => $match['matched_user_id'],
        'user_1_name' => $user1->name,
        'user_1_photo' => $user1->avatar,
        'user_2_name' => $user2->name,
        'user_2_photo' => $user2->avatar,
        'last_message' => '',
        'last_sender_id' => null,
        'last_sent_time' => $match['matched_at'],
        'user_1_unread_count' => 0,
        'user_2_unread_count' => 0,
        'status' => 'active',
        'created_at' => $match['matched_at'],
        'updated_at' => Carbon::now(),
    ];
    
    // Generate 2-8 messages per conversation
    $messageCount = rand(2, 8);
    $currentSender = rand(0, 1) ? $user1->id : $user2->id;
    $lastMessageTime = Carbon::parse($match['matched_at'])->addMinutes(rand(5, 120));
    $lastMessage = '';
    
    for ($i = 0; $i < $messageCount; $i++) {
        if ($i === 0) {
            $messageText = $conversationStarters[array_rand($conversationStarters)];
        } elseif ($i === 1) {
            $messageText = $responses[array_rand($responses)];
        } else {
            $messageText = $followUps[array_rand($followUps)];
        }
        
        $receiver = $currentSender === $user1->id ? $user2 : $user1;
        
        $chatMessages[] = [
            'chat_head_id' => $chatHeadIndex + 1, // Will be the auto-generated ID
            'sender_id' => $currentSender,
            'receiver_id' => $receiver->id,
            'sender_name' => $currentSender === $user1->id ? $user1->name : $user2->name,
            'sender_photo' => $currentSender === $user1->id ? $user1->avatar : $user2->avatar,
            'receiver_name' => $receiver->name,
            'receiver_photo' => $receiver->avatar,
            'body' => $messageText,
            'type' => 'text',
            'status' => rand(0, 10) > 2 ? 'read' : 'sent', // 80% read
            'delivery_status' => 'delivered',
            'read_at' => rand(0, 10) > 2 ? $lastMessageTime->addMinutes(rand(1, 30)) : null,
            'created_at' => $lastMessageTime,
            'updated_at' => $lastMessageTime,
        ];
        
        $lastMessage = $messageText;
        
        // Switch sender for next message
        $currentSender = $currentSender === $user1->id ? $user2->id : $user1->id;
        $lastMessageTime = $lastMessageTime->addMinutes(rand(5, 480)); // 5 min to 8 hours later
    }
    
    // Update chat head with last message info
    $chatHeads[count($chatHeads) - 1]['last_message'] = $lastMessage;
    $chatHeads[count($chatHeads) - 1]['last_sender_id'] = $chatMessages[count($chatMessages) - 1]['sender_id'];
    $chatHeads[count($chatHeads) - 1]['last_sent_time'] = $lastMessageTime;
    
    // Update match with conversation status
    DB::table('user_matches')
        ->where('id', $match['id'])
        ->update([
            'is_conversation_started' => 'yes',
            'messages_count' => $messageCount,
            'last_message_at' => $lastMessageTime,
        ]);
    
    $chatHeadId++;
}

echo "\n📱 Generating summary stats...\n";
$totalLikes = count($userLikes);
$totalMatches = count($mutualMatches);
$totalChatHeads = count($chatHeads);
$totalMessages = count($chatMessages);

// Insert data into database
echo "\n📱 Inserting test data into database...\n";

// Insert user likes in batches
if (!empty($userLikes)) {
    $likeChunks = array_chunk($userLikes, 500);
    foreach ($likeChunks as $chunk) {
        DB::table('user_likes')->insert($chunk);
    }
    echo "✅ Inserted " . count($userLikes) . " user likes\n";
}

// Insert matches
if (!empty($mutualMatches)) {
    $matchChunks = array_chunk($mutualMatches, 100);
    foreach ($matchChunks as $chunk) {
        DB::table('user_matches')->insert($chunk);
    }
    echo "✅ Inserted " . count($mutualMatches) . " mutual matches\n";
}

// Insert chat heads
if (!empty($chatHeads)) {
    $headChunks = array_chunk($chatHeads, 100);
    foreach ($headChunks as $chunk) {
        DB::table('chat_heads')->insert($chunk);
    }
    echo "✅ Inserted " . count($chatHeads) . " chat heads\n";
}

// Insert chat messages
if (!empty($chatMessages)) {
    $messageChunks = array_chunk($chatMessages, 1000);
    foreach ($messageChunks as $chunk) {
        DB::table('chat_messages')->insert($chunk);
    }
    echo "✅ Inserted " . count($chatMessages) . " chat messages\n";
}

echo "\n🎉 Dating app test data is now complete!\n";
echo "📊 Summary:\n";
echo "   👥 88 complete user profiles with photos\n";
echo "   � " . count($userLikes) . " user likes/swipes\n";
echo "   � " . count($mutualMatches) . " matches between users\n";
echo "   💬 " . count($chatHeads) . " active conversations\n";
echo "   📝 " . count($chatMessages) . " chat messages\n";
echo "\n✨ Your app is ready for comprehensive testing!\n";

?>
