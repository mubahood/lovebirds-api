<?php

/**
 * Simple JWT Test to Debug Authentication
 */

$loginData = [
    'email' => 'sarah.test@example.com',
    'password' => 'password123'
];

// Login
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "🔐 LOGIN TEST\n";
echo "=============\n";
echo "HTTP Code: $httpCode\n";

$result = json_decode($response, true);
if ($result['code'] == 1) {
    $token = $result['data']['user']['token'];
    echo "✅ Login successful! Token obtained.\n";
    echo "Token: " . substr($token, 0, 50) . "...\n\n";
    
    // Test the /me endpoint
    echo "🧪 TESTING /me ENDPOINT\n";
    echo "=======================\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Tok: Bearer ' . $token
    ]);
    
    $meResponse = curl_exec($ch);
    $meHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $meHttpCode\n";
    $meResult = json_decode($meResponse, true);
    
    if ($meResult && $meResult['code'] == 1) {
        echo "✅ /me endpoint working! User: " . $meResult['data']['name'] . "\n";
    } else {
        echo "❌ /me endpoint failed\n";
        echo "Response: $meResponse\n";
    }
    
    // Test swipe-discovery endpoint
    echo "\n🔄 TESTING SWIPE-DISCOVERY ENDPOINT\n";
    echo "===================================\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/swipe-discovery');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Tok: Bearer ' . $token
    ]);
    
    $swipeResponse = curl_exec($ch);
    $swipeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $swipeHttpCode\n";
    $swipeResult = json_decode($swipeResponse, true);
    
    if ($swipeResult && $swipeResult['code'] == 1) {
        echo "✅ Swipe discovery working!\n";
        if (isset($swipeResult['data']['user'])) {
            echo "Found user: " . $swipeResult['data']['user']['name'] . "\n";
        }
    } else {
        echo "❌ Swipe discovery failed\n";
        echo "Response: $swipeResponse\n";
    }
    
} else {
    echo "❌ Login failed: " . $result['message'] . "\n";
}

?>
