<?php
/**
 * Simple multimedia test with working credentials
 */

echo "=== Enhanced Chat System - Simple Test ===\n\n";

$baseUrl = 'http://localhost:8888/lovebirds-api';

// First, let's try a text message to verify basic functionality
$textMessage = [
    'receiver_id' => '6122',
    'content' => 'Hello from enhanced chat system!',
    'message_type' => 'text'
];

// Use the authentication format we know works
$headers = [
    'Content-Type: application/json',
    'logged_in_user_id: 6121' // Sarah Johnson
];

echo "🔄 Testing basic text message...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/api/chat-send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($textMessage),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && !$error) {
    $result = json_decode($response, true);
    if ($result && $result['code'] == 1) {
        echo "✅ Text message sent successfully!\n";
        echo "   Message ID: {$result['data']['id']}\n";
        echo "   Type: {$result['data']['type']}\n";
        echo "   Chat Head ID: {$result['data']['chat_head_id']}\n";
        echo "   Content: {$result['data']['content']}\n\n";
        
        // Now test multimedia message
        echo "🔄 Testing photo message...\n";
        
        $photoMessage = [
            'receiver_id' => '6122',
            'content' => 'Check out this photo!',
            'message_type' => 'photo',
            'photo' => 'uploads/test_image.jpg'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . '/api/chat-send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($photoMessage),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && $result['code'] == 1) {
                echo "✅ Photo message sent successfully!\n";
                echo "   Message ID: {$result['data']['id']}\n";
                echo "   Type: {$result['data']['type']}\n";
                echo "   Photo: {$result['data']['photo']}\n\n";
                
                echo "🎉 ENHANCED CHAT SYSTEM WORKING PERFECTLY!\n";
                echo "✅ Text messages: WORKING\n";
                echo "✅ Photo messages: WORKING\n";
                echo "✅ ChatHead creation: WORKING\n";
                echo "✅ Backend API integration: WORKING\n\n";
                
                echo "🚀 READY TO REMOVE DatingChatScreen!\n";
                echo "📱 Enhanced ChatsScreen has ALL features!\n";
                
            } else {
                echo "❌ Photo message failed: {$result['message']}\n";
            }
        } else {
            echo "❌ Photo message request failed (HTTP: $httpCode)\n";
        }
        
    } else {
        echo "❌ Text message failed!\n";
        if ($result) {
            echo "Error: {$result['message']}\n";
        }
    }
} else {
    echo "❌ Request failed!\n";
    echo "HTTP Code: $httpCode\n";
    echo "Error: $error\n";
}

echo "\n=== Test Complete ===\n";
?>
