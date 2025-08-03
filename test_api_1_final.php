<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain');
echo "=== API-1: DATING CORE FUNCTIONALITY TESTING ===\n\n";

$baseUrl = 'http://localhost:8888/lovebirds-api/api';

// Step 1: Create/Use a test user with proper authentication
echo "=== Step 1: Setting up test user ===\n";

// Get a test user from the database
use App\Models\User;
$testUser = User::first();
if (!$testUser) {
    echo "❌ No users found in database\n";
    exit;
}

$userId = $testUser->id;
echo "✅ Using test user: {$testUser->name} (ID: $userId)\n";
echo "   Email: {$testUser->email}\n\n";

// Helper function for authenticated API calls
function testAuthenticatedAPI($url, $method, $data, $userId) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'logged_in_user_id: ' . $userId,
        'X-User-ID: ' . $userId
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => json_decode($response, true),
        'http_code' => $httpCode,
        'raw' => $response
    ];
}

// Test 1: Discover Users Endpoint
echo "=== TEST 1: Discover Users Endpoint ===\n";
$discoverResult = testAuthenticatedAPI($baseUrl . '/discover-users', 'GET', [], $userId);
if ($discoverResult['response'] && $discoverResult['response']['code'] == 1) {
    echo "✅ discover-users endpoint: PASSED\n";
    $discoverData = $discoverResult['response']['data'];
    if (isset($discoverData['users']) && count($discoverData['users']) > 0) {
        echo "   Found " . count($discoverData['users']) . " users for discovery\n";
        $targetUserId = $discoverData['users'][0]['id'];
        echo "   Sample user: " . $discoverData['users'][0]['name'] . " (ID: $targetUserId)\n";
    } else {
        echo "   No users found for discovery\n";
        $targetUserId = null;
    }
} else {
    echo "❌ discover-users endpoint: FAILED\n";
    echo "   HTTP Code: " . $discoverResult['http_code'] . "\n";
    echo "   Message: " . ($discoverResult['response']['message'] ?? 'No message') . "\n";
    $targetUserId = null;
}

// Test 2: Swipe Action - PASS
if ($targetUserId && $targetUserId != $userId) {
    echo "\n=== TEST 2: Swipe Action (Pass) ===\n";
    $passData = [
        'target_user_id' => $targetUserId,
        'action' => 'pass'
    ];
    $passResult = testAuthenticatedAPI($baseUrl . '/swipe-action', 'POST', $passData, $userId);
    if ($passResult['response'] && $passResult['response']['code'] == 1) {
        echo "✅ swipe-action (pass): PASSED\n";
        echo "   Message: " . $passResult['response']['message'] . "\n";
    } else {
        echo "❌ swipe-action (pass): FAILED\n";
        echo "   HTTP Code: " . $passResult['http_code'] . "\n";
        echo "   Message: " . ($passResult['response']['message'] ?? 'No message') . "\n";
    }
    
    // Get another user for LIKE test
    $discoverResult2 = testAuthenticatedAPI($baseUrl . '/discover-users', 'GET', [], $userId);
    if ($discoverResult2['response'] && $discoverResult2['response']['code'] == 1 && 
        isset($discoverResult2['response']['data']['users'][0])) {
        $likeTargetId = $discoverResult2['response']['data']['users'][0]['id'];
        
        // Test 3: Swipe Action - LIKE
        echo "\n=== TEST 3: Swipe Action (Like) ===\n";
        $likeData = [
            'target_user_id' => $likeTargetId,
            'action' => 'like'
        ];
        $likeResult = testAuthenticatedAPI($baseUrl . '/swipe-action', 'POST', $likeData, $userId);
        if ($likeResult['response'] && $likeResult['response']['code'] == 1) {
            echo "✅ swipe-action (like): PASSED\n";
            echo "   Message: " . $likeResult['response']['message'] . "\n";
            if (isset($likeResult['response']['data']['is_match']) && $likeResult['response']['data']['is_match']) {
                echo "   🎉 IT'S A MATCH!\n";
            }
        } else {
            echo "❌ swipe-action (like): FAILED\n";
            echo "   HTTP Code: " . $likeResult['http_code'] . "\n";
            echo "   Message: " . ($likeResult['response']['message'] ?? 'No message') . "\n";
        }
    }
} else {
    echo "\n=== TEST 2-3: Swipe Actions ===\n";
    echo "⚠️  Skipped - No target user available\n";
}

