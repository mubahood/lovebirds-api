<?php

// Simple test file to generate dummy data
// Access via: http://localhost:8888/lovebirds-api/generate_dummy_simple.php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Carbon\Carbon;

echo "<h2>🌍 African Dating App - Dummy Data Generator</h2>";
echo "<p>Generating comprehensive African user profiles...</p>";

try {
    // African data arrays
    $africanCountries = [
        'Nigeria' => ['Lagos', 'Abuja', 'Kano', 'Ibadan', 'Port Harcourt'],
        'Kenya' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret'],
        'Uganda' => ['Kampala', 'Gulu', 'Lira', 'Mbarara', 'Jinja'],
        'Ghana' => ['Accra', 'Kumasi', 'Tamale', 'Takoradi', 'Cape Coast'],
        'South Africa' => ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria', 'Port Elizabeth']
    ];

    $maleNames = ['Kwame', 'Kofi', 'Olu', 'Chidi', 'Emeka', 'Tunde', 'Samuel', 'David', 'Michael', 'Joseph'];
    $femaleNames = ['Amara', 'Kemi', 'Funmi', 'Grace', 'Faith', 'Joy', 'Blessing', 'Wanjiku', 'Njeri', 'Mercy'];
    $lastNames = ['Adebayo', 'Ogundimu', 'Kamau', 'Mwangi', 'Nwosu', 'Okoro', 'Ibrahim', 'Afolabi', 'Kimani', 'Mutua'];
    
    $interests = ['Afrobeat Music', 'Football', 'Cooking', 'Dancing', 'Church', 'Business', 'Fashion', 'Photography'];
    $occupations = ['Teacher', 'Nurse', 'Engineer', 'Entrepreneur', 'Student', 'Developer', 'Manager', 'Artist'];

    // Get users to update
    $users = User::orderBy('id', 'asc')->limit(50)->get();
    $updated = 0;

    foreach ($users as $user) {
        // Random gender
        $gender = rand(0, 1) ? 'Male' : 'Female';
        
        // Names based on gender
        $firstName = $gender === 'Male' ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        
        // Random country and city
        $country = array_rand($africanCountries);
        $city = $africanCountries[$country][array_rand($africanCountries[$country])];
        
        // Generate age and DOB
        $age = rand(18, 45);
        $dob = Carbon::now()->subYears($age)->format('Y-m-d');
        
        // Random selections
        $userInterests = array_rand(array_flip($interests), rand(2, 4));
        $occupation = $occupations[array_rand($occupations)];
        
        // Avatar based on gender
        $avatar = $gender === 'Female' ? 'images/' . rand(1, 25) . '.jpg' : 'images/' . rand(26, 50) . '.jpg';
        
        // Update user
        $user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $firstName . ' ' . $lastName,
            'avatar' => $avatar,
            'sex' => $gender,
            'dob' => $dob,
            'country' => $country,
            'city' => $city,
            'current_city' => $city,
            'bio' => "Hello! I'm {$firstName} from beautiful {$city}, {$country}. I love " . implode(', ', $userInterests) . ". Looking for genuine connection! 💕",
            'tagline' => $gender === 'Male' ? "Gentleman from {$country} 🇺🇬" : "African Queen from {$country} 👑",
            'occupation' => $occupation,
            'height_cm' => $gender === 'Male' ? rand(165, 195) : rand(155, 180),
            'body_type' => ['Slim', 'Average', 'Athletic', 'Curvy'][array_rand(['Slim', 'Average', 'Athletic', 'Curvy'])],
            'sexual_orientation' => 'Straight',
            'looking_for' => ['Serious Relationship', 'Marriage', 'Casual Dating'][array_rand(['Serious Relationship', 'Marriage', 'Casual Dating'])],
            'interested_in' => $gender === 'Male' ? 'Female' : 'Male',
            'age_range_min' => max(18, $age - 5),
            'age_range_max' => min(50, $age + 8),
            'relationship_status' => ['Single', 'Never Married', 'Divorced'][array_rand(['Single', 'Never Married', 'Divorced'])],
            'smoking_habit' => 'Never',
            'drinking_habit' => ['Never', 'Occasionally', 'Socially'][array_rand(['Never', 'Occasionally', 'Socially'])],
            'religion' => ['Christianity', 'Islam'][array_rand(['Christianity', 'Islam'])],
            'education_level' => ['University', 'College', 'High School'][array_rand(['University', 'College', 'High School'])],
            'wants_kids' => ['Yes', 'Maybe'][array_rand(['Yes', 'Maybe'])],
            'has_kids' => rand(0, 1) ? 'Yes' : 'No',
            'interests' => json_encode($userInterests),
            'languages_spoken' => json_encode(['English', 'Swahili']),
            'profile_visibility' => 'Public',
            'account_status' => 'Active',
            'email_verified' => 'Yes',
            'onboarding_completed' => 'Yes',
            'profile_views' => rand(10, 200),
            'likes_received' => rand(5, 100),
            'matches_count' => rand(2, 30),
            'completed_profile_pct' => rand(80, 100),
            'terms_of_service_accepted' => 'Yes',
            'privacy_policy_accepted' => 'Yes',
            'community_guidelines_accepted' => 'Yes',
            'last_online_at' => Carbon::now()->subHours(rand(1, 48))->format('Y-m-d H:i:s'),
            'profile_created_at' => Carbon::now()->subDays(rand(1, 180))->format('Y-m-d H:i:s'),
        ]);

        $updated++;
        echo "<p>✅ Updated: {$firstName} {$lastName} from {$city}, {$country}</p>";
    }

    echo "<h3>🎉 SUCCESS!</h3>";
    echo "<p>Updated <strong>{$updated}</strong> users with comprehensive African profiles!</p>";
    echo "<p>All users now have:</p>";
    echo "<ul>";
    echo "<li>✅ Complete names and avatars</li>";
    echo "<li>✅ African locations and cities</li>";
    echo "<li>✅ Realistic bios and taglines</li>";
    echo "<li>✅ Dating preferences and interests</li>";
    echo "<li>✅ Profile completion data</li>";
    echo "<li>✅ Activity statistics</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Access via:</strong> <a href='generate_dummy_simple.php'>Refresh to run again</a></p>";
echo "<p><strong>Advanced version:</strong> <a href='generate-dummy'>Full comprehensive generator</a></p>";
?>
