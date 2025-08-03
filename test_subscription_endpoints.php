<?php
/**
 * Test script for Lovebirds Subscription Endpoints
 * Tests all the new subscription functionality
 */

$base_url = "http://localhost:8888/lovebirds-api/api";

// Test data
$test_user_id = 1; // Test user
$regular_user_id = 2; // Regular user for testing

echo "=== LOVEBIRDS SUBSCRIPTION ENDPOINTS TEST ===\n\n";

// Test 1: Check subscription status for test user
echo "1. Testing subscription status for test user (ID: $test_user_id)...\n";
$url = "$base_url/subscription_status?user_id=$test_user_id";
$response = file_get_contents($url);
echo "Response: $response\n\n";

// Test 2: Create subscription payment for weekly plan
echo "2. Testing subscription payment creation (weekly plan)...\n";
$data = json_encode([
    'user_id' => $regular_user_id,
    'plan' => 'weekly'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $data
    ]
]);

$url = "$base_url/create_subscription_payment";
$response = file_get_contents($url, false, $context);
echo "Response: $response\n\n";

// Test 3: Test user activation
echo "3. Testing test user subscription activation...\n";
$data = json_encode([
    'user_id' => $test_user_id,
    'plan' => 'monthly'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $data
    ]
]);

$url = "$base_url/test_user_activate_subscription";
$response = file_get_contents($url, false, $context);
echo "Response: $response\n\n";

// Test 4: Check subscription status again for test user
echo "4. Re-checking subscription status for test user after activation...\n";
$url = "$base_url/subscription_status?user_id=$test_user_id";
$response = file_get_contents($url);
echo "Response: $response\n\n";

echo "=== TEST COMPLETE ===\n";
echo "NOTE: For payment verification testing, you'll need actual Stripe payment IDs\n";
echo "The create_subscription_payment endpoint should return a payment URL for testing\n";
?>
