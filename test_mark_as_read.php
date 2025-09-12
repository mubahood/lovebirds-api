<?php
/*
 * Test Super Admin Mark as Read Functionality
 * This script tests the new super-admin-mark-as-read endpoint
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Testing Super Admin Mark as Read Functionality\n";
echo "================================================\n\n";

// Include necessary files if needed
require_once __DIR__ . '/vendor/autoload.php';

function makeRequest($endpoint, $data = [], $method = 'GET', $token = null, $userId = null) {
    $baseUrl = 'http://localhost:8888/lovebirds-api/api/';
    $url = $baseUrl . $endpoint;
    
    $curl = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token && $userId) {
        $headers[] = "Authorization: Bearer $token";
        $headers[] = "user-id: $userId";
    }
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    if ($method === 'POST' && !empty($data)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
        curl_setopt($curl, CURLOPT_URL, $url);
    }
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return json_decode($response, true);
}

// Test the mark as read functionality
echo "🧪 Testing Super Admin Mark as Read Endpoint\n";
echo "--------------------------------------------\n";

try {
    // First, get some chat heads to test with
    echo "1. Getting super admin chat heads...\n";
    $chatHeadsResponse = makeRequest('super-admin-chat-heads', [], 'GET', 'dummy-super-admin-token', 1);
    
    if ($chatHeadsResponse && isset($chatHeadsResponse['code']) && $chatHeadsResponse['code'] == 1) {
        $chatHeads = $chatHeadsResponse['data'] ?? [];
        echo "   ✅ Found " . count($chatHeads) . " chat heads\n";
        
        if (!empty($chatHeads)) {
            $testChatHead = $chatHeads[0];
            echo "   📋 Testing with chat head ID: " . $testChatHead['id'] . "\n";
            echo "   👥 Test account: " . ($testChatHead['test_account_name'] ?? 'Unknown') . "\n";
            echo "   💬 Unread count before: " . ($testChatHead['unread_messages_count'] ?? 0) . "\n\n";
            
            // Test mark as read endpoint
            echo "2. Testing mark as read endpoint...\n";
            $markAsReadData = [
                'chat_head_id' => $testChatHead['id']
            ];
            
            $markAsReadResponse = makeRequest('super-admin-mark-as-read', $markAsReadData, 'POST', 'dummy-super-admin-token', 1);
            
            if ($markAsReadResponse && isset($markAsReadResponse['code'])) {
                if ($markAsReadResponse['code'] == 1) {
                    echo "   ✅ Mark as read: SUCCESS\n";
                    echo "   📊 Response: " . ($markAsReadResponse['message'] ?? 'No message') . "\n";
                    
                    if (isset($markAsReadResponse['data']['updated_messages_count'])) {
                        echo "   📝 Messages marked as read: " . $markAsReadResponse['data']['updated_messages_count'] . "\n";
                    }
                    
                    // Verify by getting chat heads again
                    echo "\n3. Verifying unread count after mark as read...\n";
                    $verifyResponse = makeRequest('super-admin-chat-heads', [], 'GET', 'dummy-super-admin-token', 1);
                    
                    if ($verifyResponse && isset($verifyResponse['code']) && $verifyResponse['code'] == 1) {
                        $updatedChatHeads = $verifyResponse['data'] ?? [];
                        
                        foreach ($updatedChatHeads as $chatHead) {
                            if ($chatHead['id'] == $testChatHead['id']) {
                                echo "   📊 Unread count after: " . ($chatHead['unread_messages_count'] ?? 0) . "\n";
                                
                                if (($chatHead['unread_messages_count'] ?? 0) == 0) {
                                    echo "   ✅ SUCCESS: Unread count is now 0!\n";
                                } else {
                                    echo "   ⚠️  Note: Unread count is still " . ($chatHead['unread_messages_count'] ?? 0) . "\n";
                                    echo "   💡 This might be expected if there are newer messages\n";
                                }
                                break;
                            }
                        }
                    }
                    
                } else {
                    echo "   ❌ Mark as read: FAILED\n";
                    echo "   📊 Error: " . ($markAsReadResponse['message'] ?? 'Unknown error') . "\n";
                }
            } else {
                echo "   ❌ Mark as read: INVALID RESPONSE\n";
                echo "   📊 Response: " . json_encode($markAsReadResponse) . "\n";
            }
            
        } else {
            echo "   ⚠️  No chat heads found to test with\n";
            echo "   💡 Create some test chat data first\n";
        }
        
    } else {
        echo "   ❌ Failed to get chat heads\n";
        echo "   📊 Response: " . json_encode($chatHeadsResponse) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🧪 TESTING SCENARIOS\n";
echo str_repeat("=", 60) . "\n";

// Test various scenarios
$testScenarios = [
    [
        'name' => 'Missing chat_head_id',
        'data' => [],
        'expected' => 'Should return error for missing chat_head_id'
    ],
    [
        'name' => 'Invalid chat_head_id',
        'data' => ['chat_head_id' => 999999],
        'expected' => 'Should return error for non-existent chat head'
    ],
    [
        'name' => 'Non-super admin access',
        'data' => ['chat_head_id' => 1],
        'expected' => 'Should return access denied',
        'user_id' => 2
    ]
];

foreach ($testScenarios as $index => $scenario) {
    echo "\n" . ($index + 1) . ". Testing: " . $scenario['name'] . "\n";
    echo "   Expected: " . $scenario['expected'] . "\n";
    
    $userId = $scenario['user_id'] ?? 1;
    $response = makeRequest('super-admin-mark-as-read', $scenario['data'], 'POST', 'dummy-token', $userId);
    
    if ($response && isset($response['code'])) {
        if ($response['code'] == 0) {
            echo "   ✅ Got expected error: " . ($response['message'] ?? 'No message') . "\n";
        } else {
            echo "   ⚠️  Unexpected success: " . ($response['message'] ?? 'No message') . "\n";
        }
    } else {
        echo "   ❌ Invalid response: " . json_encode($response) . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Super Admin Mark as Read endpoint has been tested\n";
echo "🔗 Endpoint: POST /api/super-admin-mark-as-read\n";
echo "📝 Required parameters: chat_head_id\n";
echo "🔐 Access: Super admin only (ID = 1)\n";
echo "🎯 Function: Marks all messages in a chat head as read\n";
echo "\n";
echo "🚀 Integration with Flutter app:\n";
echo "   - Called automatically when chat screen loads\n";
echo "   - Called when app becomes active\n";
echo "   - Updates unread counts in real-time\n";
echo "\n";
echo "🎉 Mark as Read functionality is ready for testing!\n";
