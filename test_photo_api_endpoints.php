<?php

echo "=== TESTING PHOTO MANAGEMENT API ENDPOINTS ===\n\n";

// Test reorder endpoint with user ID header (fallback auth method)
$testPhotos = [
    'uploads/test1.jpg',
    'uploads/test2.jpg',
    'uploads/test3.jpg'
];

echo "=== Test 1: Reorder profile photos ===\n";
$reorderData = [
    'photo_order' => array_reverse($testPhotos)
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/reorder-profile-photos');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reorderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'logged_in_user_id: 6127'  // Using fallback auth method
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Reorder API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

echo "=== Test 2: Delete profile photo ===\n";
$deleteData = [
    'photo_url' => $testPhotos[0]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/delete-profile-photo');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($deleteData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'logged_in_user_id: 6127'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Delete API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

echo "=== Test 3: Upload profile photo (multipart form) ===\n";

// Create a test image file for upload testing
$testImagePath = '/tmp/test_upload.jpg';
file_put_contents($testImagePath, 'test image content');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/upload-profile-photos');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'photo' => new CURLFile($testImagePath, 'image/jpeg', 'test.jpg')
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'logged_in_user_id: 6127'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Upload API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

// Clean up test file
unlink($testImagePath);

echo "✅ Photo management API endpoints testing completed!\n";
