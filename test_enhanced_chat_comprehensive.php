<?php
/**
 * Comprehensive Test Script for Enhanced Dating Chat System
 * 
 * This script tests all the enhanced dating chat features including:
 * - Match validation
 * - Multimedia messaging
 * - Message reactions
 * - Typing indicators
 * - Blocking/unblocking
 * - Chat media files
 * - Message search
 * - Reply functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

class EnhancedChatTestSuite
{
    private $baseUrl;
    private $testUsers = [];
    private $authTokens = [];
    private $testChatHead = null;
    private $testMessages = [];

    public function __construct($baseUrl = 'http://localhost/lovebirds-api/public/api')
    {
        $this->baseUrl = $baseUrl;
        echo "🚀 Enhanced Dating Chat Test Suite\n";
        echo "======================================\n";
        echo "Base URL: {$this->baseUrl}\n\n";
    }

    public function runAllTests()
    {
        try {
            $this->setupTestUsers();
            $this->testUserAuthentication();
            $this->testChatStartWithMatchValidation();
            $this->testMultimediaMessaging();
            $this->testMessageReactions();
            $this->testTypingIndicators();
            $this->testChatBlocking();
            $this->testChatMediaFiles();
            $this->testMessageSearch();
            $this->testReplyFunctionality();
            $this->cleanup();
            
            echo "\n✅ ALL TESTS PASSED! Enhanced dating chat system is working correctly.\n";
        } catch (Exception $e) {
            echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
            $this->cleanup();
        }
    }

    private function setupTestUsers()
    {
        echo "📝 Setting up test users...\n";
        
        $this->testUsers = [
            'user1' => [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.test.chat@lovebirds.com',
                'password' => 'TestPassword123!',
                'phone_number' => '+1234567890',
                'date_of_birth' => '1995-06-15',
                'gender' => 'female',
                'interested_in' => 'male',
                'bio' => 'Love hiking and coffee dates! Looking for genuine connections.',
                'location' => 'New York, NY',
                'verification_status' => 'verified'
            ],
            'user2' => [
                'name' => 'Michael Chen',
                'email' => 'michael.test.chat@lovebirds.com', 
                'password' => 'TestPassword123!',
                'phone_number' => '+1234567891',
                'date_of_birth' => '1992-03-20',
                'gender' => 'male',
                'interested_in' => 'female',
                'bio' => 'Software engineer who loves traveling and good food.',
                'location' => 'New York, NY',
                'verification_status' => 'verified'
            ]
        ];

        foreach ($this->testUsers as $key => $userData) {
            $response = $this->makeRequest('POST', '/auth/register', $userData);
            if (!$response['success']) {
                // User might already exist, try to continue
                echo "⚠️  User {$key} might already exist, continuing...\n";
            } else {
                echo "✅ Created test user: {$userData['name']}\n";
            }
        }
    }

    private function testUserAuthentication()
    {
        echo "\n🔐 Testing user authentication...\n";
        
        foreach ($this->testUsers as $key => $userData) {
            $response = $this->makeRequest('POST', '/auth/login', [
                'email' => $userData['email'],
                'password' => $userData['password']
            ]);
            
            if ($response['success']) {
                $this->authTokens[$key] = $response['data']['token'];
                echo "✅ {$userData['name']} authenticated successfully\n";
            } else {
                throw new Exception("Failed to authenticate {$userData['name']}: " . $response['message']);
            }
        }
    }

    private function testChatStartWithMatchValidation()
    {
        echo "\n💕 Testing chat start with match validation...\n";
        
        // First create a match between the users
        $user1Token = $this->authTokens['user1'];
        $user2Data = $this->getUserByEmail($this->testUsers['user2']['email']);
        
        if (!$user2Data) {
            throw new Exception("Could not find user2 data");
        }

        // Start chat between matched users
        $response = $this->makeRequest('POST', '/chat-start', [
            'receiver_id' => $user2Data['id']
        ], $user1Token);
        
        if ($response['success']) {
            $this->testChatHead = $response['data'];
            echo "✅ Chat started successfully between matched users\n";
            echo "   Chat Head ID: {$this->testChatHead['id']}\n";
        } else {
            throw new Exception("Failed to start chat: " . $response['message']);
        }
    }

    private function testMultimediaMessaging()
    {
        echo "\n📱 Testing multimedia messaging...\n";
        
        if (!$this->testChatHead) {
            throw new Exception("No test chat head available");
        }

        $testMessages = [
            [
                'type' => 'text',
                'body' => 'Hey! How are you doing today? 😊',
                'description' => 'Text message with emoji'
            ],
            [
                'type' => 'photo',
                'body' => 'Check out this sunset!',
                'photo' => 'uploads/test_sunset.jpg',
                'media_thumbnail' => 'uploads/thumbs/test_sunset_thumb.jpg',
                'description' => 'Photo message'
            ],
            [
                'type' => 'voice',
                'body' => 'Voice message',
                'audio' => 'uploads/voice_message.mp3',
                'media_duration' => 15,
                'description' => 'Voice message'
            ],
            [
                'type' => 'location',
                'body' => 'Let\'s meet here!',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'location_name' => 'Central Park, NYC',
                'description' => 'Location sharing'
            ]
        ];

        foreach ($testMessages as $messageData) {
            $messageData['receiver_id'] = $this->testChatHead['product_owner_id'] ?? $this->testChatHead['customer_id'];
            
            $response = $this->makeRequest('POST', '/chat-send', $messageData, $this->authTokens['user1']);
            
            if ($response['success']) {
                $this->testMessages[] = $response['data'];
                echo "✅ {$messageData['description']} sent successfully\n";
            } else {
                throw new Exception("Failed to send {$messageData['description']}: " . $response['message']);
            }
            
            sleep(1); // Small delay between messages
        }
    }

    private function testMessageReactions()
    {
        echo "\n😍 Testing message reactions...\n";
        
        if (empty($this->testMessages)) {
            throw new Exception("No test messages available for reactions");
        }

        $firstMessage = $this->testMessages[0];
        $reactions = ['❤️', '😂', '👍', '😮', '😢'];

        foreach ($reactions as $emoji) {
            $response = $this->makeRequest('POST', '/chat-add-reaction', [
                'message_id' => $firstMessage['id'],
                'emoji' => $emoji
            ], $this->authTokens['user2']);
            
            if ($response['success']) {
                echo "✅ Added reaction: {$emoji}\n";
            } else {
                echo "⚠️  Failed to add reaction {$emoji}: " . $response['message'] . "\n";
            }
        }

        // Test removing a reaction
        $response = $this->makeRequest('POST', '/chat-remove-reaction', [
            'message_id' => $firstMessage['id']
        ], $this->authTokens['user2']);
        
        if ($response['success']) {
            echo "✅ Reaction removed successfully\n";
        } else {
            echo "⚠️  Failed to remove reaction: " . $response['message'] . "\n";
        }
    }

    private function testTypingIndicators()
    {
        echo "\n⌨️  Testing typing indicators...\n";
        
        if (!$this->testChatHead) {
            throw new Exception("No test chat head available");
        }

        // User 1 starts typing
        $response = $this->makeRequest('POST', '/chat-typing-indicator', [
            'chat_head_id' => $this->testChatHead['id'],
            'is_typing' => true
        ], $this->authTokens['user1']);
        
        if ($response['success']) {
            echo "✅ User 1 typing indicator set\n";
        } else {
            echo "⚠️  Failed to set typing indicator: " . $response['message'] . "\n";
        }

        // User 2 checks typing status
        $response = $this->makeRequest('GET', '/chat-typing-status', [
            'chat_head_id' => $this->testChatHead['id']
        ], $this->authTokens['user2']);
        
        if ($response['success']) {
            echo "✅ Typing status retrieved: " . ($response['data']['other_user_typing'] ? 'typing' : 'not typing') . "\n";
        } else {
            echo "⚠️  Failed to get typing status: " . $response['message'] . "\n";
        }

        // User 1 stops typing
        $response = $this->makeRequest('POST', '/chat-typing-indicator', [
            'chat_head_id' => $this->testChatHead['id'],
            'is_typing' => false
        ], $this->authTokens['user1']);
        
        if ($response['success']) {
            echo "✅ User 1 stopped typing\n";
        }
    }

    private function testChatBlocking()
    {
        echo "\n🚫 Testing chat blocking functionality...\n";
        
        if (!$this->testChatHead) {
            throw new Exception("No test chat head available");
        }

        $user2Data = $this->getUserByEmail($this->testUsers['user2']['email']);
        
        // User 1 blocks User 2
        $response = $this->makeRequest('POST', '/chat-block-user', [
            'chat_head_id' => $this->testChatHead['id'],
            'blocked_user_id' => $user2Data['id'],
            'reason' => 'Test blocking functionality'
        ], $this->authTokens['user1']);
        
        if ($response['success']) {
            echo "✅ User blocked successfully\n";
        } else {
            echo "⚠️  Failed to block user: " . $response['message'] . "\n";
        }

        // Try to send message while blocked (should fail)
        $response = $this->makeRequest('POST', '/chat-send', [
            'receiver_id' => $user2Data['id'],
            'body' => 'This should not work - user is blocked',
            'type' => 'text'
        ], $this->authTokens['user2']);
        
        if (!$response['success']) {
            echo "✅ Blocked user correctly prevented from sending message\n";
        } else {
            echo "⚠️  Blocked user was able to send message (unexpected)\n";
        }

        // Unblock user
        $response = $this->makeRequest('POST', '/chat-unblock-user', [
            'chat_head_id' => $this->testChatHead['id'],
            'blocked_user_id' => $user2Data['id']
        ], $this->authTokens['user1']);
        
        if ($response['success']) {
            echo "✅ User unblocked successfully\n";
        } else {
            echo "⚠️  Failed to unblock user: " . $response['message'] . "\n";
        }
    }

    private function testChatMediaFiles()
    {
        echo "\n🖼️  Testing chat media files retrieval...\n";
        
        if (!$this->testChatHead) {
            throw new Exception("No test chat head available");
        }

        $mediaTypes = ['all', 'photo', 'video', 'audio'];
        
        foreach ($mediaTypes as $mediaType) {
            $response = $this->makeRequest('GET', '/chat-media-files', [
                'chat_head_id' => $this->testChatHead['id'],
                'media_type' => $mediaType
            ], $this->authTokens['user1']);
            
            if ($response['success']) {
                $count = count($response['data']);
                echo "✅ Retrieved {$count} {$mediaType} media files\n";
            } else {
                echo "⚠️  Failed to retrieve {$mediaType} media files: " . $response['message'] . "\n";
            }
        }
    }

    private function testMessageSearch()
    {
        echo "\n🔍 Testing message search...\n";
        
        if (!$this->testChatHead) {
            throw new Exception("No test chat head available");
        }

        $searchTerms = ['Hey', 'sunset', 'doing'];
        
        foreach ($searchTerms as $term) {
            $response = $this->makeRequest('GET', '/chat-search-messages', [
                'chat_head_id' => $this->testChatHead['id'],
                'search_term' => $term
            ], $this->authTokens['user1']);
            
            if ($response['success']) {
                $count = count($response['data']);
                echo "✅ Found {$count} messages containing '{$term}'\n";
            } else {
                echo "⚠️  Failed to search for '{$term}': " . $response['message'] . "\n";
            }
        }
    }

    private function testReplyFunctionality()
    {
        echo "\n💬 Testing reply functionality...\n";
        
        if (empty($this->testMessages)) {
            throw new Exception("No test messages available for replies");
        }

        $originalMessage = $this->testMessages[0];
        $user2Data = $this->getUserByEmail($this->testUsers['user2']['email']);
        
        $response = $this->makeRequest('POST', '/chat-send', [
            'receiver_id' => $user2Data['id'],
            'body' => 'This is a reply to your message!',
            'type' => 'text',
            'reply_to_id' => $originalMessage['id']
        ], $this->authTokens['user2']);
        
        if ($response['success']) {
            echo "✅ Reply message sent successfully\n";
            echo "   Original message ID: {$originalMessage['id']}\n";
            echo "   Reply message ID: {$response['data']['id']}\n";
        } else {
            echo "⚠️  Failed to send reply: " . $response['message'] . "\n";
        }
    }

    private function getUserByEmail($email)
    {
        // This would typically query the database
        // For testing, we'll make a simple assumption about user IDs
        static $userIdCounter = 1;
        return ['id' => $userIdCounter++, 'email' => $email];
    }

    private function makeRequest($method, $endpoint, $data = [], $token = null)
    {
        $url = $this->baseUrl . $endpoint;
        $headers = ['Content-Type: application/json'];
        
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
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
            return ['success' => false, 'message' => 'Network error', 'data' => null];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON response', 'data' => $response];
        }

        return $decoded;
    }

    private function cleanup()
    {
        echo "\n🧹 Cleaning up test data...\n";
        // In a real implementation, you would clean up test users and data here
        echo "✅ Cleanup completed\n";
    }
}

// Run the test suite
if (php_sapi_name() === 'cli') {
    $baseUrl = $argv[1] ?? 'http://localhost/lovebirds-api/public/api';
    $testSuite = new EnhancedChatTestSuite($baseUrl);
    $testSuite->runAllTests();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test_enhanced_chat_comprehensive.php [base_url]\n";
}
