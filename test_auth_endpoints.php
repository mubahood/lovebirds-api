<?php

/**
 * Simple Authentication Test
 * Test the login endpoint with our test users
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base configuration
$API_BASE = 'http://localhost:8888/lovebirds-api/api';
$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

function makeRequest($url, $data = null, $headers = [], $method = 'POST') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => json_decode($response, true), 'http_code' => $httpCode];
}

echo "🧪 TESTING AUTHENTICATION ENDPOINTS\n";
echo "===================================\n";

$testCredentials = [
    'email' => 'sarah.test@example.com',
    'password' => '123456'
];

echo "🔐 Testing /auth/login endpoint...\n";
$result = makeRequest("$API_BASE/auth/login", $testCredentials, $headers);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

echo "🔐 Testing /api/User endpoint (old way)...\n";
$result = makeRequest("$API_BASE/api/User", $testCredentials, $headers);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

echo "🔐 Testing /users endpoint (if exists)...\n";
$result = makeRequest("$API_BASE/users", $testCredentials, $headers);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

?>
