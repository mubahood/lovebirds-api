<?php

// Test script for cart submission endpoint
echo "Testing Cart Submission Endpoint\n";
echo "================================\n\n";

// Test data
$testData = [
    'items' => [
        [
            'product_id' => 1,
            'name' => 'Test Product 1',
            'price' => 29.99,
            'quantity' => 2
        ],
        [
            'product_id' => 2,
            'name' => 'Test Product 2', 
            'price' => 19.99,
            'quantity' => 1
        ]
    ],
    'subtotal' => 79.97,
    'shipping_cost' => 9.99,
    'tax_amount' => 7.20,
    'total_amount' => 97.16,
    'shipping_address' => [
        'street' => '123 Test Street',
        'city' => 'Vancouver',
        'province' => 'BC',
        'postal_code' => 'V6B 1A1',
        'country' => 'Canada'
    ],
    'payment_method' => 'credit_card'
];

// API endpoint URL
$url = 'http://10.0.2.2:8888/lovebirds-api/api/cart/submit-order';

// Test 1: Test without authentication (should fail)
echo "Test 1: Testing without authentication...\n";
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($testData));
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

// Test 2: Check if route exists (with form data)
echo "Test 2: Testing route availability with form data...\n";
$formData = http_build_query($testData);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $formData);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

echo "Test completed!\n";
echo "Expected results:\n";
echo "- HTTP 401/403 for unauthorized request (Test 1)\n";
echo "- HTTP 401/403 or valid response for Test 2 (not 404)\n";
echo "- If you see 404, the route is not properly registered\n";

?>
