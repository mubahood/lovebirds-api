<?php
/**
 * Direct API test for chat-send endpoint
 * This tests the actual HTTP endpoint without Laravel bootstrap issues
 */

echo "=== Testing Chat Send API Endpoint Directly ===\n\n";

// Configuration
$baseUrl = 'http://localhost:8888/lovebirds-api'; // Updated to MAMP default port
$endpoint = '/api/chat-send';
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2xvdmViaXJkcy1hcGkvYXBpL2xvZ2luIiwiaWF0IjoxNzM4MjQxNDQ0LCJleHAiOjE3MzgyNDUwNDQsIm5iZiI6MTczODI0MTQ0NCwianRpIjoibXJ3R0V4RWJoTGF2ZVFSViIsInN1YiI6IjU5NjQiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.dZHHMWN6utkJKdHUE0jYX9VPYgAHN5tYLcnD-w4Yfgw';

// Test data (from the error log)
$testData = [
    'receiver_id' => '1000',
    'content' => 'test message via API',
    'message_type' => 'text',
    'logged_in_user_id' => '5964'
];

echo "Test Configuration:\n";
echo "- Base URL: $baseUrl\n";
echo "- Endpoint: $endpoint\n";
echo "- Sender ID: 5964\n";
echo "- Receiver ID: 1000\n";
echo "- Message: test message via API\n";
echo "- Type: text\n\n";

// Prepare the cURL request
$url = $baseUrl . $endpoint;
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token,
    'logged_in_user_id: 5964'
];

$postData = json_encode($testData);

echo "Making API request...\n";
echo "URL: $url\n";
echo "Headers: " . implode(', ', $headers) . "\n";
echo "Data: $postData\n\n";

// Initialize cURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true,
    CURLOPT_STDERR => STDOUT
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "--- Response ---\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
    exit;
}

if ($response === false) {
    echo "❌ Failed to get response\n";
    exit;
}

echo "Response Body:\n";
echo $response . "\n\n";

// Parse JSON response
$responseData = json_decode($response, true);

if ($responseData === null) {
    echo "❌ Invalid JSON response\n";
    exit;
}

// Analyze the response
if (isset($responseData['code'])) {
    if ($responseData['code'] == 1) {
        echo "✅ SUCCESS: Message sent successfully!\n";
        echo "Message: " . ($responseData['message'] ?? 'No message') . "\n";
        
        if (isset($responseData['data'])) {
            echo "Response data:\n";
            echo json_encode($responseData['data'], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ FAILED: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        if (isset($responseData['error'])) {
            echo "Error details: " . $responseData['error'] . "\n";
        }
    }
} else {
    echo "❌ Unexpected response format\n";
}

echo "\n=== Test Complete ===\n";
?>
