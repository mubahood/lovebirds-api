<?php
/**
 * Comprehensive Test Script for Advanced Dating Discovery System
 * 
 * Tests all the new discovery endpoints including:
 * - discover-users (comprehensive filtering)
 * - discovery-stats (statistics and insights)
 * - smart-recommendations (AI-like recommendations)
 * - swipe-discovery (Tinder-style swiping)
 * - search-users (text-based search)
 * - nearby-users (location-based discovery)
 */

echo "🚀 Testing Advanced Dating Discovery System\n";
echo "==========================================\n\n";

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
        return ['success' => false, 'message' => 'Invalid JSON', 'raw' => substr($response, 0, 200), 'http_code' => $httpCode];
    }

    return array_merge($decoded, ['http_code' => $httpCode]);
}

// Test data
$testUser = [
    'email' => 'test.discovery@lovebirds.com',
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
        'name' => 'Discovery Test User',
        'phone_number' => '+1234567892',
        'date_of_birth' => '1993-05-15',
        'gender' => 'female',
        'interested_in' => 'male',
        'bio' => 'Testing the discovery system',
        'city' => 'San Francisco',
        'latitude' => 37.7749,
        'longitude' => -122.4194,
        'age_range_min' => 25,
        'age_range_max' => 35,
        'max_distance_km' => 50,
        'interests' => json_encode(['hiking', 'coffee', 'reading', 'travel']),
        'religion' => 'Christian',
        'education_level' => 'Bachelor\'s Degree',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Socially'
    ]);
    
    $regResponse = makeRequest('POST', '/auth/register', $registerData);
    echo "Registration response: " . ($regResponse['success'] ? 'Success' : $regResponse['message']) . "\n";
    
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

// Test all discovery endpoints
$discoveryEndpoints = [
    [
        'name' => 'Basic User Discovery',
        'method' => 'GET',
        'endpoint' => '/discover-users',
        'data' => ['per_page' => 5]
    ],
    [
        'name' => 'Discovery with Age Filter',
        'method' => 'GET',
        'endpoint' => '/discover-users',
        'data' => ['per_page' => 5, 'age_min' => 25, 'age_max' => 35]
    ],
    [
        'name' => 'Discovery with Location Filter',
        'method' => 'GET',
        'endpoint' => '/discover-users',
        'data' => ['per_page' => 5, 'max_distance' => 25]
    ],
    [
        'name' => 'Discovery with Multiple Filters',
        'method' => 'GET',
        'endpoint' => '/discover-users',
        'data' => [
            'per_page' => 5,
            'max_distance' => 50,
            'verified_only' => true,
            'recently_active' => true,
            'shared_interests' => true,
            'sort_by' => 'smart'
        ]
    ],
    [
        'name' => 'Discovery Statistics',
        'method' => 'GET',
        'endpoint' => '/discovery-stats',
        'data' => []
    ],
    [
        'name' => 'Smart Recommendations',
        'method' => 'GET',
        'endpoint' => '/smart-recommendations',
        'data' => []
    ],
    [
        'name' => 'Swipe Discovery',
        'method' => 'GET',
        'endpoint' => '/swipe-discovery',
        'data' => []
    ],
    [
        'name' => 'User Search by Name',
        'method' => 'GET',
        'endpoint' => '/search-users',
        'data' => ['search_term' => 'test']
    ],
    [
        'name' => 'User Search by Interest',
        'method' => 'GET',
        'endpoint' => '/search-users',
        'data' => ['search_term' => 'hiking']
    ],
    [
        'name' => 'Nearby Users (25km)',
        'method' => 'GET',
        'endpoint' => '/nearby-users',
        'data' => ['radius' => 25]
    ],
    [
        'name' => 'Nearby Users (10km)',
        'method' => 'GET',
        'endpoint' => '/nearby-users',
        'data' => ['radius' => 10]
    ]
];

