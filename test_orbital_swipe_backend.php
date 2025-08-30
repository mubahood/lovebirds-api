<?php
/**
 * Test script for the new orbital swipe batch endpoint
 * Run this to verify the backend API changes are working correctly
 */

require_once 'vendor/autoload.php';

// Configuration
$API_BASE_URL = "http://localhost:8888/lovebirds-api"; // Adjust for your local setup
$TEST_USER_TOKEN = ""; // You'll need to get a valid JWT token

function testOrbitalBatchEndpoint($token) {
    global $API_BASE_URL;
    
    echo "Testing orbital batch endpoint...\n";
    
    $url = "$API_BASE_URL/api/swipe-discovery-batch?count=8";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($response === false) {
        echo "❌ cURL Error: " . curl_error($ch) . "\n";
        return false;
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['code']) && $data['code'] == 1) {
        echo "✅ Orbital batch endpoint working!\n";
        echo "Users returned: " . count($data['data']['users'] ?? []) . "\n";
        echo "Has more: " . ($data['data']['has_more'] ? 'Yes' : 'No') . "\n";
        echo "Orbital positioning data: " . (isset($data['data']['batch_info']['orbital_positioning']) ? 'Present' : 'Missing') . "\n";
        return true;
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        return false;
    }
}

function testEnhancedDiscoveryStats($token) {
    global $API_BASE_URL;
    
    echo "\nTesting enhanced discovery stats...\n";
    
    $url = "$API_BASE_URL/api/discovery-stats";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($response === false) {
        echo "❌ cURL Error: " . curl_error($ch) . "\n";
        return false;
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['code']) && $data['code'] == 1) {
        echo "✅ Discovery stats endpoint working!\n";
        echo "Daily stats present: " . (isset($data['data']['daily_stats']) ? 'Yes' : 'No') . "\n";
        echo "Orbital optimization present: " . (isset($data['data']['orbital_optimization']) ? 'Yes' : 'No') . "\n";
        
        if (isset($data['data']['daily_stats'])) {
            $dailyStats = $data['data']['daily_stats'];
            echo "Likes remaining: " . ($dailyStats['likes_remaining'] ?? 'N/A') . "\n";
            echo "Super likes remaining: " . ($dailyStats['super_likes_remaining'] ?? 'N/A') . "\n";
        }
        
        return true;
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        return false;
    }
}

function testSwipeStats($token) {
    global $API_BASE_URL;
    
    echo "\nTesting enhanced swipe stats...\n";
    
    $url = "$API_BASE_URL/api/swipe-stats";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($response === false) {
        echo "❌ cURL Error: " . curl_error($ch) . "\n";
        return false;
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['code']) && $data['code'] == 1) {
        echo "✅ Swipe stats endpoint working!\n";
        echo "Orbital stats present: " . (isset($data['data']['orbital_stats']) ? 'Yes' : 'No') . "\n";
        echo "Mobile app compatibility: " . (isset($data['data']['likesRemaining']) ? 'Yes' : 'No') . "\n";
        
        if (isset($data['data']['orbital_stats'])) {
            $orbitalStats = $data['data']['orbital_stats'];
            echo "Can like: " . ($orbitalStats['can_like'] ? 'Yes' : 'No') . "\n";
            echo "Can super like: " . ($orbitalStats['can_super_like'] ? 'Yes' : 'No') . "\n";
        }
        
        return true;
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        return false;
    }
}

// Main test execution
echo "🚀 Testing Orbital Swipe Backend Integration\n";
echo "==========================================\n";

if (empty($TEST_USER_TOKEN)) {
    echo "❌ Please set TEST_USER_TOKEN with a valid JWT token\n";
    echo "You can get one by calling the login endpoint or using an existing test token\n";
    exit(1);
}

$allTestsPassed = true;

// Run tests
$allTestsPassed &= testOrbitalBatchEndpoint($TEST_USER_TOKEN);
$allTestsPassed &= testEnhancedDiscoveryStats($TEST_USER_TOKEN);
$allTestsPassed &= testSwipeStats($TEST_USER_TOKEN);

echo "\n==========================================\n";
if ($allTestsPassed) {
    echo "🎉 All tests passed! Orbital swipe backend is ready!\n";
} else {
    echo "❌ Some tests failed. Check the output above for details.\n";
}

echo "\n📋 What to do next:\n";
echo "1. ✅ Backend API enhanced with orbital endpoints\n";
echo "2. 🔄 Test with mobile app to verify integration\n";
echo "3. 📱 Update mobile app to use OrbitalSwipeScreen by default\n";
echo "4. 🎯 Fine-tune orbital positioning and animations\n";
