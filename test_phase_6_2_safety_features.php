<?php

/**
 * Test Phase 6.2 Chat Safety & Moderation Features
 * Tests AI-powered safety features, photo sharing consent, and emergency systems
 */

require_once 'vendor/autoload.php';

// Test configuration
$base_url = 'http://localhost/lovebirds-api/api/';
$test_token = 'YOUR_JWT_TOKEN_HERE'; // Replace with actual JWT token

class Phase62SafetyTester
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

    public function testMessageSafetyAnalysis()
    {
        echo "🔍 Testing AI-Powered Message Safety Analysis...\n";
        
        // Test safe message
        $safeResult = $this->makeRequest('analyze-message-safety', [
            'message' => 'Hey! How was your day? I had a wonderful time at the coffee shop this morning.'
        ]);

        // Test potentially inappropriate message
        $dangerousResult = $this->makeRequest('analyze-message-safety', [
            'message' => 'This is a test message with inappropriate threat content for testing'
        ]);

        $success = $safeResult['status_code'] === 200 && 
                  $dangerousResult['status_code'] === 200 &&
                  isset($safeResult['response']['success']) && 
                  isset($dangerousResult['response']['success']);

        $this->testResults['message_safety_analysis'] = $success;

        if ($success) {
            echo "✅ Message safety analysis working correctly\n";
            echo "   Safe message safety level: " . $safeResult['response']['data']['safety_level'] . "\n";
            echo "   Dangerous message safety level: " . $dangerousResult['response']['data']['safety_level'] . "\n";
            echo "   Sentiment analysis included: " . ($safeResult['response']['data']['sentiment_score'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "❌ Failed to analyze message safety\n";
            echo "   Safe message status: " . $safeResult['status_code'] . "\n";
            echo "   Dangerous message status: " . $dangerousResult['status_code'] . "\n";
        }
        echo "\n";
    }

    public function testUnsafeBehaviorReporting()
    {
        echo "🚨 Testing Unsafe Behavior Reporting System...\n";
        
        $result = $this->makeRequest('report-unsafe-behavior', [
            'reported_user_id' => 123,
            'reason' => 'inappropriate_messages',
            'description' => 'User sent inappropriate content and made me feel uncomfortable with persistent requests for personal information.'
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['unsafe_behavior_reporting'] = $success;

        if ($success) {
            echo "✅ Unsafe behavior reporting working correctly\n";
            echo "   Report ID: " . $result['response']['data']['report_id'] . "\n";
            echo "   Review time: " . $result['response']['data']['estimated_review_time'] . "\n";
            echo "   Next steps provided: " . count($result['response']['data']['next_steps']) . " items\n";
        } else {
            echo "❌ Failed to submit report\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testMeetupConsentVerification()
    {
        echo "🤝 Testing Mutual Meetup Consent Verification...\n";
        
        $result = $this->makeRequest('verify-meetup-consent', [
            'partner_id' => 456,
            'meetup_details' => [
                'location' => 'Central Park Coffee Shop',
                'date_time' => '2024-02-14 15:00:00',
                'activity' => 'Coffee and conversation',
                'is_public_place' => true,
                'emergency_contact' => 'Best friend Sarah'
            ]
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['meetup_consent_verification'] = $success;

        if ($success) {
            echo "✅ Meetup consent verification working correctly\n";
            echo "   Verification ID: " . $result['response']['data']['verification_id'] . "\n";
            echo "   Both consented: " . ($result['response']['data']['both_consented'] ? 'Yes' : 'No') . "\n";
            echo "   Safety reminders: " . count($result['response']['data']['safety_reminders']) . " items\n";
            echo "   Emergency features: " . count($result['response']['data']['emergency_features']) . " available\n";
        } else {
            echo "❌ Failed to verify meetup consent\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testPhotoSharingRiskAssessment()
    {
        echo "📸 Testing Photo Sharing Safety Warnings...\n";
        
        $result = $this->makeRequest('check-photo-sharing-risk', [
            'receiver_id' => 789
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['photo_sharing_risk_assessment'] = $success;

        if ($success) {
            echo "✅ Photo sharing risk assessment working correctly\n";
            echo "   Risk level: " . $result['response']['data']['risk_level'] . "\n";
            echo "   Relationship duration: " . $result['response']['data']['relationship_duration_days'] . " days\n";
            echo "   Safety warnings: " . count($result['response']['data']['warnings']) . " items\n";
            echo "   Consent required: " . ($result['response']['data']['should_show_consent'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "❌ Failed to assess photo sharing risk\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testConversationSentimentAnalysis()
    {
        echo "💭 Testing Conversation Sentiment Analysis...\n";
        
        $result = $this->makeRequest('analyze-conversation-sentiment', [
            'partner_id' => 321,
            'recent_messages' => [
                'Hello! How are you doing today?',
                'I had such a great time at the park yesterday!',
                'Would you like to grab coffee sometime this week?',
                'I love how we can talk about anything together',
                'Thank you for being so understanding and kind'
            ]
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['conversation_sentiment_analysis'] = $success;

        if ($success) {
            echo "✅ Conversation sentiment analysis working correctly\n";
            echo "   Health status: " . $result['response']['data']['health_status'] . "\n";
            echo "   Average sentiment: " . $result['response']['data']['average_sentiment'] . "/1.0\n";
            echo "   Sentiment trend: " . $result['response']['data']['sentiment_trend'] . "\n";
            echo "   Recommendations: " . count($result['response']['data']['recommendations']) . " items\n";
        } else {
            echo "❌ Failed to analyze conversation sentiment\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function testEmergencySafetyAlert()
    {
        echo "🚨 Testing Emergency Safety Alert System...\n";
        
        $result = $this->makeRequest('emergency-safety-alert', [
            'alert_type' => 'safety_concern',
            'location' => [
                'latitude' => 43.6532,
                'longitude' => -79.3832,
                'address' => '123 Main St, Toronto, ON'
            ],
            'additional_info' => 'Feeling uncomfortable with current situation. Person not matching profile and acting suspicious.'
        ]);

        $success = $result['status_code'] === 200 && 
                  isset($result['response']['success']) && 
                  $result['response']['success'] === true;

        $this->testResults['emergency_safety_alert'] = $success;

        if ($success) {
            echo "✅ Emergency safety alert system working correctly\n";
            echo "   Alert ID: " . $result['response']['data']['alert_id'] . "\n";
            echo "   Emergency contacts notified: " . ($result['response']['data']['emergency_contacts_notified'] ? 'Yes' : 'No') . "\n";
            echo "   Safety team notified: " . ($result['response']['data']['safety_team_notified'] ? 'Yes' : 'No') . "\n";
            echo "   Immediate resources: " . count($result['response']['data']['immediate_resources']) . " available\n";
        } else {
            echo "❌ Failed to process emergency alert\n";
            echo "   Status: " . $result['status_code'] . "\n";
            echo "   Response: " . json_encode($result['response']) . "\n";
        }
        echo "\n";
    }

    public function runAllTests()
    {
        echo "🛡️  Starting Phase 6.2 Chat Safety & Moderation Tests\n";
        echo "=" . str_repeat("=", 65) . "\n\n";

        echo "⚠️  Note: These tests require a valid JWT token for authentication.\n";
        echo "   Please update the \$test_token variable with a real JWT token.\n\n";

        $this->testMessageSafetyAnalysis();
        $this->testUnsafeBehaviorReporting();
        $this->testMeetupConsentVerification();
        $this->testPhotoSharingRiskAssessment();
        $this->testConversationSentimentAnalysis();
        $this->testEmergencySafetyAlert();

        $this->printSummary();
    }

    private function printSummary()
    {
        echo "📊 Test Summary\n";
        echo "=" . str_repeat("=", 65) . "\n";

        $totalTests = count($this->testResults);
        $passedTests = array_sum($this->testResults);
        $failedTests = $totalTests - $passedTests;

        foreach ($this->testResults as $test => $passed) {
            $status = $passed ? "✅ PASS" : "❌ FAIL";
            echo sprintf("%-35s %s\n", str_replace('_', ' ', ucwords($test, '_')), $status);
        }

        echo "\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests\n";
        echo "Failed: $failedTests\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

        if ($passedTests === $totalTests) {
            echo "🎉 All Phase 6.2 safety tests passed! Chat safety & moderation ready.\n";
        } else {
            echo "🔧 Some tests failed. Please check the API implementation.\n";
        }

        echo "\n🛡️  Phase 6.2 Safety Features Tested:\n";
        echo "• AI-powered inappropriate message detection\n";
        echo "• Unsafe behavior reporting system\n";
        echo "• Mutual consent verification for meetups\n";
        echo "• Photo sharing safety warnings and consent\n";
        echo "• Conversation sentiment analysis\n";
        echo "• Emergency safety alert system\n\n";

        echo "🔒 Security & Privacy Features:\n";
        echo "• Real-time content moderation\n";
        echo "• Comprehensive reporting mechanisms\n";
        echo "• Emergency contact notification\n";
        echo "• Location-based safety features\n";
        echo "• Consent verification protocols\n";
        echo "• 24/7 safety support integration\n";
    }
}

// Run the tests
try {
    $tester = new Phase62SafetyTester($base_url, $test_token);
    $tester->runAllTests();
} catch (Exception $e) {
    echo "❌ Test execution failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Update \$test_token with a valid JWT token\n";
echo "2. Run: php test_phase_6_2_safety_features.php\n";
echo "3. Test mobile app safety widget integration\n";
echo "4. Proceed to Phase 7.1 Canadian Market Optimization\n\n";

?>
