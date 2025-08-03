<?php
/**
 * Quick Test for New Enhanced Dating Chat API Endpoints
 * 
 * Tests the new endpoints we just added:
 * - chat-typing-indicator
 * - chat-typing-status  
 * - chat-add-reaction
 * - chat-remove-reaction
 * - chat-block-user
 * - chat-unblock-user
 * - chat-media-files
 * - chat-search-messages
 */

echo "🚀 Testing Enhanced Dating Chat API Endpoints\n";
echo "=============================================\n\n";

$baseUrl = 'http://localhost/lovebirds-api/public/api';

function makeRequest($method, $endpoint, $data = [], $token = null) {
    global $baseUrl;
    $url = $baseUrl . $endpoint;
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Network error', 'http_code' => $httpCode];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Invalid JSON', 'raw' => $response, 'http_code' => $httpCode];
    }

    return array_merge($decoded, ['http_code' => $httpCode]);
}

// Test data
$testUser = [
    'email' => 'test.enhanced.chat@lovebirds.com',
    'password' => 'TestPassword123!'
];

echo "🔐 Testing authentication...\n";
$authResponse = makeRequest('POST', '/auth/login', $testUser);

if (isset($authResponse['data']['token'])) {
    $token = $authResponse['data']['token'];
    echo "✅ Authentication successful\n\n";
} else {
    echo "❌ Authentication failed. Creating test user...\n";
    
    $registerData = array_merge($testUser, [
        'name' => 'Enhanced Chat Test User',
        'phone_number' => '+1234567890',
        'date_of_birth' => '1995-01-01',
        'gender' => 'female',
        'interested_in' => 'male'
    ]);
    
    $regResponse = makeRequest('POST', '/auth/register', $registerData);
    echo "Registration response: " . json_encode($regResponse) . "\n";
    
    // Try login again
    $authResponse = makeRequest('POST', '/auth/login', $testUser);
    if (isset($authResponse['data']['token'])) {
        $token = $authResponse['data']['token'];
        echo "✅ Registration and authentication successful\n\n";
    } else {
        echo "❌ Still can't authenticate. Response: " . json_encode($authResponse) . "\n";
        exit(1);
    }
}

// Test endpoints
$endpoints = [
    [
        'name' => 'Typing Indicator (Set)',
        'method' => 'POST',
        'endpoint' => '/chat-typing-indicator',
        'data' => ['chat_head_id' => 1, 'is_typing' => true]
    ],
    [
        'name' => 'Typing Status (Get)', 
        'method' => 'GET',
        'endpoint' => '/chat-typing-status',
        'data' => ['chat_head_id' => 1]
    ],
    [
        'name' => 'Add Message Reaction',
        'method' => 'POST', 
        'endpoint' => '/chat-add-reaction',
        'data' => ['message_id' => 1, 'emoji' => '❤️']
    ],
    [
        'name' => 'Remove Message Reaction',
        'method' => 'POST',
        'endpoint' => '/chat-remove-reaction', 
        'data' => ['message_id' => 1]
    ],
    [
        'name' => 'Block User in Chat',
        'method' => 'POST',
        'endpoint' => '/chat-block-user',
        'data' => ['chat_head_id' => 1, 'blocked_user_id' => 2, 'reason' => 'Test blocking']
    ],
    [
        'name' => 'Get Chat Media Files',
        'method' => 'GET',
        'endpoint' => '/chat-media-files',
        'data' => ['chat_head_id' => 1, 'media_type' => 'all']
    ],
    [
        'name' => 'Search Chat Messages',
        'method' => 'GET', 
        'endpoint' => '/chat-search-messages',
        'data' => ['chat_head_id' => 1, 'search_term' => 'hello']
    ],
    [
        'name' => 'Unblock User in Chat',
        'method' => 'POST',
        'endpoint' => '/chat-unblock-user',
        'data' => ['chat_head_id' => 1, 'blocked_user_id' => 2]
    ]
];

foreach ($endpoints as $test) {
    echo "🧪 Testing: {$test['name']}\n";
    $response = makeRequest($test['method'], $test['endpoint'], $test['data'], $token);
    
    echo "   HTTP Code: {$response['http_code']}\n";
    
    if ($response['http_code'] === 200) {
        echo "   ✅ Endpoint accessible\n";
        if (isset($response['success'])) {
            echo "   📊 Success: " . ($response['success'] ? 'true' : 'false') . "\n";
            if (!$response['success'] && isset($response['message'])) {
                echo "   📝 Message: {$response['message']}\n";
            }
        }
    } else {
        echo "   ❌ HTTP Error\n";
        if (isset($response['message'])) {
            echo "   📝 Error: {$response['message']}\n";
        }
    }
    echo "\n";
}

echo "🏁 Endpoint testing completed!\n";
echo "\nℹ️  Note: Some endpoints may return logical errors (like 'Chat head not found')\n";
echo "   because we're testing with dummy data. The important thing is that the\n";
echo "   endpoints are accessible (HTTP 200) and returning proper API responses.\n";
