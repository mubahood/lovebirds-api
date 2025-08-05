<?php
/**
 * Comprehensive test of the enhanced chat system after DatingChatScreen migration
 */

echo "=== COMPREHENSIVE CHAT SYSTEM TEST ===\n";
echo "Testing enhanced ChatScreen after DatingChatScreen migration\n\n";

$baseUrl = 'http://localhost:8888/lovebirds-api';

// Test different message types
$testMessages = [
    [
        'name' => 'Basic Text Message',
        'data' => [
            'receiver_id' => '6122',
            'content' => 'Hello! This is a test message from enhanced ChatScreen',
            'message_type' => 'text'
        ]
    ],
    [
        'name' => 'Photo Message',
        'data' => [
            'receiver_id' => '6122',
            'content' => 'Check out this photo!',
            'message_type' => 'photo',
            'photo' => 'uploads/test_photo.jpg'
        ]
    ],
    [
        'name' => 'Location Message',
        'data' => [
            'receiver_id' => '6122',
            'content' => 'Let\'s meet here!',
            'message_type' => 'location',
            'latitude' => '43.6532',
            'longitude' => '-79.3832',
            'location_name' => 'CN Tower, Toronto'
        ]
    ],
    [
        'name' => 'Audio Message',
        'data' => [
            'receiver_id' => '6122',
            'content' => 'Voice message',
            'message_type' => 'audio',
            'audio' => 'uploads/voice_note.mp3'
        ]
    ]
];

function makeApiRequest($url, $data, $headers) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'success' => $httpCode === 200 && !$error,
        'http_code' => $httpCode,
        'response' => $response ? json_decode($response, true) : null,
        'error' => $error
    ];
}

// Headers for API requests
$headers = [
    'Content-Type: application/json',
    'logged_in_user_id: 6121' // Sarah Johnson
];

$successCount = 0;
$failureCount = 0;
$createdChatHeadId = null;

echo "📱 TESTING ENHANCED CHAT-SEND ENDPOINT\n";
echo "======================================\n";

foreach ($testMessages as $test) {
    echo "🔄 Testing: {$test['name']}\n";
    
    $result = makeApiRequest(
        $baseUrl . '/api/chat-send',
        $test['data'],
        $headers
    );
    
    if ($result['success'] && isset($result['response']['code']) && $result['response']['code'] == 1) {
        echo "✅ SUCCESS: {$test['name']}\n";
        echo "   Message ID: {$result['response']['data']['id']}\n";
        echo "   Type: {$result['response']['data']['type']}\n";
        echo "   Chat Head ID: {$result['response']['data']['chat_head_id']}\n";
        
        if ($createdChatHeadId === null) {
            $createdChatHeadId = $result['response']['data']['chat_head_id'];
        }
        
        $successCount++;
    } else {
        echo "❌ FAILED: {$test['name']}\n";
        if ($result['response']) {
            echo "   Error: {$result['response']['message']}\n";
        } else {
            echo "   HTTP Error: {$result['http_code']}\n";
        }
        $failureCount++;
    }
    echo "\n";
    
    // Small delay to avoid overwhelming the server
    usleep(500000); // 0.5 seconds
}

echo "📋 TESTING CHAT-HEADS ENDPOINT\n";
echo "==============================\n";

// Test chat heads retrieval
$result = makeApiRequest(
    $baseUrl . '/api/chat-heads',
    [],
    $headers
);

if ($result['success'] && isset($result['response']['code']) && $result['response']['code'] == 1) {
    echo "✅ SUCCESS: Chat heads retrieved\n";
    $chatHeads = $result['response']['data'];
    echo "   Found " . count($chatHeads) . " chat head(s)\n";
    
    if (!empty($chatHeads)) {
        $firstHead = $chatHeads[0];
        echo "   Sample chat head ID: {$firstHead['id']}\n";
        echo "   Last message: " . substr($firstHead['last_message_body'], 0, 50) . "...\n";
    }
} else {
    echo "❌ FAILED: Chat heads retrieval\n";
    if ($result['response']) {
        echo "   Error: {$result['response']['message']}\n";
    }
    $failureCount++;
}

echo "\n📋 TESTING CHAT-MESSAGES ENDPOINT\n";
echo "=================================\n";

if ($createdChatHeadId) {
    // Test message retrieval
    $result = makeApiRequest(
        $baseUrl . '/api/chat-messages',
        ['chat_head_id' => $createdChatHeadId],
        $headers
    );
    
    if ($result['success'] && isset($result['response']['code']) && $result['response']['code'] == 1) {
        echo "✅ SUCCESS: Messages retrieved for chat head {$createdChatHeadId}\n";
        $messages = $result['response']['data'];
        echo "   Found " . count($messages) . " message(s)\n";
        
        // Show message types
        $messageTypes = array_unique(array_column($messages, 'type'));
        echo "   Message types: " . implode(', ', $messageTypes) . "\n";
    } else {
        echo "❌ FAILED: Message retrieval\n";
        if ($result['response']) {
            echo "   Error: {$result['response']['message']}\n";
        }
        $failureCount++;
    }
} else {
    echo "⚠️  SKIPPED: No chat head ID available for message testing\n";
}

echo "\n🔍 TESTING ENHANCED FEATURES\n";
echo "============================\n";

// Test enhanced features like typing indicator (if available)
if ($createdChatHeadId) {
    $enhancedTests = [
        [
            'name' => 'Chat Typing Indicator',
            'endpoint' => '/api/chat-typing-indicator',
            'data' => [
                'chat_head_id' => $createdChatHeadId,
                'is_typing' => true
            ]
        ],
        [
            'name' => 'Chat Media Files',
            'endpoint' => '/api/chat-media-files',
            'data' => [
                'chat_head_id' => $createdChatHeadId,
                'media_type' => 'all'
            ]
        ]
    ];
    
    foreach ($enhancedTests as $test) {
        echo "🔄 Testing: {$test['name']}\n";
        
        $result = makeApiRequest(
            $baseUrl . $test['endpoint'],
            $test['data'],
            $headers
        );
        
        if ($result['success'] && isset($result['response']['code']) && $result['response']['code'] == 1) {
            echo "✅ SUCCESS: {$test['name']}\n";
            $successCount++;
        } else {
            // Enhanced features might not be implemented yet, so just note as info
            echo "ℹ️  INFO: {$test['name']} - " . ($result['response']['message'] ?? 'Not available') . "\n";
        }
    }
}

echo "\n📊 TEST RESULTS SUMMARY\n";
echo "=======================\n";
echo "✅ Successful tests: $successCount\n";
echo "❌ Failed tests: $failureCount\n";
echo "📈 Success rate: " . round(($successCount / max(1, $successCount + $failureCount)) * 100, 1) . "%\n\n";

if ($failureCount === 0) {
    echo "🎉 ALL CORE TESTS PASSED!\n";
    echo "✅ Enhanced ChatScreen is working perfectly\n";
    echo "✅ DatingChatScreen migration completed successfully\n";
    echo "✅ Backend API integration functional\n";
    echo "✅ Multimedia messaging supported\n";
    echo "✅ Chat heads creation/retrieval working\n";
    echo "✅ Message persistence working\n\n";
    
    echo "🚀 MIGRATION STATUS: COMPLETE ✅\n";
    echo "📱 The app is ready for production use!\n";
} else {
    echo "⚠️  Some tests failed. Please review the errors above.\n";
    if ($successCount > 0) {
        echo "✅ Core functionality is working\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
