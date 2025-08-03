<?php
// Test script to verify User model can save dating profile fields
require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

// Test data that ProfileSetupWizardScreen would send
$testData = [
    'id' => 1, // Test user ID
    'bio' => 'This is a test bio for dating profile',
    'height_cm' => '175',
    'body_type' => 'Athletic',
    'interests' => json_encode(['Travel', 'Music', 'Sports']),
    'lifestyle' => json_encode(['Active', 'Social']),
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

echo "Testing User model fillable fields...\n";

// Test 1: Check if User model can mass assign the fields
try {
    $user = User::find(1);
    if ($user) {
        $user->fill($testData);
        $user->save();
        echo "✅ SUCCESS: User model can save dating profile fields\n";
        echo "Updated user bio: " . $user->bio . "\n";
        echo "Updated user height: " . $user->height_cm . "cm\n";
        echo "Updated user interests: " . $user->interests . "\n";
    } else {
        echo "❌ ERROR: Test user (ID: 1) not found\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Create a new user with dating fields
try {
    $newUserData = array_merge($testData, [
        'name' => 'Test Dating User',
        'email' => 'test_dating_' . time() . '@example.com',
        'password' => bcrypt('password123'),
        'first_name' => 'Test',
        'last_name' => 'User'
    ]);
    unset($newUserData['id']); // Remove ID for new user
    
    $newUser = User::create($newUserData);
    echo "✅ SUCCESS: Created new user with dating fields\n";
    echo "New user ID: " . $newUser->id . "\n";
    echo "New user bio: " . $newUser->bio . "\n";
} catch (Exception $e) {
    echo "❌ ERROR creating new user: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
?>
