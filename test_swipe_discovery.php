<?php

// Test script for swipe discovery endpoint
echo "Testing Swipe Discovery Endpoint\n";
echo "================================\n\n";

// API endpoint URL
$url = 'http://localhost:8888/lovebirds-api/api/swipe-discovery';

echo "Testing swipe discovery endpoint...\n";
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode == 401) {
    echo "✅ Good! Endpoint exists and requires authentication\n";
} else if ($httpCode == 200) {
    echo "✅ Good! Endpoint is working\n";
} else if ($httpCode == 404) {
    echo "❌ Endpoint not found\n";
} else {
    echo "⚠️  Unexpected response code\n";
}

echo "\nTesting complete!\n";

?>
