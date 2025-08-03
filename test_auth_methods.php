<?php

echo "=== TESTING PROFILESETUPWIZARDSCREEN WITH DIFFERENT AUTH METHODS ===\n\n";

// Test 1: Try with Authorization header instead
echo "=== Test 1: Using Authorization header format ===\n";

$profileData = [
    'id' => 6127,
    'first_name' => 'UpdatedWizard',
    'last_name' => 'TestUser',
    'bio' => 'Test bio from ProfileSetupWizard'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/api/User');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profileData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer fake-token-for-test',
    'logged_in_user_id: 6127'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response (HTTP $httpCode): $response\n\n";

// Test 2: Try with POST data instead of header
echo "=== Test 2: Using POST data for user ID ===\n";

$profileDataWithUserId = [
    'id' => 6127,
    'logged_in_user_id' => 6127,
    'first_name' => 'UpdatedWizard',
    'last_name' => 'TestUser',
    'bio' => 'Test bio from ProfileSetupWizard'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/api/User');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profileDataWithUserId));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response (HTTP $httpCode): $response\n\n";

// Test 3: Check what endpoints don't require auth
echo "=== Test 3: Testing public endpoints ===\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/api/User');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "GET api/User Response (HTTP $httpCode): $response\n\n";

echo "=== Authentication methods tested ===\n";
