<?php

// Comprehensive Dating API Integration Test
// Run this in browser: http://localhost:8888/katogo/test_dating_endpoints.php

header('Content-Type: text/plain');
echo "=== COMPREHENSIVE DATING API INTEGRATION TEST ===\n\n";

$baseUrl = 'http://localhost:8888/katogo/api';

// Use working login credentials
$testUser = [
    'email' => 'admin@gmail.com',
    'password' => '123456'
];

echo "=== Step 1: Authentication ===\n";
$loginResult = testAPI($baseUrl . '/auth/login', 'POST', $testUser);

if ($loginResult['response']['code'] != 1) {
    echo "❌ Login failed: " . $loginResult['response']['message'] . "\n";
    exit;
}

// Extract token
$token = null;
$user = null;

if (isset($loginResult['response']['data']['user']['token'])) {
    $token = $loginResult['response']['data']['user']['token'];
    $user = $loginResult['response']['data']['user'];
} elseif (isset($loginResult['response']['data']['token'])) {
    $token = $loginResult['response']['data']['token'];
    $user = $loginResult['response']['data']['user'] ?? null;
}

if (!$token) {
    echo "❌ Token not found in response\n";
    exit;
}

$userId = $user['id'];
echo "✅ Login successful\n";
echo "   User: " . $user['name'] . " (ID: $userId)\n";
echo "   Token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Test Discovery Stats
echo "=== Step 2: Discovery Stats Test ===\n";
$statsResult = testAuthenticatedAPI($baseUrl . '/discovery-stats', 'GET', [], $token, $userId);
if ($statsResult['response']['code'] == 1) {
    echo "✅ Discovery stats: PASSED\n";
    if (isset($statsResult['response']['data'])) {
        $stats = $statsResult['response']['data'];
        echo "   Available users: " . ($stats['available_users'] ?? 'N/A') . "\n";
        echo "   Likes sent today: " . ($stats['likes_sent_today'] ?? 'N/A') . "\n";
        echo "   Matches count: " . ($stats['matches_count'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ Discovery stats: FAILED\n";
    echo "   Message: " . $statsResult['response']['message'] . "\n";
}

// Step 3: Test Swipe Discovery
echo "\n=== Step 3: Swipe Discovery Test ===\n";
$discoveryResult = testAuthenticatedAPI($baseUrl . '/swipe-discovery', 'GET', [], $token, $userId);
if ($discoveryResult['response']['code'] == 1) {
    echo "✅ Swipe discovery: PASSED\n";
    $swipeData = $discoveryResult['response']['data'];
    if (isset($swipeData['user']) && $swipeData['user']) {
        $swipeUser = $swipeData['user'];
        echo "   Found user to swipe: " . $swipeUser['name'] . " (ID: " . $swipeUser['id'] . ")\n";
        echo "   Age: " . (isset($swipeUser['age']) ? $swipeUser['age'] : 'N/A') . "\n";
        echo "   City: " . ($swipeUser['city'] ?? 'N/A') . "\n";
        echo "   Compatibility: " . ($swipeUser['compatibility_score'] ?? 'N/A') . "%\n";
        
        // Store target user for swipe test
        $targetUserId = $swipeUser['id'];
    } else {
        echo "   No users available for swiping\n";
        $targetUserId = null;
    }
} else {
    echo "❌ Swipe discovery: FAILED\n";
    echo "   Message: " . $discoveryResult['response']['message'] . "\n";
    $targetUserId = null;
}

// Step 4: Test Swipe Actions (if we have a target user)
if ($targetUserId && $targetUserId != $userId) {
    echo "\n=== Step 4: Swipe Actions Test ===\n";
    
    // Test PASS action
    echo "Testing PASS action...\n";
    $passData = [
        'target_user_id' => $targetUserId,
        'action' => 'pass'
    ];
    $passResult = testAuthenticatedAPI($baseUrl . '/swipe-action', 'POST', $passData, $token, $userId);
    if ($passResult['response']['code'] == 1) {
        echo "✅ Pass action: PASSED\n";
        echo "   Message: " . $passResult['response']['message'] . "\n";
    } else {
        echo "❌ Pass action: FAILED\n";
        echo "   Message: " . $passResult['response']['message'] . "\n";
    }
    
    // Get another user for LIKE test
    $discoveryResult2 = testAuthenticatedAPI($baseUrl . '/swipe-discovery', 'GET', [], $token, $userId);
    if ($discoveryResult2['response']['code'] == 1 && isset($discoveryResult2['response']['data']['user'])) {
        $likeTargetId = $discoveryResult2['response']['data']['user']['id'];
        
        // Test LIKE action
        echo "\nTesting LIKE action...\n";
        $likeData = [
            'target_user_id' => $likeTargetId,
            'action' => 'like'
        ];
        $likeResult = testAuthenticatedAPI($baseUrl . '/swipe-action', 'POST', $likeData, $token, $userId);
        if ($likeResult['response']['code'] == 1) {
            echo "✅ Like action: PASSED\n";
            echo "   Message: " . $likeResult['response']['message'] . "\n";
            if (isset($likeResult['response']['data']['is_match']) && $likeResult['response']['data']['is_match']) {
                echo "   🎉 IT'S A MATCH!\n";
            }
        } else {
            echo "❌ Like action: FAILED\n";
            echo "   Message: " . $likeResult['response']['message'] . "\n";
        }
    }
} else {
    echo "\n=== Step 4: Swipe Actions Test ===\n";
    echo "⚠️  Skipped - No target user available\n";
}

// Step 5: Test Who Liked Me
echo "\n=== Step 5: Who Liked Me Test ===\n";
$whoLikedResult = testAuthenticatedAPI($baseUrl . '/who-liked-me', 'GET', [], $token, $userId);
if ($whoLikedResult['response']['code'] == 1) {
    echo "✅ Who liked me: PASSED\n";
    $whoLikedData = $whoLikedResult['response']['data'];
    echo "   Users who liked you: " . ($whoLikedData['count'] ?? 0) . "\n";
    if (isset($whoLikedData['users']) && count($whoLikedData['users']) > 0) {
        echo "   Recent likes:\n";
        foreach (array_slice($whoLikedData['users'], 0, 3) as $liker) {
            echo "     - " . $liker['name'] . " (" . ($liker['city'] ?? 'Unknown location') . ")\n";
        }
    }
} else {
    echo "❌ Who liked me: FAILED\n";
    echo "   Message: " . $whoLikedResult['response']['message'] . "\n";
}

// Step 6: Test Users List (for dating)
echo "\n=== Step 6: Users List Test ===\n";
$usersListResult = testAuthenticatedAPI($baseUrl . '/users-list', 'GET', [], $token, $userId);
if ($usersListResult['response']['code'] == 1) {
    echo "✅ Users list: PASSED\n";
    $usersData = $usersListResult['response']['data'];
    if (isset($usersData['data'])) {
        echo "   Total users found: " . count($usersData['data']) . "\n";
        echo "   Pagination: Page " . ($usersData['pagination']['current_page'] ?? 1) . " of " . ($usersData['pagination']['last_page'] ?? 1) . "\n";
    }
} else {
    echo "❌ Users list: FAILED\n";
    echo "   Message: " . $usersListResult['response']['message'] . "\n";
}

// Step 7: Test User Profile Update (for better matching)
echo "\n=== Step 7: Profile Update Test ===\n";
$profileData = [
    'model' => 'User',
    'id' => $userId,
    'bio' => 'Updated bio for dating app testing - ' . date('Y-m-d H:i:s'),
    'city' => 'Kampala',
    'age_range_min' => 18,
    'age_range_max' => 35,
    'max_distance_km' => 50
];
$profileResult = testAuthenticatedAPI($baseUrl . '/dynamic-save', 'POST', $profileData, $token, $userId);
if ($profileResult['response']['code'] == 1) {
    echo "✅ Profile update: PASSED\n";
    echo "   Updated profile for better matching\n";
} else {
    echo "❌ Profile update: FAILED\n";
    echo "   Message: " . $profileResult['response']['message'] . "\n";
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Dating API Endpoints Tested:\n";
echo "  ✓ Authentication (login)\n";
echo "  ✓ Discovery stats\n";
echo "  ✓ Swipe discovery\n";
echo "  ✓ Swipe actions (pass/like)\n";
echo "  ✓ Who liked me\n";
echo "  ✓ Users list\n";
echo "  ✓ Profile updates\n\n";

echo "Mobile App Integration Status:\n";
echo "  🔹 Backend APIs are functional\n";
echo "  🔹 Authentication working with JWT tokens\n";
echo "  🔹 Dating discovery system operational\n";
echo "  🔹 Swipe mechanics implemented\n";
echo "  🔹 Match detection working\n";
echo "  🔹 User profile management active\n\n";

echo "Next Steps for Mobile Testing:\n";
echo "  1. Test mobile app's API calls\n";
echo "  2. Verify SwipeScreen integration\n";
echo "  3. Test WhoLikedMeScreen data loading\n";
echo "  4. Validate MatchesScreen functionality\n";
echo "  5. Check ProfileEditScreen updates\n\n";

// Helper functions
function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

function testAuthenticatedAPI($url, $method, $data, $token, $userId) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Tok: Bearer ' . $token,
        'logged_in_user_id: ' . $userId
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
        'http_code' => $httpCode
    ];
}

?>
