<?php
/**
 * Quick authentication test to get working credentials
 */

echo "=== AUTHENTICATION TEST ===\n\n";

$baseUrl = 'http://localhost:8888/lovebirds-api';

// Test with the existing successful format from our previous test
$testData = [
    'receiver_id' => '6122',
    'content' => 'Test message from enhanced ChatScreen',
    'message_type' => 'text',
    'logged_in_user_id' => '6121'
];

$headers = [
    'Content-Type: application/json',
    'logged_in_user_id: 6121'
];

echo "🔄 Testing with user ID 6121 (Sarah Johnson)\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/api/chat-send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && $result['code'] == 1) {
        echo "✅ SUCCESS: Enhanced chat system is working!\n";
        echo "Message ID: {$result['data']['id']}\n";
        echo "Chat Head ID: {$result['data']['chat_head_id']}\n";
        echo "🎉 MIGRATION SUCCESSFUL!\n";
    } else {
        echo "❌ Failed: {$result['message']}\n";
    }
} else {
    echo "❌ HTTP Error: $httpCode\n";
    echo "Error: $error\n";
}

echo "\n=== Test Complete ===\n";
?>
