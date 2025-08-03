<?php

echo "=== TESTING PHOTO MANAGEMENT WITH TEST ENDPOINT ===\n\n";

// Test the test endpoint
$testPhotos = [
    'uploads/test3.jpg',
    'uploads/test1.jpg', 
    'uploads/test2.jpg'
];

$reorderData = [
    'photo_order' => $testPhotos
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/test-photo-reorder');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reorderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Test Reorder API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

echo "✅ Photo management test endpoint completed!\n";
