<?php

require_once 'vendor/autoload.php';

use App\Models\User;

echo "=== TESTING PROFILESETUPWIZARDSCREEN BACKEND INTEGRATION ===\n\n";

// Get a test user to verify current data
$user = User::where('email', 'like', 'test%')->first();

if (!$user) {
    echo "❌ No test user found\n";
    exit;
}

echo "✅ Found test user: {$user->email} (ID: {$user->id})\n";
echo "Current profile data:\n";
echo "- Name: {$user->first_name} {$user->last_name}\n";
echo "- Bio: " . ($user->bio ?: 'null') . "\n";
echo "- Height: " . ($user->height_cm ?: 'null') . "\n";
echo "- Body Type: " . ($user->body_type ?: 'null') . "\n";
echo "- Smoking: " . ($user->smoking_habit ?: 'null') . "\n";
echo "- Education: " . ($user->education_level ?: 'null') . "\n";
echo "- Interests: " . ($user->interests ?: 'null') . "\n";
echo "- Looking For: " . ($user->looking_for ?: 'null') . "\n\n";

// Test the API endpoint that ProfileSetupWizardScreen uses
echo "=== Testing API/User endpoint (ProfileSetupWizardScreen backend call) ===\n";

// Simulate ProfileSetupWizardScreen data update
$profileData = [
    'id' => $user->id,
    'first_name' => 'Updated',
    'last_name' => 'TestUser',
    'bio' => 'This is my updated bio from ProfileSetupWizardScreen test',
    'height_cm' => 175,
    'body_type' => 'Athletic',
    'smoking_habit' => 'Never',
    'drinking_habit' => 'Socially',
    'education_level' => 'Bachelor\'s degree',
    'occupation' => 'Software Developer',
    'interests' => '["Technology", "Sports", "Travel"]',
    'looking_for' => 'Long-term relationship',
    'sexual_orientation' => 'Straight',
    'religion' => 'Other',
    'languages_spoken' => '["English", "French"]',
    'age_range_min' => 25,
    'age_range_max' => 35
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/lovebirds-api/api/api/User');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profileData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'logged_in_user_id: ' . $user->id
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
    
    // Verify the data was actually saved
    echo "\n=== Verifying data persistence ===\n";
    $updatedUser = User::find($user->id);
    
    echo "Updated profile data in database:\n";
    echo "- Name: {$updatedUser->first_name} {$updatedUser->last_name}\n";
    echo "- Bio: " . ($updatedUser->bio ?: 'null') . "\n";
    echo "- Height: " . ($updatedUser->height_cm ?: 'null') . "\n";
    echo "- Body Type: " . ($updatedUser->body_type ?: 'null') . "\n";
    echo "- Smoking: " . ($updatedUser->smoking_habit ?: 'null') . "\n";
    echo "- Education: " . ($updatedUser->education_level ?: 'null') . "\n";
    echo "- Interests: " . ($updatedUser->interests ?: 'null') . "\n";
    echo "- Looking For: " . ($updatedUser->looking_for ?: 'null') . "\n";
    
    if ($updatedUser->bio === $profileData['bio'] && 
        $updatedUser->height_cm == $profileData['height_cm'] &&
        $updatedUser->body_type === $profileData['body_type']) {
        echo "\n✅ Data persistence verified! ProfileSetupWizardScreen backend integration is working!\n";
    } else {
        echo "\n❌ Data persistence failed! Some fields were not saved correctly.\n";
    }
    
} else {
    echo "❌ API call failed or returned error.\n";
    if ($responseData && isset($responseData['message'])) {
        echo "Error message: " . $responseData['message'] . "\n";
    }
}

echo "\n✅ ProfileSetupWizardScreen backend integration test completed!\n";
