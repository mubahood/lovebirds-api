<?php

echo "=== TESTING PROFILESETUPWIZARDSCREEN BACKEND INTEGRATION ===\n\n";

// Test the API endpoint that ProfileSetupWizardScreen uses
echo "=== Testing API/api/User endpoint (ProfileSetupWizardScreen backend call) ===\n";

// Simulate ProfileSetupWizardScreen data update for test user ID 6127
$profileData = [
    'id' => 6127,
    'first_name' => 'UpdatedWizard',
    'last_name' => 'TestUser',
    'bio' => 'This is my updated bio from ProfileSetupWizardScreen integration test',
    'height_cm' => 175,
    'body_type' => 'Athletic',
    'smoking_habit' => 'Never',
    'drinking_habit' => 'Socially',
    'education_level' => 'Bachelor\'s degree',
    'occupation' => 'Software Developer',
    'interests' => '["Technology", "Sports", "Travel", "Music"]',
    'looking_for' => 'Long-term relationship',
    'sexual_orientation' => 'Straight',
    'religion' => 'Other',
    'languages_spoken' => '["English", "French", "Spanish"]',
    'age_range_min' => 25,
    'age_range_max' => 35,
    'city' => 'Toronto',
    'lifestyle' => 'Active'
];

echo "Sending profile data:\n";
foreach ($profileData as $key => $value) {
    echo "- $key: $value\n";
}
echo "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/api/User');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profileData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'logged_in_user_id: 6127'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "ProfileSetupWizardScreen API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

$responseData = json_decode($response, true);

if ($responseData && isset($responseData['code']) && $responseData['code'] == 1) {
    echo "✅ API call successful! Profile data was updated.\n";
    echo "✅ ProfileSetupWizardScreen backend integration is working!\n";
    
    if (isset($responseData['data'])) {
        echo "\nReturned user data includes:\n";
        $userData = $responseData['data'];
        
        $checkFields = ['first_name', 'last_name', 'bio', 'height_cm', 'body_type', 'education_level', 'interests'];
        foreach ($checkFields as $field) {
            if (isset($userData[$field])) {
                echo "- $field: " . $userData[$field] . "\n";
            }
        }
    }
    
} else {
    echo "❌ API call failed or returned error.\n";
    if ($responseData && isset($responseData['message'])) {
        echo "Error message: " . $responseData['message'] . "\n";
    }
}

echo "\n=== Testing error scenarios ===\n";

// Test with invalid data to check error handling
$invalidData = [
    'id' => 6127,
    'height_cm' => 'invalid_height', // Invalid height
    'age_range_min' => 100, // Invalid age range
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/api/User');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invalidData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'logged_in_user_id: 6127'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Error scenario API Response (HTTP $httpCode):\n";
echo $response . "\n\n";

echo "✅ ProfileSetupWizardScreen backend integration testing completed!\n";
