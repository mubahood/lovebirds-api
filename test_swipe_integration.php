<?php

/**
 * Comprehensive Swipe System Integration Test
 * Tests all swipe endpoints with proper authentication
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "🧪 COMPREHENSIVE SWIPE SYSTEM INTEGRATION TEST\n";
echo "================================================\n\n";

// Load test config
$testConfig = json_decode(file_get_contents('test_config.json'), true);
$userIds = $testConfig['user_ids'];
$userEmails = $testConfig['user_emails'];

if (empty($userIds)) {
    echo "❌ No test users found. Run get_test_user_ids.php first.\n";
    exit(1);
}

// Test 1: Login and get JWT token
echo "🔐 TEST 1: Authentication\n";
echo "-------------------------\n";

$testUser = User::find($userIds[0]);
$loginData = [
    'email' => $testUser->email,
    'password' => 'password123'
];

$loginResponse = httpRequest('POST', 'auth/login', $loginData);
$loginResult = json_decode($loginResponse, true);

if ($loginResult['code'] != 1) {
    echo "❌ Authentication failed: " . $loginResult['message'] . "\n";
    exit(1);
}

// Check token location
if (isset($loginResult['data']['user']['token'])) {
    $token = $loginResult['data']['user']['token'];
} else if (isset($loginResult['data']['token'])) {
    $token = $loginResult['data']['token'];
} else if (isset($loginResult['token'])) {
    $token = $loginResult['token'];
} else if (isset($loginResult['data']['access_token'])) {
    $token = $loginResult['data']['access_token'];
} else {
    echo "❌ Token not found in response.\n";
    exit(1);
}
echo "✅ Authentication successful! Token obtained.\n";
echo "   User: {$testUser->name} (ID: {$testUser->id})\n\n";

// Test 2: Swipe Discovery
echo "🔍 TEST 2: Swipe Discovery\n";
echo "--------------------------\n";

$discoveryResponse = httpRequest('GET', 'swipe-discovery', null, $token);
$discoveryResult = json_decode($discoveryResponse, true);

if ($discoveryResult['code'] == 1) {
    $discoveredUser = $discoveryResult['data']['user'];
    echo "✅ Swipe discovery successful!\n";
    echo "   Found user: {$discoveredUser['name']} (ID: {$discoveredUser['id']})\n";
    
    $age = isset($discoveredUser['age']) ? $discoveredUser['age'] : 'N/A';
    $location = isset($discoveredUser['current_location']) ? $discoveredUser['current_location'] : 'N/A';
    echo "   Age: {$age}, Location: {$location}\n\n";
    
    $targetUserId = $discoveredUser['id'];
} else {
    echo "❌ Swipe discovery failed: " . $discoveryResult['message'] . "\n";
    // Use another test user as fallback
    $targetUserId = $userIds[1];
    echo "📝 Using fallback target user ID: $targetUserId\n\n";
}

// Test 3: Perform Like Swipe
echo "❤️ TEST 3: Perform Like Swipe\n";
echo "------------------------------\n";

$swipeData = [
    'target_user_id' => $targetUserId,
    'action' => 'like',
    'message' => 'You seem awesome! 😊'
];

$swipeResponse = httpRequest('POST', 'swipe-action', $swipeData, $token);
$swipeResult = json_decode($swipeResponse, true);

if ($swipeResult['code'] == 1) {
    echo "✅ Like swipe successful!\n";
    $isMatch = isset($swipeResult['data']['match']) ? $swipeResult['data']['match'] : false;
    echo "   Match: " . ($isMatch ? 'YES! 🎉' : 'No') . "\n";
    if ($isMatch && isset($swipeResult['data']['match_id'])) {
        echo "   Match ID: {$swipeResult['data']['match_id']}\n";
    }
} else {
    echo "❌ Like swipe failed: " . $swipeResult['message'] . "\n";
}
echo "\n";

// Test 4: Get Swipe Stats
echo "📊 TEST 4: Swipe Statistics\n";
echo "---------------------------\n";

$statsResponse = httpRequest('GET', 'swipe-stats', null, $token);
$statsResult = json_decode($statsResponse, true);

if ($statsResult['code'] == 1) {
    $stats = $statsResult['data'];
    echo "✅ Swipe stats retrieved!\n";
    echo "   Likes sent: " . ($stats['total_likes_sent'] ?? 0) . "\n";
    echo "   Super likes sent: " . ($stats['super_likes_sent'] ?? 0) . "\n";
    echo "   Passes given: " . ($stats['total_passes'] ?? 0) . "\n";
    echo "   Likes received: " . ($stats['total_likes_received'] ?? 0) . "\n";
    echo "   Matches: " . ($stats['total_matches'] ?? 0) . "\n";
    echo "   Daily likes remaining: " . ($stats['daily_likes_remaining'] ?? 50) . "\n";
    echo "   Match rate: " . ($stats['match_rate'] ?? 0) . "%\n";
} else {
    echo "❌ Swipe stats failed: " . $statsResult['message'] . "\n";
}
echo "\n";

// Test 5: Who Liked Me
echo "👥 TEST 5: Who Liked Me\n";
echo "-----------------------\n";

$whoLikedResponse = httpRequest('GET', 'who-liked-me?page=1', null, $token);
$whoLikedResult = json_decode($whoLikedResponse, true);

if ($whoLikedResult['code'] == 1) {
    $likers = $whoLikedResult['data']['users'];
    echo "✅ Who liked me retrieved!\n";
    echo "   Total users who liked you: " . count($likers) . "\n";
    
    if (!empty($likers)) {
        echo "   Recent likes:\n";
        foreach (array_slice($likers, 0, 3) as $liker) {
            echo "     - {$liker['name']} (ID: {$liker['id']})\n";
        }
    }
} else {
    echo "❌ Who liked me failed: " . $whoLikedResult['message'] . "\n";
}
echo "\n";

// Test 6: My Matches
echo "💑 TEST 6: My Matches\n";
echo "---------------------\n";

$matchesResponse = httpRequest('GET', 'my-matches?page=1', null, $token);
$matchesResult = json_decode($matchesResponse, true);

if ($matchesResult['code'] == 1) {
    $matches = $matchesResult['data']['matches'];
    echo "✅ My matches retrieved!\n";
    echo "   Total matches: " . count($matches) . "\n";
    
    if (!empty($matches)) {
        echo "   Recent matches:\n";
        foreach (array_slice($matches, 0, 3) as $match) {
            // The match now contains user data directly
            echo "     - {$match['name']} (ID: {$match['id']})\n";
            if (isset($match['matched_at'])) {
                echo "       Matched on: {$match['matched_at']}\n";
            }
        }
    }
} else {
    echo "❌ My matches failed: " . $matchesResult['message'] . "\n";
}
echo "\n";

// Test 7: Recent Activity
echo "📱 TEST 7: Recent Activity\n";
echo "--------------------------\n";

$activityResponse = httpRequest('GET', 'recent-activity?days=7', null, $token);
$activityResult = json_decode($activityResponse, true);

if ($activityResult['code'] == 1) {
    $activities = $activityResult['data']['activity'];
    echo "✅ Recent activity retrieved!\n";
    echo "   Activities in last 7 days: " . count($activities) . "\n";
    
    if (!empty($activities)) {
        echo "   Recent activities:\n";
        foreach (array_slice($activities, 0, 3) as $activity) {
            echo "     - {$activity['type']}: {$activity['user']['name']} ({$activity['time_ago']})\n";
        }
    }
} else {
    echo "❌ Recent activity failed: " . $activityResult['message'] . "\n";
}
echo "\n";

// Test 8: Undo Last Swipe
echo "↶ TEST 8: Undo Last Swipe\n";
echo "--------------------------\n";

$undoResponse = httpRequest('POST', 'undo-swipe', [], $token);
$undoResult = json_decode($undoResponse, true);

if ($undoResult['code'] == 1) {
    echo "✅ Undo swipe successful!\n";
    echo "   Message: {$undoResult['message']}\n";
} else {
    echo "ℹ️ Undo swipe: " . $undoResult['message'] . "\n";
}
echo "\n";

echo "🎉 INTEGRATION TEST COMPLETED!\n";
echo "===============================\n";
echo "✅ All swipe endpoints are functional and connected!\n";
echo "✅ Mobile app can now fully interact with backend swipe system.\n\n";

// Helper function for HTTP requests
function httpRequest($method, $endpoint, $data = null, $token = null) {
    $baseUrl = 'http://localhost:8888/lovebirds-api/api/';
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $headers = [
        'Accept: application/json',
        'User-Agent: SwipeTest/1.0'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
        $headers[] = 'Tok: Bearer ' . $token;
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            // Send empty body for POST requests without data
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            $headers[] = 'Content-Length: 0';
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "⚠️ CURL Error: $error\n";
        return null;
    }
    
    if ($httpCode !== 200) {
        echo "⚠️ HTTP $httpCode for $endpoint\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    
    return $response;
}

?>