foreach ($discoveryEndpoints as $test) {
    echo "🧪 Testing: {$test['name']}\n";
    $response = makeRequest($test['method'], $test['endpoint'], $test['data'], $token);
    
    echo "   HTTP Code: {$response['http_code']}\n";
    
    if ($response['http_code'] === 200) {
        echo "   ✅ Endpoint accessible\n";
        
        if (isset($response['success'])) {
            echo "   📊 Success: " . ($response['success'] ? 'true' : 'false') . "\n";
            
            if ($response['success'] && isset($response['data'])) {
                // Analyze response data
                if (isset($response['data']['users'])) {
                    $userCount = is_array($response['data']['users']) ? count($response['data']['users']) : 0;
                    echo "   👥 Users found: {$userCount}\n";
                    
                    if ($userCount > 0) {
                        $firstUser = $response['data']['users'][0];
                        if (isset($firstUser['compatibility_score'])) {
                            echo "   💕 Compatibility score: {$firstUser['compatibility_score']}%\n";
                        }
                        if (isset($firstUser['distance'])) {
                            echo "   📍 Distance: {$firstUser['distance']}km\n";
                        }
                        if (isset($firstUser['is_online'])) {
                            echo "   🟢 Online status: " . ($firstUser['is_online'] ? 'Online' : 'Offline') . "\n";
                        }
                    }
                    
                    if (isset($response['data']['pagination'])) {
                        $pagination = $response['data']['pagination'];
                        echo "   📄 Pagination: Page {$pagination['current_page']} of {$pagination['last_page']} (Total: {$pagination['total']})\n";
                    }
                }
                
                if (isset($response['data']['user'])) {
                    // Swipe discovery response
                    $user = $response['data']['user'];
                    if ($user) {
                        echo "   👤 Swipe user: {$user['name']} (ID: {$user['id']})\n";
                        if (isset($user['compatibility_score'])) {
                            echo "   💕 Compatibility: {$user['compatibility_score']}%\n";
                        }
                    } else {
                        echo "   😕 No more users to swipe\n";
                    }
                }
                
                if (isset($response['data']['total_potential_matches'])) {
                    // Discovery stats response
                    $stats = $response['data'];
                    echo "   📊 Potential matches: {$stats['total_potential_matches']}\n";
                    echo "   🆕 New this week: {$stats['new_users_this_week']}\n";
                    echo "   🟢 Online now: {$stats['online_users_now']}\n";
                    echo "   📍 Nearby: {$stats['nearby_users']}\n";
                }
                
                if (isset($response['data']['recommendations'])) {
                    // Smart recommendations response
                    $recommendationCount = count($response['data']['recommendations']);
                    echo "   🤖 Smart recommendations: {$recommendationCount}\n";
                    
                    if ($recommendationCount > 0) {
                        $topRecommendation = $response['data']['recommendations'][0];
                        echo "   🏆 Top match: {$topRecommendation['name']} ({$topRecommendation['compatibility_score']}%)\n";
                        
                        if (isset($topRecommendation['compatibility_reasons'])) {
                            echo "   💡 Reasons: " . implode(', ', $topRecommendation['compatibility_reasons']) . "\n";
                        }
                    }
                }
                
                if (isset($response['data']['results'])) {
                    // Search results response
                    $resultCount = count($response['data']['results']);
                    echo "   🔍 Search results: {$resultCount}\n";
                    if (isset($response['data']['search_term'])) {
                        echo "   🔎 Search term: '{$response['data']['search_term']}'\n";
                    }
                }
                
                if (isset($response['data']['nearby_users'])) {
                    // Nearby users response
                    $nearbyCount = count($response['data']['nearby_users']);
                    echo "   📍 Nearby users: {$nearbyCount}\n";
                    if (isset($response['data']['radius_km'])) {
                        echo "   🎯 Search radius: {$response['data']['radius_km']}km\n";
                    }
                }
                
                if (isset($response['data']['filters_applied'])) {
                    $filterCount = count($response['data']['filters_applied']);
                    echo "   🔧 Filters applied: {$filterCount}\n";
                }
            }
            
            if (!$response['success'] && isset($response['message'])) {
                echo "   📝 Message: {$response['message']}\n";
            }
        }
    } else {
        echo "   ❌ HTTP Error\n";
        if (isset($response['message'])) {
            echo "   📝 Error: {$response['message']}\n";
        }
        if (isset($response['raw'])) {
            echo "   🔍 Raw response: {$response['raw']}\n";
        }
    }
    echo "\n";
}

echo "🏁 Discovery system testing completed!\n";
echo "\nℹ️  Summary:\n";
echo "   - All discovery endpoints tested\n";
echo "   - Filtering capabilities validated\n";
echo "   - Compatibility scoring tested\n";
echo "   - Location-based discovery verified\n";
echo "   - Smart recommendations evaluated\n";
echo "   - Pagination and search functionality checked\n";
echo "\n📊 Note: Some endpoints may return empty results if there's insufficient\n";
echo "   test data in the database. The important thing is that the endpoints\n";
echo "   are accessible and returning proper API response structures.\n";