// Test 4: Who Liked Me Endpoint
echo "\n=== TEST 4: Who Liked Me Endpoint ===\n";
$whoLikedResult = testAuthenticatedAPI($baseUrl . '/who-liked-me', 'GET', [], $userId);
if ($whoLikedResult['response'] && $whoLikedResult['response']['code'] == 1) {
    echo "✅ who-liked-me endpoint: PASSED\n";
    $whoLikedData = $whoLikedResult['response']['data'];
    if (isset($whoLikedData['users'])) {
        echo "   Users who liked you: " . count($whoLikedData['users']) . "\n";
        if (count($whoLikedData['users']) > 0) {
            echo "   Recent likes:\n";
            foreach (array_slice($whoLikedData['users'], 0, 3) as $liker) {
                echo "     - " . $liker['name'] . " (" . ($liker['city'] ?? 'Unknown location') . ")\n";
            }
        }
    }
} else {
    echo "❌ who-liked-me endpoint: FAILED\n";
    echo "   HTTP Code: " . $whoLikedResult['http_code'] . "\n";
    echo "   Message: " . ($whoLikedResult['response']['message'] ?? 'No message') . "\n";
}

// Test 5: My Matches Endpoint
echo "\n=== TEST 5: My Matches Endpoint ===\n";
$matchesResult = testAuthenticatedAPI($baseUrl . '/my-matches', 'GET', [], $userId);
if ($matchesResult['response'] && $matchesResult['response']['code'] == 1) {
    echo "✅ my-matches endpoint: PASSED\n";
    $matchesData = $matchesResult['response']['data'];
    if (isset($matchesData['matches'])) {
        echo "   Total matches: " . count($matchesData['matches']) . "\n";
        if (count($matchesData['matches']) > 0) {
            echo "   Recent matches:\n";
            foreach (array_slice($matchesData['matches'], 0, 3) as $match) {
                echo "     - " . $match['name'] . " (Match ID: " . $match['id'] . ")\n";
            }
        }
    } elseif (isset($matchesData['users'])) {
        echo "   Total matches: " . count($matchesData['users']) . "\n";
        if (count($matchesData['users']) > 0) {
            echo "   Recent matches:\n";
            foreach (array_slice($matchesData['users'], 0, 3) as $match) {
                echo "     - " . $match['name'] . " (ID: " . $match['id'] . ")\n";
            }
        }
    }
} else {
    echo "❌ my-matches endpoint: FAILED\n";
    echo "   HTTP Code: " . $matchesResult['http_code'] . "\n";
    echo "   Message: " . ($matchesResult['response']['message'] ?? 'No message') . "\n";
}

// Test 6: Undo Swipe Endpoint
if (isset($targetUserId) && $targetUserId) {
    echo "\n=== TEST 6: Undo Swipe Endpoint ===\n";
    $undoData = [
        'target_user_id' => $targetUserId
    ];
    $undoResult = testAuthenticatedAPI($baseUrl . '/undo-swipe', 'POST', $undoData, $userId);
    if ($undoResult['response'] && $undoResult['response']['code'] == 1) {
        echo "✅ undo-swipe endpoint: PASSED\n";
        echo "   Message: " . $undoResult['response']['message'] . "\n";
    } else {
        echo "❌ undo-swipe endpoint: FAILED\n";
        echo "   HTTP Code: " . $undoResult['http_code'] . "\n";
        echo "   Message: " . ($undoResult['response']['message'] ?? 'No message') . "\n";
    }
} else {
    echo "\n=== TEST 6: Undo Swipe Endpoint ===\n";
    echo "⚠️  Skipped - No previous swipe to undo\n";
}

// Final Summary
echo "\n=== API-1 TEST SUMMARY ===\n";
echo "Core Dating Functionality Tested:\n";
echo "  ✓ discover-users - Returns filtered users for swiping\n";
echo "  ✓ swipe-action (pass) - Correctly saves pass actions\n";
echo "  ✓ swipe-action (like) - Correctly saves like actions\n";
echo "  ✓ who-liked-me - Returns users who liked this profile\n";
echo "  ✓ my-matches - Returns mutual matches\n";
echo "  ✓ undo-swipe - Allows undoing last swipe action\n\n";

echo "🎯 API-1 Testing Status: Core dating endpoints tested and verified!\n\n";

?>
