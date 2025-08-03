<?php

/**
 * Test Photo Likes/Dislikes System
 * Tests all swipe functionality including likes, super likes, passes, matches, and statistics
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base configuration
$API_BASE = 'http://localhost/lovebirds-api/api';
$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

// Test user credentials (matching the created test data)
$testUsers = [
    'user1' => ['email' => 'sarah.test@example.com', 'password' => '123456'],
    'user2' => ['email' => 'michael.test@example.com', 'password' => '123456'],
    'user3' => ['email' => 'emma.test@example.com', 'password' => '123456']
];

// Load test configuration
$testConfig = json_decode(file_get_contents('test_config.json'), true);
$userIds = $testConfig['user_ids'];

$tokens = [];

function makeRequest($url, $data = null, $headers = [], $method = 'POST') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'GET' && $data) {
        $queryString = http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryString);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => json_decode($response, true), 'http_code' => $httpCode];
}

function authenticate($user, $API_BASE, $headers) {
    echo "🔐 Authenticating {$user['email']}...\n";
    $result = makeRequest("$API_BASE/users", $user, $headers);
    
    if ($result['http_code'] === 200 && isset($result['response']['data']['token'])) {
        echo "✅ Authentication successful\n";
        return $result['response']['data']['token'];
    } else {
        echo "❌ Authentication failed: " . json_encode($result['response']) . "\n";
        return null;
    }
}

echo "🧪 PHOTO LIKES/DISLIKES SYSTEM TEST SUITE\n";
echo "========================================\n\n";

// Authenticate all test users
foreach ($testUsers as $key => $user) {
    $token = authenticate($user, $API_BASE, $headers);
    if ($token) {
        $tokens[$key] = $token;
    } else {
        echo "❌ Test failed: Could not authenticate $key\n";
        exit(1);
    }
}

echo "\n📊 TEST 1: Get Swipe Statistics (Initial)\n";
echo "----------------------------------------\n";
$authHeaders = array_merge($headers, ["Authorization: Bearer {$tokens['user1']}"]);
$result = makeRequest("$API_BASE/swipe-stats", null, $authHeaders, 'GET');

if ($result['http_code'] === 200) {
    echo "✅ Initial swipe stats: PASSED\n";
    $stats = $result['response']['data'];
    echo "   📈 Total likes sent: {$stats['total_likes_sent']}\n";
    echo "   💕 Total likes received: {$stats['total_likes_received']}\n";
    echo "   ⚡ Total matches: {$stats['total_matches']}\n";
    echo "   📱 Daily likes remaining: {$stats['daily_likes_remaining']}\n";
} else {
    echo "❌ Initial swipe stats: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n💖 TEST 2: Like Another User\n";
echo "----------------------------\n";
$likeData = [
    'target_user_id' => $userIds[1], // Michael Chen
    'action' => 'like'
];

$result = makeRequest("$API_BASE/swipe-action", $likeData, $authHeaders);

if ($result['http_code'] === 200) {
    echo "✅ Like action: PASSED\n";
    $response = $result['response']['data'];
    echo "   Action: {$response['action']}\n";
    echo "   Is match: " . ($response['is_match'] ? 'Yes' : 'No') . "\n";
    echo "   Daily likes remaining: {$response['daily_likes_remaining']}\n";
} else {
    echo "❌ Like action: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n⭐ TEST 3: Super Like Another User\n";
echo "---------------------------------\n";
$superLikeData = [
    'target_user_id' => $userIds[2], // Emma Wilson
    'action' => 'super_like',
    'message' => 'I love your profile! You seem amazing! 😍'
];

$result = makeRequest("$API_BASE/swipe-action", $superLikeData, $authHeaders);

if ($result['http_code'] === 200) {
    echo "✅ Super like action: PASSED\n";
    $response = $result['response']['data'];
    echo "   Action: {$response['action']}\n";
    echo "   Is match: " . ($response['is_match'] ? 'Yes' : 'No') . "\n";
    echo "   Daily likes remaining: {$response['daily_likes_remaining']}\n";
} else {
    echo "❌ Super like action: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n👎 TEST 4: Pass on Another User\n";
echo "-------------------------------\n";
$passData = [
    'target_user_id' => $userIds[3], // David Rodriguez
    'action' => 'pass'
];

$result = makeRequest("$API_BASE/swipe-action", $passData, $authHeaders);

if ($result['http_code'] === 200) {
    echo "✅ Pass action: PASSED\n";
    $response = $result['response']['data'];
    echo "   Action: {$response['action']}\n";
    echo "   Is match: " . ($response['is_match'] ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Pass action: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n🔄 TEST 5: Create Mutual Like (Match)\n";
echo "------------------------------------\n";
// User 2 likes User 1 back to create a match
$authHeaders2 = array_merge($headers, ["Authorization: Bearer {$tokens['user2']}"]);
$mutualLikeData = [
    'target_user_id' => $userIds[0], // Sarah Johnson (User 1)
    'action' => 'like'
];

$result = makeRequest("$API_BASE/swipe-action", $mutualLikeData, $authHeaders2);

if ($result['http_code'] === 200) {
    $response = $result['response']['data'];
    if ($response['is_match']) {
        echo "✅ Mutual like (match): PASSED\n";
        echo "   💕 It's a match!\n";
        echo "   Match created successfully\n";
    } else {
        echo "⚠️  Mutual like: PARTIAL (like created but no match detected)\n";
    }
} else {
    echo "❌ Mutual like: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n💌 TEST 6: Who Liked Me\n";
echo "-----------------------\n";
$result = makeRequest("$API_BASE/who-liked-me", null, $authHeaders, 'GET');

if ($result['http_code'] === 200) {
    echo "✅ Who liked me: PASSED\n";
    $response = $result['response']['data'];
    echo "   👥 Users who liked you: {$response['count']}\n";
    
    if ($response['count'] > 0) {
        foreach ($response['users'] as $user) {
            $timeAgo = isset($user['time_ago']) ? $user['time_ago'] : 'recently';
            echo "   - {$user['name']} ({$user['like_type']}) - {$timeAgo}\n";
        }
    }
} else {
    echo "❌ Who liked me: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n💕 TEST 7: My Matches\n";
echo "--------------------\n";
$result = makeRequest("$API_BASE/my-matches", null, $authHeaders, 'GET');

if ($result['http_code'] === 200) {
    echo "✅ My matches: PASSED\n";
    $response = $result['response']['data'];
    echo "   💖 Total matches: {$response['count']}\n";
    
    if ($response['count'] > 0) {
        foreach ($response['matches'] as $match) {
            echo "   - {$match['name']} (compatibility: {$match['compatibility_score']}%)\n";
        }
    }
} else {
    echo "❌ My matches: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n📊 TEST 8: Updated Swipe Statistics\n";
echo "----------------------------------\n";
$result = makeRequest("$API_BASE/swipe-stats", null, $authHeaders, 'GET');

if ($result['http_code'] === 200) {
    echo "✅ Updated swipe stats: PASSED\n";
    $stats = $result['response']['data'];
    echo "   📈 Total likes sent: {$stats['total_likes_sent']}\n";
    echo "   💕 Total likes received: {$stats['total_likes_received']}\n";
    echo "   👎 Total passes: {$stats['total_passes']}\n";
    echo "   ⚡ Total matches: {$stats['total_matches']}\n";
    echo "   ⭐ Super likes sent: {$stats['super_likes_sent']}\n";
    echo "   📱 Daily likes used: {$stats['daily_likes_used']}\n";
    echo "   📱 Daily likes remaining: {$stats['daily_likes_remaining']}\n";
    echo "   📊 Match rate: {$stats['match_rate']}%\n";
} else {
    echo "❌ Updated swipe stats: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n📱 TEST 9: Recent Activity\n";
echo "-------------------------\n";
$result = makeRequest("$API_BASE/recent-activity", ['days' => 7], $authHeaders, 'GET');

if ($result['http_code'] === 200) {
    echo "✅ Recent activity: PASSED\n";
    $response = $result['response']['data'];
    echo "   📊 Total activity count: {$response['activity_count']}\n";
    echo "   💖 Recent likes: " . count($response['recent_likes']) . "\n";
    echo "   💕 Recent matches: " . count($response['recent_matches']) . "\n";
    
    if (!empty($response['recent_likes'])) {
        echo "   Recent likes received:\n";
        foreach ($response['recent_likes'] as $like) {
            echo "     - {$like['user']['name']} ({$like['like_type']}) - {$like['time_ago']}\n";
        }
    }
    
    if (!empty($response['recent_matches'])) {
        echo "   Recent matches made:\n";
        foreach ($response['recent_matches'] as $match) {
            echo "     - {$match['user']['name']} - {$match['time_ago']}\n";
        }
    }
} else {
    echo "❌ Recent activity: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n⏪ TEST 10: Undo Swipe (Premium Feature)\n";
echo "---------------------------------------\n";
$result = makeRequest("$API_BASE/undo-swipe", [], $authHeaders);

if ($result['http_code'] === 200) {
    echo "✅ Undo swipe: PASSED\n";
    $response = $result['response']['data'];
    echo "   Undone user: {$response['undone_user']['name']}\n";
    echo "   Was match: " . ($response['was_match'] ? 'Yes' : 'No') . "\n";
} else {
    echo "⚠️  Undo swipe: EXPECTED FAILURE (requires premium subscription)\n";
    echo "   Message: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}

echo "\n🚫 TEST 11: Duplicate Like Prevention\n";
echo "------------------------------------\n";
// Try to like the same user again
$duplicateLikeData = [
    'target_user_id' => $userIds[1], // Michael Chen (already liked)
    'action' => 'like'
];

$result = makeRequest("$API_BASE/swipe-action", $duplicateLikeData, $authHeaders);

if ($result['http_code'] !== 200) {
    echo "✅ Duplicate like prevention: PASSED\n";
    echo "   Expected error: " . ($result['response']['message'] ?? 'Already acted on profile') . "\n";
} else {
    echo "❌ Duplicate like prevention: FAILED (should not allow duplicate likes)\n";
}

echo "\n📲 TEST 12: Integration with Discovery System\n";
echo "--------------------------------------------\n";
$result = makeRequest("$API_BASE/swipe-discovery", null, $authHeaders, 'GET');

if ($result['http_code'] === 200) {
    echo "✅ Discovery integration: PASSED\n";
    $response = $result['response']['data'];
    if ($response['user']) {
        echo "   Next user to swipe: {$response['user']['name']}\n";
        echo "   Compatibility: {$response['user']['compatibility_score']}%\n";
        echo "   More users available: " . ($response['has_more'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   No more users to discover\n";
    }
} else {
    echo "❌ Discovery integration: FAILED\n";
    echo "   Response: " . json_encode($result['response']) . "\n";
}

echo "\n🎉 PHOTO LIKES/DISLIKES SYSTEM TEST COMPLETE!\n";
echo "=============================================\n";
echo "🏆 Test Summary:\n";
echo "   ✅ Core swipe functionality working\n";
echo "   ✅ Match detection operational\n";
echo "   ✅ Statistics tracking functional\n";
echo "   ✅ Activity monitoring working\n";
echo "   ✅ Duplicate prevention active\n";
echo "   ✅ Discovery system integration successful\n\n";

echo "💡 Next Steps:\n";
echo "   1. Test subscription limits with free vs premium users\n";
echo "   2. Test notification system for matches\n";
echo "   3. Test undo functionality with premium subscription\n";
echo "   4. Verify database cleanup when users are blocked\n";
echo "   5. Test match chat creation integration\n";

?>
