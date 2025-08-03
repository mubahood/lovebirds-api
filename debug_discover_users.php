<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain');
echo "=== API-1 DEBUG: TESTING DATING ENDPOINTS ===\n\n";

$baseUrl = 'http://localhost:8888/lovebirds-api/api';
$userId = 1;

echo "Testing discover-users endpoint...\n";
$discoverUrl = $baseUrl . '/discover-users';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $discoverUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'logged_in_user_id: ' . $userId
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Raw Response: " . $response . "\n";

$result = json_decode($response, true);
if ($result) {
    echo "Parsed JSON successfully\n";
    echo "Code: " . (isset($result['code']) ? $result['code'] : 'NOT SET') . "\n";
    echo "Message: " . (isset($result['message']) ? $result['message'] : 'NOT SET') . "\n";
    if (isset($result['data'])) {
        echo "Data present: " . (is_array($result['data']) ? count($result['data']) : 'YES') . "\n";
    }
} else {
    echo "Failed to parse JSON\n";
}

?>
