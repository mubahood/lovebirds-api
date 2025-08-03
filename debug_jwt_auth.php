<?php
echo "🔍 DEBUG SWIPE ACTION AUTHENTICATION\n";
echo "=====================================\n";

// Test user login first
$login_url = "http://localhost:8888/lovebirds-api/api/auth/login";
$login_data = [
    'email' => 'sarah.test@example.com',
    'password' => 'testpass123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $login_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$login_response = curl_exec($ch);
$login_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "🔐 Login Response (Code: $login_code):\n";
$login_data = json_decode($login_response, true);
echo json_encode($login_data, JSON_PRETTY_PRINT) . "\n\n";

if ($login_code === 200 && isset($login_data['data']['user']['token'])) {
    $token = $login_data['data']['user']['token'];
    echo "🎯 Extracted Token: " . substr($token, 0, 50) . "...\n\n";
    
    // Test swipe action with different authorization header formats
    $swipe_url = "http://localhost:8888/lovebirds-api/api/swipe-action";
    $swipe_data = [
        'target_user_id' => 6122,
        'action' => 'like'
    ];
    
    // Test 1: Bearer format
    echo "🧪 Test 1: Bearer Authorization Header\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $swipe_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($swipe_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Code: $code\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
    
    // Test 2: X-Authorization format (sometimes used in Laravel APIs)
    echo "🧪 Test 2: X-Authorization Header\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $swipe_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($swipe_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Authorization: ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Code: $code\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
    
    // Test 3: Token parameter (check if supported)
    echo "🧪 Test 3: Token as Query Parameter\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $swipe_url . "?token=" . urlencode($token));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($swipe_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Code: $code\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
    
} else {
    echo "❌ Failed to get token from login\n";
}

echo "🏁 Debug Complete!\n";
?>
