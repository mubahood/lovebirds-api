<?php

/**
 * Test Phase 6.1 Dating-Focused Chat Features
 * Tests the new chat enhancement APIs and dating functionality
 */

require_once 'vendor/autoload.php';

// Test configuration
$base_url = 'http://localhost/lovebirds-api/api/';
$test_token = 'YOUR_JWT_TOKEN_HERE'; // Replace with actual JWT token

class Phase61ChatTester
{
    private $baseUrl;
    private $token;
    private $testResults = [];

    public function __construct($baseUrl, $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    private function makeRequest($endpoint, $data = [], $method = 'POST')
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'response' => json_decode($response, true),
            'raw_response' => $response
        ];
    }

    public function testGetChatMessages()
    {
        echo "🔄 Testing Chat Messages Retrieval...\n";
        
        $result = $this->makeRequest('get-chat-messages', [
            'other_user_id' => 123
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['chat_messages'] = $success;

        if ($success) {
            echo "✅ Chat messages retrieved successfully\n";
            echo "   Messages count: " . count($result['response']['data']) . "\n";
        } else {
            echo "❌ Failed to retrieve chat messages\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testSendMessage()
    {
        echo "🔄 Testing Message Sending...\n";
        
        $result = $this->makeRequest('send-message', [
            'receiver_id' => 123,
            'message' => 'Hey! How was your day? I was thinking we could grab coffee sometime this week. What do you think? ☕️'
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['send_message'] = $success;

        if ($success) {
            echo "✅ Message sent successfully\n";
            echo "   Message ID: " . $result['response']['data']['id'] . "\n";
        } else {
            echo "❌ Failed to send message\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testRestaurantSuggestions()
    {
        echo "🔄 Testing Restaurant Suggestions...\n";
        
        $result = $this->makeRequest('get-restaurant-suggestions', []);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['restaurant_suggestions'] = $success;

        if ($success) {
            echo "✅ Restaurant suggestions retrieved successfully\n";
            echo "   Restaurants count: " . count($result['response']['data']) . "\n";
            
            foreach ($result['response']['data'] as $restaurant) {
                echo "   • {$restaurant['name']} ({$restaurant['cuisine']}) - {$restaurant['rating']}⭐\n";
            }
        } else {
            echo "❌ Failed to retrieve restaurant suggestions\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testDateActivities()
    {
        echo "🔄 Testing Date Activities...\n";
        
        $result = $this->makeRequest('get-date-activities', []);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['date_activities'] = $success;

        if ($success) {
            echo "✅ Date activities retrieved successfully\n";
            echo "   Activities count: " . count($result['response']['data']) . "\n";
            
            foreach ($result['response']['data'] as $activity) {
                echo "   • {$activity['title']} ({$activity['category']}) - {$activity['duration']}\n";
            }
        } else {
            echo "❌ Failed to retrieve date activities\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testPopularDateSpots()
    {
        echo "🔄 Testing Popular Date Spots...\n";
        
        $result = $this->makeRequest('get-popular-date-spots', []);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['popular_date_spots'] = $success;

        if ($success) {
            echo "✅ Popular date spots retrieved successfully\n";
            echo "   Spots count: " . count($result['response']['data']) . "\n";
            
            foreach ($result['response']['data'] as $spot) {
                echo "   • {$spot['name']} ({$spot['type']}) - {$spot['rating']}⭐\n";
            }
        } else {
            echo "❌ Failed to retrieve popular date spots\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testSavePlannedDate()
    {
        echo "🔄 Testing Save Planned Date...\n";
        
        $result = $this->makeRequest('save-planned-date', [
            'partner_id' => 123,
            'type' => 'restaurant',
            'details' => [
                'restaurant_name' => 'The Romantic Garden',
                'date_time' => '2024-02-14 19:30:00',
                'special_requests' => 'Window table please'
            ],
            'planned_date' => '2024-02-14'
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['save_planned_date'] = $success;

        if ($success) {
            echo "✅ Planned date saved successfully\n";
            echo "   Date ID: " . $result['response']['data']['id'] . "\n";
        } else {
            echo "❌ Failed to save planned date\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testAdvancedSearch()
    {
        echo "🔄 Testing Advanced Search...\n";
        
        $result = $this->makeRequest('advanced-search', [
            'age_range' => [25, 35],
            'distance' => 25,
            'interests' => ['hiking', 'cooking', 'travel'],
            'education' => 'University Graduate',
            'relationship_goals' => 'Long-term relationship',
            'lifestyle' => ['active', 'social'],
            'personality' => ['outgoing', 'adventurous']
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['advanced_search'] = $success;

        if ($success) {
            echo "✅ Advanced search completed successfully\n";
            echo "   Results count: " . $result['response']['data']['total_count'] . "\n";
            
            foreach ($result['response']['data']['results'] as $match) {
                echo "   • {$match['name']} (Age {$match['age']}) - {$match['compatibility_score']}% match\n";
            }
        } else {
            echo "❌ Failed to perform advanced search\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function runAllTests()
    {
        echo "🚀 Starting Phase 6.1 Dating-Focused Chat Features Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";

        // Note: These tests require valid JWT authentication
        echo "⚠️  Note: These tests require a valid JWT token for authentication.\n";
        echo "   Please update the \$test_token variable with a real JWT token.\n\n";

        $this->testGetChatMessages();
        $this->testSendMessage();
        $this->testRestaurantSuggestions();
        $this->testDateActivities();
        $this->testPopularDateSpots();
        $this->testSavePlannedDate();
        $this->testAdvancedSearch();

        $this->printSummary();
    }

    private function printSummary()
    {
        echo "📊 Test Summary\n";
        echo "=" . str_repeat("=", 60) . "\n";

        $totalTests = count($this->testResults);
        $passedTests = array_sum($this->testResults);
        $failedTests = $totalTests - $passedTests;

        foreach ($this->testResults as $test => $passed) {
            $status = $passed ? "✅ PASS" : "❌ FAIL";
            echo sprintf("%-25s %s\n", str_replace('_', ' ', ucwords($test, '_')), $status);
        }

        echo "\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests\n";
        echo "Failed: $failedTests\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

        if ($passedTests === $totalTests) {
            echo "🎉 All Phase 6.1 tests passed! Dating chat features are ready.\n";
        } else {
            echo "🔧 Some tests failed. Please check the API implementation.\n";
        }

        echo "\n📋 Phase 6.1 Features Tested:\n";
        echo "• Chat message retrieval and sending\n";
        echo "• Restaurant suggestion system\n";
        echo "• Date activity recommendations\n";
        echo "• Popular date spots discovery\n";
        echo "• Planned date saving functionality\n";
        echo "• Advanced search with dating filters\n";
    }
}

// Run the tests
try {
    $tester = new Phase61ChatTester($base_url, $test_token);
    $tester->runAllTests();
} catch (Exception $e) {
    echo "❌ Test execution failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Update \$test_token with a valid JWT token\n";
echo "2. Run: php test_phase_6_1_chat_features.php\n";
echo "3. Proceed to Phase 6.2 Chat Safety & Moderation\n";
echo "4. Test the mobile app integration\n\n";

?>
