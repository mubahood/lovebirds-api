<?php
/**
 * Create Comprehensive Test Data for Dating Discovery System
 * 
 * This script creates diverse test users with various profiles, preferences,
 * and locations to properly test the advanced discovery features.
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🚀 Creating Comprehensive Test Data for Discovery System\n";
echo "======================================================\n\n";

$baseUrl = 'http://localhost/lovebirds-api/public/api';

function makeRequest($method, $endpoint, $data = []) {
    global $baseUrl;
    $url = $baseUrl . $endpoint;
    
    $headers = ['Content-Type: application/json'];

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
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Network error', 'http_code' => $httpCode];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Invalid JSON', 'http_code' => $httpCode];
    }

    return array_merge($decoded, ['http_code' => $httpCode]);
}

// Diverse test user profiles
$testUsers = [
    [
        'name' => 'Emma Williams',
        'email' => 'emma.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567001',
        'date_of_birth' => '1995-03-22',
        'gender' => 'female',
        'interested_in' => 'male',
        'bio' => 'Adventure seeker, coffee enthusiast, and dog lover! Looking for someone to explore the city with.',
        'city' => 'San Francisco',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.7749,
        'longitude' => -122.4194,
        'age_range_min' => 25,
        'age_range_max' => 35,
        'max_distance_km' => 50,
        'height' => 165,
        'interests' => json_encode(['hiking', 'coffee', 'dogs', 'photography', 'travel']),
        'religion' => 'Christian',
        'education_level' => 'Bachelor\'s Degree',
        'occupation' => 'Marketing Manager',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Socially',
        'pet_preference' => 'Love dogs',
        'looking_for' => 'Long-term relationship',
        'languages_spoken' => json_encode(['English', 'Spanish']),
        'lifestyle' => json_encode(['Active', 'Social', 'Outdoorsy'])
    ],
    [
        'name' => 'James Thompson',
        'email' => 'james.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567002',
        'date_of_birth' => '1990-08-15',
        'gender' => 'male',
        'interested_in' => 'female',
        'bio' => 'Software engineer by day, chef by night. Love cooking for friends and trying new restaurants.',
        'city' => 'San Francisco',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.7849,
        'longitude' => -122.4094,
        'age_range_min' => 23,
        'age_range_max' => 32,
        'max_distance_km' => 30,
        'height' => 180,
        'interests' => json_encode(['cooking', 'technology', 'travel', 'wine', 'reading']),
        'religion' => 'Agnostic',
        'education_level' => 'Master\'s Degree',
        'occupation' => 'Software Engineer',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Regularly',
        'pet_preference' => 'No preference',
        'looking_for' => 'Long-term relationship',
        'languages_spoken' => json_encode(['English', 'French']),
        'lifestyle' => json_encode(['Intellectual', 'Creative', 'Urban'])
    ],
    [
        'name' => 'Sofia Rodriguez',
        'email' => 'sofia.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567003',
        'date_of_birth' => '1993-12-05',
        'gender' => 'female',
        'interested_in' => 'both',
        'bio' => 'Artist and yoga instructor. Passionate about mindfulness, art, and sustainable living.',
        'city' => 'Oakland',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.8044,
        'longitude' => -122.2711,
        'age_range_min' => 25,
        'age_range_max' => 40,
        'max_distance_km' => 40,
        'height' => 158,
        'interests' => json_encode(['yoga', 'art', 'meditation', 'sustainability', 'music']),
        'religion' => 'Buddhist',
        'education_level' => 'Bachelor\'s Degree',
        'occupation' => 'Art Teacher',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Rarely',
        'pet_preference' => 'Love cats',
        'looking_for' => 'Casual dating',
        'languages_spoken' => json_encode(['English', 'Spanish', 'Portuguese']),
        'lifestyle' => json_encode(['Spiritual', 'Creative', 'Eco-conscious'])
    ],
    [
        'name' => 'David Kim',
        'email' => 'david.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567004',
        'date_of_birth' => '1988-07-18',
        'gender' => 'male',
        'interested_in' => 'female',
        'bio' => 'Finance professional who loves fitness, hiking, and weekend getaways. Looking for an active partner.',
        'city' => 'Palo Alto',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.4419,
        'longitude' => -122.1430,
        'age_range_min' => 26,
        'age_range_max' => 36,
        'max_distance_km' => 60,
        'height' => 175,
        'interests' => json_encode(['fitness', 'hiking', 'finance', 'travel', 'skiing']),
        'religion' => 'Christian',
        'education_level' => 'MBA',
        'occupation' => 'Financial Analyst',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Socially',
        'pet_preference' => 'No pets',
        'looking_for' => 'Long-term relationship',
        'languages_spoken' => json_encode(['English', 'Korean']),
        'lifestyle' => json_encode(['Active', 'Professional', 'Ambitious'])
    ],
    [
        'name' => 'Rachel Green',
        'email' => 'rachel.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567005',
        'date_of_birth' => '1996-11-12',
        'gender' => 'female',
        'interested_in' => 'male',
        'bio' => 'Medical student with a passion for helping others. Love books, board games, and cozy nights in.',
        'city' => 'Berkeley',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.8715,
        'longitude' => -122.2730,
        'age_range_min' => 24,
        'age_range_max' => 34,
        'max_distance_km' => 25,
        'height' => 170,
        'interests' => json_encode(['medicine', 'reading', 'board games', 'volunteering', 'classical music']),
        'religion' => 'Jewish',
        'education_level' => 'Graduate Degree',
        'occupation' => 'Medical Student',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Rarely',
        'pet_preference' => 'Love both',
        'looking_for' => 'Long-term relationship',
        'languages_spoken' => json_encode(['English', 'Hebrew']),
        'lifestyle' => json_encode(['Academic', 'Caring', 'Intellectual'])
    ],
    [
        'name' => 'Marcus Johnson',
        'email' => 'marcus.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567006',
        'date_of_birth' => '1991-04-30',
        'gender' => 'male',
        'interested_in' => 'female',
        'bio' => 'Personal trainer and nutrition coach. Passionate about helping people achieve their fitness goals.',
        'city' => 'San Jose',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.3382,
        'longitude' => -121.8863,
        'age_range_min' => 22,
        'age_range_max' => 32,
        'max_distance_km' => 45,
        'height' => 185,
        'interests' => json_encode(['fitness', 'nutrition', 'basketball', 'swimming', 'motivation']),
        'religion' => 'Christian',
        'education_level' => 'Associate Degree',
        'occupation' => 'Personal Trainer',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Rarely',
        'pet_preference' => 'Love dogs',
        'looking_for' => 'Both casual and serious',
        'languages_spoken' => json_encode(['English']),
        'lifestyle' => json_encode(['Active', 'Health-conscious', 'Motivational'])
    ],
    [
        'name' => 'Aria Patel',
        'email' => 'aria.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567007',
        'date_of_birth' => '1994-09-25',
        'gender' => 'female',
        'interested_in' => 'male',
        'bio' => 'Data scientist who loves solving complex problems. Enjoy dancing, spicy food, and exploring new cultures.',
        'city' => 'Mountain View',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 37.3861,
        'longitude' => -122.0839,
        'age_range_min' => 27,
        'age_range_max' => 37,
        'max_distance_km' => 35,
        'height' => 162,
        'interests' => json_encode(['data science', 'dancing', 'cooking', 'culture', 'languages']),
        'religion' => 'Hindu',
        'education_level' => 'Master\'s Degree',
        'occupation' => 'Data Scientist',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Socially',
        'pet_preference' => 'No preference',
        'looking_for' => 'Long-term relationship',
        'languages_spoken' => json_encode(['English', 'Hindi', 'Gujarati']),
        'lifestyle' => json_encode(['Analytical', 'Cultural', 'Social'])
    ],
    [
        'name' => 'Tyler Brown',
        'email' => 'tyler.discovery@lovebirds.com',
        'password' => 'TestPassword123!',
        'phone_number' => '+1234567008',
        'date_of_birth' => '1989-01-08',
        'gender' => 'male',
        'interested_in' => 'female',
        'bio' => 'Environmental lawyer fighting for a sustainable future. Love nature, rock climbing, and live music.',
        'city' => 'Santa Cruz',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 36.9741,
        'longitude' => -122.0308,
        'age_range_min' => 25,
        'age_range_max' => 38,
        'max_distance_km' => 80,
        'height' => 178,
        'interests' => json_encode(['environment', 'law', 'rock climbing', 'music', 'nature']),
        'religion' => 'Agnostic',
        'education_level' => 'Law Degree',
        'occupation' => 'Environmental Lawyer',
        'smoking_habit' => 'Never',
        'drinking_habit' => 'Socially',
        'pet_preference' => 'Love both',
        'looking_for' => 'Long-term relationship',
        'languages_spoken' => json_encode(['English', 'German']),
        'lifestyle' => json_encode(['Eco-conscious', 'Adventurous', 'Justice-oriented'])
    ]
];

echo "👥 Creating " . count($testUsers) . " diverse test users...\n\n";

$successCount = 0;
foreach ($testUsers as $index => $userData) {
    echo "Creating user " . ($index + 1) . ": {$userData['name']}\n";
    
    $response = makeRequest('POST', '/auth/register', $userData);
    
    if ($response['success'] || strpos($response['message'] ?? '', 'already exists') !== false) {
        $successCount++;
        echo "   ✅ Created successfully (or already exists)\n";
    } else {
        echo "   ❌ Failed: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
    
    // Small delay to avoid overwhelming the server
    usleep(500000); // 0.5 seconds
}

echo "\n📊 Summary:\n";
echo "   Total users processed: " . count($testUsers) . "\n";
echo "   Successfully created/verified: {$successCount}\n";
echo "   Failed: " . (count($testUsers) - $successCount) . "\n";

if ($successCount > 0) {
    echo "\n🎉 Test data creation completed successfully!\n";
    echo "The discovery system now has diverse users with:\n";
    echo "   - Various ages (1988-1996 birth years)\n";
    echo "   - Different locations around Bay Area\n";
    echo "   - Diverse interests and preferences\n";
    echo "   - Multiple religions and education levels\n";
    echo "   - Various lifestyle choices\n";
    echo "   - Different relationship goals\n";
    echo "   - Multilingual users\n";
    echo "\n🧪 You can now run the discovery system tests with realistic data!\n";
} else {
    echo "\n❌ Test data creation failed. Please check the server and try again.\n";
}

echo "\n💡 Next steps:\n";
echo "   1. Run 'php test_discovery_system.php' to test all discovery features\n";
echo "   2. The system will now have diverse users to match against\n";
echo "   3. Try different filter combinations to see the advanced matching\n";
