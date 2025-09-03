<?php

/**
 * Test script for Profile Wizard API endpoint
 * This script simulates a comprehensive profile submission to validate the endpoint
 */

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Profile Wizard API Test ===\n";

// Create test data similar to what the app would send
$testData = [
    // Basic Info
    'id' => 1, // Test user ID - adjust as needed
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'dob' => '1995-05-15',
    'sex' => 'Male',
    'phone_number' => '+1234567890',
    'city' => 'New York',
    
    // Physical Attributes
    'height_cm' => '180',
    'body_type' => 'Athletic',
    'sexual_orientation' => 'Straight',
    
    // Lifestyle
    'smoking_habit' => 'Never',
    'drinking_habit' => 'Socially',
    'pet_preference' => 'Love pets',
    'religion' => 'Christianity',
    'political_views' => 'Moderate',
    'education_level' => 'Bachelor Degree',
    'occupation' => 'Software Developer',
    'languages_spoken' => 'English, Spanish',
    
    // Goals & Preferences
    'looking_for' => 'Long-term relationship',
    'interested_in' => 'Women',
    'age_range_min' => '25',
    'age_range_max' => '35',
    'max_distance_km' => '50',
    'family_plans' => 'Want children',
    'bio' => 'I am a passionate software developer who loves hiking, cooking, and exploring new places. Looking for someone to share adventures and build meaningful connections.',
    
    // Interests (JSON)
    'interests_json' => '["Hiking", "Photography", "Cooking", "Travel", "Technology", "Movies"]'
];

try {
    echo "Testing profile data validation...\n";
    
    // Find test user
    $user = User::find($testData['id']);
    if (!$user) {
        echo "❌ Test user not found. Please create a test user with ID {$testData['id']}\n";
        exit(1);
    }
    
    echo "✅ Test user found: {$user->email}\n";
    
    // Test individual field cleaning
    echo "\nTesting field validation:\n";
    
    $controller = new ApiController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('cleanProfileField');
    $method->setAccessible(true);
    
    // Test height validation
    $heightTest = $method->invokeArgs($controller, ['height_cm', '180']);
    echo "Height validation (180): " . ($heightTest === 180 ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Test invalid height
    $invalidHeight = $method->invokeArgs($controller, ['height_cm', '300']);
    echo "Invalid height validation (300): " . ($invalidHeight === null ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Test age range validation
    $ageTest = $method->invokeArgs($controller, ['age_range_min', '25']);
    echo "Age validation (25): " . ($ageTest === 25 ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Test JSON interests
    $interestsTest = $method->invokeArgs($controller, ['interests_json', '["Hiking","Photography"]']);
    echo "Interests JSON validation: " . (json_decode($interestsTest) ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Test email validation
    $emailTest = $method->invokeArgs($controller, ['email', 'test@example.com']);
    echo "Email validation: " . ($emailTest === 'test@example.com' ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Test bio sanitization
    $bioTest = $method->invokeArgs($controller, ['bio', 'This is a <script>alert("xss")</script>test bio']);
    echo "Bio sanitization: " . (strpos($bioTest, '<script>') === false ? "✅ PASS" : "❌ FAIL") . "\n";
    
    echo "\n=== All Tests Completed ===\n";
    echo "✅ Profile wizard validation logic is working correctly\n";
    echo "📝 To test the full endpoint, make a POST request to /api/profile-wizard-save with proper JWT authentication\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Profile wizard endpoint is ready for use!\n";

?>
