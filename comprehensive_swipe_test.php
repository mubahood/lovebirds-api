<?php
echo "🧪 COMPREHENSIVE PHOTO LIKES/DISLIKES SYSTEM TEST\n";
echo "=================================================\n\n";

// Test configuration
$base_url = "http://localhost:8888/lovebirds-api/api";
$test_users = [
    ['email' => 'sarah.test@example.com', 'password' => 'testpass123'],
    ['email' => 'michael.test@example.com', 'password' => 'testpass123'],
    ['email' => 'emma.test@example.com', 'password' => 'testpass123'],
    ['email' => 'david.test@example.com', 'password' => 'testpass123']
];

// Test results storage
$test_results = [];
$tokens = [];

// Function to make API requests
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    // Add token as query parameter if provided
    if ($token) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url .= $separator . 'token=' . urlencode($token);
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = ['Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
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
        'code' => $httpCode,
        'response' => json_decode($response, true) ?: $response
    ];
}

// Test 1: Login all test users
echo "🔐 TEST 1: LOGIN ALL TEST USERS\n";
echo "===============================\n";
foreach ($test_users as $index => $user) {
    $response = makeRequest("$base_url/auth/login", 'POST', $user);
    
    if ($response['code'] === 200 && isset($response['response']['data']['user']['token'])) {
        $tokens[$index] = $response['response']['data']['user']['token'];
        $user_id = $response['response']['data']['user']['id'];
        echo "✅ User $index (ID: $user_id) logged in successfully\n";
        $test_results["login_user_$index"] = true;
    } else {
        echo "❌ User $index login failed: " . json_encode($response) . "\n";
        $test_results["login_user_$index"] = false;
    }
}

if (count($tokens) < 2) {
    echo "❌ Not enough users logged in. Cannot proceed with tests.\n";
    exit(1);
}

echo "\n🎯 TEST 2: SWIPE ACTIONS TEST\n";
echo "=============================\n";

// Test swipe like action
$swipe_data = [
    'target_user_id' => 6122, // User 1 swipes on User 2
    'action' => 'like'
];

$response = makeRequest("$base_url/swipe-action", 'POST', $swipe_data, $tokens[0]);
echo "👍 Like Action Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['swipe_like'] = $response['code'] === 200;

// Test swipe dislike action
$swipe_data['action'] = 'dislike';
$swipe_data['target_user_id'] = 6123; // User 1 dislikes User 3

$response = makeRequest("$base_url/swipe-action", 'POST', $swipe_data, $tokens[0]);
echo "👎 Dislike Action Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['swipe_dislike'] = $response['code'] === 200;

// Test super like action
$swipe_data['action'] = 'super_like';
$swipe_data['target_user_id'] = 6124; // User 1 super likes User 4

$response = makeRequest("$base_url/swipe-action", 'POST', $swipe_data, $tokens[0]);
echo "💖 Super Like Action Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['swipe_super_like'] = $response['code'] === 200;

echo "🔄 TEST 3: MUTUAL MATCH TEST\n";
echo "============================\n";

// User 2 likes User 1 back to create a match
$mutual_swipe = [
    'target_user_id' => 6121, // User 2 likes User 1
    'action' => 'like'
];

$response = makeRequest("$base_url/swipe-action", 'POST', $mutual_swipe, $tokens[1]);
echo "💕 Mutual Like Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['mutual_match'] = $response['code'] === 200;

echo "📊 TEST 4: STATISTICS AND DATA RETRIEVAL\n";
echo "========================================\n";

// Test who liked me endpoint
$response = makeRequest("$base_url/who-liked-me", 'GET', null, $tokens[0]);
echo "❤️ Who Liked Me Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['who_liked_me'] = $response['code'] === 200;

// Test my matches endpoint
$response = makeRequest("$base_url/my-matches", 'GET', null, $tokens[0]);
echo "🤝 My Matches Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['my_matches'] = $response['code'] === 200;

// Test swipe statistics
$response = makeRequest("$base_url/swipe-stats", 'GET', null, $tokens[0]);
echo "📈 Swipe Statistics Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['swipe_stats'] = $response['code'] === 200;

// Test recent activity
$response = makeRequest("$base_url/recent-activity", 'GET', null, $tokens[0]);
echo "📋 Recent Activity Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['recent_activity'] = $response['code'] === 200;

echo "↩️ TEST 5: UNDO FUNCTIONALITY\n";
echo "=============================\n";

// Test undo last swipe
$response = makeRequest("$base_url/undo-swipe", 'POST', [], $tokens[0]);
echo "🔄 Undo Swipe Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['undo_swipe'] = $response['code'] === 200;

echo "🚫 TEST 6: ERROR HANDLING\n";
echo "=========================\n";

// Test invalid action
$invalid_swipe = [
    'target_user_id' => 6122,
    'action' => 'invalid_action'
];

$response = makeRequest("$base_url/swipe-action", 'POST', $invalid_swipe, $tokens[0]);
echo "❌ Invalid Action Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['error_handling'] = $response['code'] !== 200; // Should fail

// Test unauthorized access
$response = makeRequest("$base_url/swipe-stats", 'GET', null, null);
echo "🔒 Unauthorized Access Response (Code: {$response['code']}):\n";
echo json_encode($response['response'], JSON_PRETTY_PRINT) . "\n\n";
$test_results['unauthorized_access'] = $response['code'] === 401 || $response['code'] === 403;

echo "📊 FINAL TEST RESULTS SUMMARY\n";
echo "=============================\n";

$total_tests = count($test_results);
$passed_tests = array_sum($test_results);
$failed_tests = $total_tests - $passed_tests;

foreach ($test_results as $test_name => $result) {
    $status = $result ? "✅ PASS" : "❌ FAIL";
    echo "$status - $test_name\n";
}

echo "\n📈 OVERALL RESULTS:\n";
echo "==================\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: $failed_tests\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n";

if ($passed_tests === $total_tests) {
    echo "\n🎉 ALL TESTS PASSED! Photo Likes/Dislikes system is working perfectly!\n";
} elseif ($passed_tests > ($total_tests / 2)) {
    echo "\n✅ Most tests passed. System is largely functional with minor issues.\n";
} else {
    echo "\n⚠️ Multiple test failures detected. System needs attention.\n";
}

echo "\n🏁 Testing Complete!\n";
?>
