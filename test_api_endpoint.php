<?php
// Test API endpoint that ProfileSetupWizardScreen uses
$url = 'http://localhost:8888/lovebirds-api/public/api/api/User';

// Sample data that ProfileSetupWizardScreen would send
$postData = [
    'id' => 1, // Test user ID
    'logged_in_user_id' => 1,
    'bio' => 'This is a test bio from API test',
    'height_cm' => '180',
    'body_type' => 'Athletic',
    'interests' => '["Travel", "Music", "Sports"]',
    'lifestyle' => '["Active", "Social"]',
    'wants_kids' => 'Yes',
    'has_kids' => 'No',
    'relationship_type' => 'Serious',
    'education_level' => 'Bachelor',
    'occupation' => 'Software Developer',
    'smoking_habit' => 'Never',
    'drinking_habit' => 'Socially',
    'exercise_frequency' => 'Often',
    'looking_for' => 'Long-term relationship',
    'interested_in' => 'Women',
    'age_range_min' => '25',
    'age_range_max' => '35',
    'max_distance_km' => '50'
];

// Initialize cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
]);

echo "Testing ProfileSetupWizardScreen API endpoint...\n";
echo "URL: $url\n";
echo "Method: POST\n";
echo "Data: " . json_encode($postData, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "❌ cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    
    $responseData = json_decode($response, true);
    
    if ($httpCode == 200 && isset($responseData['code']) && $responseData['code'] == 1) {
        echo "\n✅ SUCCESS: ProfileSetupWizardScreen API endpoint is working!\n";
        echo "Message: " . ($responseData['message'] ?? 'No message') . "\n";
    } else {
        echo "\n❌ API Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }
}

curl_close($ch);
?>
