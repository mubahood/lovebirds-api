<?php

require_once 'vendor/autoload.php';

// Database connection
$servername = "127.0.0.1"; // Use IP instead of localhost
$username = "root";
$password = "root"; 
$database = "katogo";
$port = 8889;

try {
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully\n";
} catch(PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

echo "\n=== TESTING PHOTO MANAGEMENT ENDPOINTS ===\n\n";

// Get a test user
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email LIKE 'test%' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ No test user found in database\n";
    exit;
}

echo "✅ Using test user: {$user['email']} (ID: {$user['id']})\n";

// Create a JWT token for testing (simplified)
$headers = ['typ' => 'JWT', 'alg' => 'HS256'];
$payload = [
    'iss' => 'lovebirds-api',
    'aud' => 'lovebirds-mobile',
    'iat' => time(),
    'exp' => time() + (60 * 60 * 24), // 24 hours
    'user_id' => $user['id']
];

$base64Headers = base64_encode(json_encode($headers));
$base64Payload = base64_encode(json_encode($payload));
$signature = hash_hmac('sha256', $base64Headers . '.' . $base64Payload, 'your-secret-key', true);
$base64Signature = base64_encode($signature);
$jwt = $base64Headers . '.' . $base64Payload . '.' . $base64Signature;

echo "✅ JWT token created for testing\n\n";

// Test 1: Get current profile photos
echo "=== Test 1: Check current profile photos ===\n";
echo "Current profile_photos: " . ($user['profile_photos'] ?: 'null') . "\n\n";

// Test 2: Test reorder endpoint with mock data
echo "=== Test 2: Test reorder profile photos endpoint ===\n";

// First, set some test photos if none exist
if (empty($user['profile_photos'])) {
    $testPhotos = [
        'uploads/photo1.jpg',
        'uploads/photo2.jpg', 
        'uploads/photo3.jpg'
    ];
    
    $stmt = $pdo->prepare("UPDATE admin_users SET profile_photos = ? WHERE id = ?");
    $stmt->execute([json_encode($testPhotos), $user['id']]);
    echo "✅ Added test photos to user profile\n";
} else {
    $testPhotos = json_decode($user['profile_photos'], true);
    echo "✅ Using existing photos: " . implode(', ', $testPhotos) . "\n";
}

// Test reorder API call
$reorderData = [
    'photo_order' => array_reverse($testPhotos) // Reverse the order
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/reorder-profile-photos');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reorderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $jwt
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Reorder API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

// Test 3: Test delete photo endpoint
echo "=== Test 3: Test delete profile photo endpoint ===\n";

if (!empty($testPhotos)) {
    $photoToDelete = $testPhotos[0]; // Delete first photo
    
    $deleteData = [
        'photo_url' => $photoToDelete
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/delete-profile-photo');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($deleteData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $jwt
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Delete API Response (HTTP $httpCode):\n";
    echo $response . "\n\n";
} else {
    echo "⚠️ No photos to delete\n\n";
}

// Test 4: Check final state
echo "=== Test 4: Final profile photos state ===\n";
$stmt = $pdo->prepare("SELECT profile_photos FROM admin_users WHERE id = ?");
$stmt->execute([$user['id']]);
$finalUser = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Final profile_photos: " . ($finalUser['profile_photos'] ?: 'null') . "\n";

if ($finalUser['profile_photos']) {
    $finalPhotos = json_decode($finalUser['profile_photos'], true);
    echo "Parsed photos: " . implode(', ', $finalPhotos) . "\n";
    echo "Total photos: " . count($finalPhotos) . "\n";
}

echo "\n✅ Photo management endpoints testing completed!\n";
