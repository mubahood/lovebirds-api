<?php

echo "=== TESTING PHOTO MANAGEMENT ENDPOINTS WITH REAL AUTH ===\n\n";

// Step 1: Login to get a real JWT token
$loginData = [
    'email' => 'test@example.com',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response (HTTP $httpCode):\n";
echo $response . "\n\n";

$loginResponse = json_decode($response, true);

if (!isset($loginResponse['data']['token'])) {
    echo "❌ Could not get login token. Trying alternative approach...\n\n";
    
    // Alternative: Use logged_in_user_id header
    echo "=== TESTING WITH logged_in_user_id HEADER ===\n";
    
    // Test reorder endpoint with user ID header
    $testPhotos = [
        'uploads/photo1.jpg',
        'uploads/photo2.jpg', 
        'uploads/photo3.jpg'
    ];
    
    $reorderData = [
        'photo_order' => array_reverse($testPhotos) // Reverse the order
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/reorder-profile-photos');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reorderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'logged_in_user_id: 6127'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Reorder API Response with user ID header (HTTP $httpCode):\n";
    echo $response . "\n\n";
    
    exit;
}

$token = $loginResponse['data']['token'];
echo "✅ Got JWT token: " . substr($token, 0, 50) . "...\n\n";

// Step 2: Test the photo management endpoints
$testPhotos = [
    'uploads/photo1.jpg',
    'uploads/photo2.jpg', 
    'uploads/photo3.jpg'
];

// Test reorder endpoint
echo "=== Test: Reorder profile photos ===\n";
$reorderData = [
    'photo_order' => array_reverse($testPhotos)
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/reorder-profile-photos');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reorderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Reorder API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

// Test delete endpoint
echo "=== Test: Delete profile photo ===\n";
$deleteData = [
    'photo_url' => $testPhotos[0]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/delete-profile-photo');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($deleteData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Delete API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

echo "✅ Photo management endpoints testing completed!\n";
