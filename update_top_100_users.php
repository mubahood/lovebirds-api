<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "🔄 Starting to update top 100 users with comprehensive dating profiles...\n";

// Realistic names, occupations, bios, and traits for diverse dating profiles
$maleNames = [
    'Alexander', 'Benjamin', 'Christopher', 'Daniel', 'Ethan', 'Felix', 'Gabriel', 'Harrison', 'Isaac', 'Jackson',
    'Kevin', 'Liam', 'Michael', 'Nathan', 'Oliver', 'Parker', 'Quinn', 'Ryan', 'Samuel', 'Tyler',
    'Victor', 'William', 'Xavier', 'Zachary', 'Adrian', 'Blake', 'Cameron', 'David', 'Evan', 'Finn',
    'George', 'Henry', 'Ian', 'James', 'Kyle', 'Lucas', 'Matthew', 'Nicholas', 'Owen', 'Peter',
    'Robert', 'Sebastian', 'Thomas', 'Vincent', 'Wesley', 'Yuki', 'Zane', 'Austin', 'Caleb', 'Derek'
];

$femaleNames = [
    'Amelia', 'Bella', 'Charlotte', 'Diana', 'Emma', 'Faith', 'Grace', 'Hannah', 'Isabella', 'Julia',
    'Kate', 'Luna', 'Maya', 'Natalie', 'Olivia', 'Penelope', 'Riley', 'Sophia', 'Taylor', 'Victoria',
    'Willow', 'Zoe', 'Aria', 'Brooklyn', 'Chloe', 'Delilah', 'Elena', 'Freya', 'Gabriella', 'Hazel',
    'Ivy', 'Jasmine', 'Kiara', 'Layla', 'Mia', 'Nova', 'Paige', 'Quinn', 'Rose', 'Stella',
    'Tessa', 'Uma', 'Violet', 'Winter', 'Ximena', 'Yasmin', 'Zara', 'Aurora', 'Bianca', 'Celeste'
];

$occupations = [
    'Software Engineer', 'Doctor', 'Teacher', 'Marketing Manager', 'Graphic Designer', 'Nurse', 'Lawyer', 
    'Chef', 'Photographer', 'Artist', 'Accountant', 'Veterinarian', 'Architect', 'Writer', 'Musician',
    'Personal Trainer', 'Therapist', 'Real Estate Agent', 'Fashion Designer', 'Data Scientist',
    'Product Manager', 'Social Worker', 'Journalist', 'Consultant', 'Entrepreneur', 'Pilot',
    'Interior Designer', 'Physical Therapist', 'Financial Advisor', 'Video Game Developer',
    'Mental Health Counselor', 'Environmental Scientist', 'Marine Biologist', 'Travel Blogger',
    'Fitness Instructor', 'Event Planner', 'UX Designer', 'Pharmacist', 'Dental Hygienist',
    'Speech Therapist', 'Occupational Therapist', 'Radiologic Technologist', 'Dietitian',
    'Social Media Manager', 'Content Creator', 'Yoga Instructor', 'Life Coach', 'Translator',
    'Research Scientist', 'Investment Banker'
];

$hobbies = [
    'hiking', 'cooking', 'photography', 'reading', 'traveling', 'yoga', 'gaming', 'painting', 'dancing',
    'rock climbing', 'surfing', 'skiing', 'meditation', 'gardening', 'wine tasting', 'cycling',
    'running', 'swimming', 'pottery', 'music production', 'board games', 'volunteering', 'karaoke',
    'baking', 'fishing', 'camping', 'chess', 'martial arts', 'astronomy', 'collecting vintage records',
    'learning languages', 'podcasting', 'standup comedy', 'sailing', 'horseback riding', 'scuba diving',
    'mountain biking', 'crossfit', 'pilates', 'creative writing', 'film making', 'woodworking',
    'jewelry making', 'calligraphy', 'urban exploration', 'bird watching', 'rock collecting',
    'magic tricks', 'beatboxing', 'parkour'
];

$interests = [
    'sustainable living', 'mindfulness', 'entrepreneurship', 'social justice', 'technology trends',
    'climate change activism', 'mental health awareness', 'cultural diversity', 'personal development',
    'financial literacy', 'animal welfare', 'space exploration', 'renewable energy', 'organic farming',
    'minimalism', 'digital nomad lifestyle', 'cryptocurrency', 'artificial intelligence', 'philosophy',
    'psychology', 'neuroscience', 'quantum physics', 'historical documentaries', 'world cuisines',
    'traditional crafts', 'alternative medicine', 'urban planning', 'wildlife conservation',
    'ocean cleanup', 'food security', 'education reform', 'healthcare innovation', 'green technology',
    'social entrepreneurship', 'community building', 'cultural preservation', 'renewable resources',
    'sustainable fashion', 'ethical investing', 'conscious consumption'
];

$bioTemplates = [
    "Adventure seeker who believes life is meant to be lived fully. Love {hobby1} and {hobby2}. Looking for someone who shares my passion for {interest1}.",
    "Passionate about {interest1} and always up for trying new things. When I'm not working as a {occupation}, you'll find me {hobby1} or {hobby2}.",
    "Life's too short for boring conversations! I enjoy {hobby1}, {hobby2}, and discovering new {interest1} spots around the city.",
    "Creative soul with a love for {hobby1} and {interest1}. Seeking genuine connections and meaningful conversations over coffee or wine.",
    "Fitness enthusiast who enjoys {hobby1} and {hobby2}. Believe in living a balanced life filled with {interest1} and good vibes.",
    "Spontaneous traveler with a passion for {interest1}. Love {hobby1} and always ready for the next adventure. Let's explore together!",
    "Foodie at heart who loves {hobby1} and {hobby2}. Deeply interested in {interest1}. Looking for someone to share life's beautiful moments with.",
    "Optimistic and driven professional who values {interest1}. Enjoy {hobby1} on weekends and believe in making every day count.",
    "Nature lover who finds peace in {hobby1} and {hobby2}. Passionate about {interest1} and building a better tomorrow.",
    "Curious mind always learning something new about {interest1}. Love {hobby1} and {hobby2}. Seeking authentic connections."
];

$cities = [
    'Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa', 'Edmonton', 'Mississauga', 'Winnipeg',
    'Quebec City', 'Hamilton', 'Brampton', 'Surrey', 'Laval', 'Halifax', 'London', 'Markham',
    'Vaughan', 'Gatineau', 'Saskatoon', 'Longueuil', 'Burnaby', 'Regina', 'Richmond', 'Richmond Hill',
    'Oakville', 'Burlington', 'Barrie', 'Oshawa', 'Sherbrooke', 'Saguenay', 'Lévis', 'Kelowna',
    'Abbotsford', 'Coquitlam', 'Trois-Rivières', 'Guelph', 'Cambridge', 'Whitby', 'Ajax', 'Langley',
    'Saanich', 'Terrebonne', 'Milton', 'St. Catharines', 'New Westminster', 'Châteauguay', 'Waterloo',
    'Delta', 'Sudbury', 'Thunder Bay'
];

$schools = [
    'University of Toronto', 'University of British Columbia', 'McGill University', 'University of Alberta',
    'McMaster University', 'University of Waterloo', 'Queen\'s University', 'University of Calgary',
    'Simon Fraser University', 'Dalhousie University', 'University of Ottawa', 'Western University',
    'Carleton University', 'York University', 'Concordia University', 'University of Victoria',
    'University of Manitoba', 'University of Saskatchewan', 'Memorial University', 'Ryerson University'
];

$relationshipGoals = [
    'Long-term relationship', 'Something casual', 'New friends', 'Marriage', 'Life partner',
    'Serious dating', 'Open to anything', 'Companionship', 'Exclusive relationship'
];

$ethnicities = [
    'Canadian', 'European', 'Asian', 'African', 'Latin American', 'Middle Eastern', 'Indigenous',
    'Mixed Heritage', 'Caribbean', 'Mediterranean', 'Scandinavian', 'Eastern European'
];

$religions = [
    'Christian', 'Catholic', 'Jewish', 'Muslim', 'Hindu', 'Buddhist', 'Sikh', 'Agnostic',
    'Atheist', 'Spiritual', 'Other', 'Non-religious'
];

$bodyTypes = ['Slim', 'Athletic', 'Average', 'Curvy', 'Muscular', 'Plus-size'];
$heights = [150, 155, 160, 165, 170, 175, 180, 185, 190, 195]; // in cm
$drinkingHabits = ['never', 'socially', 'occasionally', 'regularly'];
$smokingHabits = ['never', 'socially', 'trying to quit', 'regularly'];
$exerciseHabits = ['daily', 'often', 'sometimes', 'never'];

$eyeColors = ['brown', 'blue', 'green', 'hazel', 'gray', 'amber'];
$hairColors = ['black', 'brown', 'blonde', 'red', 'gray', 'other'];
$zodiacSigns = ['aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces'];
$personalityTypes = ['extrovert', 'introvert', 'ambivert', 'creative', 'analytical', 'adventurous', 'calm', 'energetic'];

// Function to generate realistic age
function generateAge() {
    $ageRanges = [
        ['range' => range(18, 25), 'weight' => 20],
        ['range' => range(26, 30), 'weight' => 25],
        ['range' => range(31, 35), 'weight' => 25],
        ['range' => range(36, 45), 'weight' => 20],
        ['range' => range(46, 60), 'weight' => 10]
    ];
    
    $pool = [];
    foreach ($ageRanges as $item) {
        for ($i = 0; $i < $item['weight']; $i++) {
            $pool = array_merge($pool, $item['range']);
        }
    }
    return $pool[array_rand($pool)];
}

// Function to generate bio with placeholders filled
function generateBio($occupation, $hobbies, $interests) {
    global $bioTemplates;
    $template = $bioTemplates[array_rand($bioTemplates)];
    
    $hobby1 = $hobbies[array_rand($hobbies)];
    $hobby2 = $hobbies[array_rand($hobbies)];
    while ($hobby2 === $hobby1) {
        $hobby2 = $hobbies[array_rand($hobbies)];
    }
    
    $interest1 = $interests[array_rand($interests)];
    
    return str_replace(
        ['{occupation}', '{hobby1}', '{hobby2}', '{interest1}'],
        [$occupation, $hobby1, $hobby2, $interest1],
        $template
    );
}

// Update users 1-100
for ($i = 1; $i <= 100; $i++) {
    $user = User::find($i);
    
    if (!$user) {
        echo "❌ User $i not found, skipping...\n";
        continue;
    }
    
    // Determine gender based on ID (alternating with some variation)
    $isEven = $i % 2 === 0;
    $genderVariation = rand(1, 10) > 8; // 20% chance to switch
    $isFemale = $genderVariation ? !$isEven : $isEven;
    
    $gender = $isFemale ? 'Female' : 'Male';
    $namePool = $isFemale ? $femaleNames : $maleNames;
    
    // Assign avatar image (cycle through 1-50.jpg)
    $imageNumber = (($i - 1) % 50) + 1;
    $avatarImage = "images/{$imageNumber}.jpg";
    
    // Generate additional gallery images (2-4 more images per user)
    $galleryImages = [];
    $numGalleryImages = rand(2, 4);
    $usedImages = [$imageNumber];
    
    for ($g = 0; $g < $numGalleryImages; $g++) {
        do {
            $galleryImageNumber = rand(1, 50);
        } while (in_array($galleryImageNumber, $usedImages));
        
        $usedImages[] = $galleryImageNumber;
        $galleryImages[] = "images/{$galleryImageNumber}.jpg";
    }
    
    // Generate comprehensive profile data
    $age = generateAge();
    $name = $namePool[array_rand($namePool)];
    $occupation = $occupations[array_rand($occupations)];
    $city = $cities[array_rand($cities)];
    $school = $schools[array_rand($schools)];
    
    // Generate interests and hobbies (3-5 each)
    $userHobbies = array_rand(array_flip($hobbies), rand(3, 5));
    $userInterests = array_rand(array_flip($interests), rand(3, 5));
    
    $bio = generateBio($occupation, $hobbies, $interests);
    
    $height = $heights[array_rand($heights)];
    $bodyType = $bodyTypes[array_rand($bodyTypes)];
    $ethnicity = $ethnicities[array_rand($ethnicities)];
    $religion = $religions[array_rand($religions)];
    $relationshipGoal = $relationshipGoals[array_rand($relationshipGoals)];
    
    $drinking = $drinkingHabits[array_rand($drinkingHabits)];
    $smoking = $smokingHabits[array_rand($smokingHabits)];
    $exercise = $exerciseHabits[array_rand($exerciseHabits)];
    
    // Generate location (latitude/longitude for Canadian cities)
    $locations = [
        'Toronto' => ['lat' => 43.6532, 'lng' => -79.3832],
        'Vancouver' => ['lat' => 49.2827, 'lng' => -123.1207],
        'Montreal' => ['lat' => 45.5017, 'lng' => -73.5673],
        'Calgary' => ['lat' => 51.0447, 'lng' => -114.0719],
        'Ottawa' => ['lat' => 45.4215, 'lng' => -75.6972],
    ];
    $defaultLocation = $locations['Toronto'];
    $location = $locations[$city] ?? $defaultLocation;
    
    // Add small random variation to coordinates
    $lat = $location['lat'] + (rand(-100, 100) / 10000);
    $lng = $location['lng'] + (rand(-100, 100) / 10000);
    
    // Create email from name and ID
    $email = strtolower(str_replace(' ', '.', $name)) . '.' . $i . '@lovebirds.test';
    
    // Update user record
    $updateData = [
        'name' => $name,
        'email' => $email,
        'password' => Hash::make('password123'), // Default password for testing
        'first_name' => explode(' ', $name)[0],
        'last_name' => explode(' ', $name)[1] ?? '',
        'sex' => $gender,
        'dob' => Carbon::now()->subYears($age)->format('Y-m-d'),
        'address' => $city . ', Canada',
        'phone_number' => '+1' . rand(4160000000, 7809999999),
        'avatar' => $avatarImage,
        'profile_photos' => json_encode($galleryImages),
        
        // Dating-specific fields
        'bio' => $bio,
        'tagline' => $hobbies[array_rand($hobbies)] . ' enthusiast',
        'occupation' => $occupation,
        'education_level' => $school,
        'height_cm' => $height,
        'body_type' => $bodyType,
        'ethnicity' => $ethnicity,
        'religion' => $religion,
        'relationship_type' => $relationshipGoal,
        'drinking_habit' => $drinking,
        'smoking_habit' => $smoking,
        'exercise_frequency' => $exercise,
        
        // Location
        'latitude' => $lat,
        'longitude' => $lng,
        'city' => $city,
        'current_city' => $city,
        'country' => 'Canada',
        
        // Interests and preferences
        'interests' => json_encode($userInterests),
        'lifestyle' => json_encode($userHobbies),
        'looking_for' => $gender === 'Male' ? 'Female' : 'Male',
        'age_range_min' => max(18, $age - 8),
        'age_range_max' => min(60, $age + 8),
        'max_distance_km' => rand(5, 50),
        
        // Profile status
        'completed_profile_pct' => rand(80, 100),
        'email_verified' => 1,
        'phone_verified' => rand(0, 10) > 7 ? 1 : 0,
        'photo_verified' => rand(0, 10) > 6 ? 1 : 0,
        'subscription_tier' => rand(0, 10) > 8 ? 'premium' : 'free',
        'online_status' => rand(0, 10) > 6 ? 'online' : 'offline',
        'last_online_at' => Carbon::now()->subMinutes(rand(1, 1440)),
        
        // Activity stats
        'total_likes_sent' => rand(10, 500),
        'likes_received' => rand(15, 600),
        'matches_count' => rand(5, 100),
        'total_messages_sent' => rand(0, 300),
        'profile_views' => rand(50, 2000),
        
        // Additional preferences
        'show_me' => 'everyone',
        'show_age' => 1,
        'show_distance' => 1,
        'account_status' => 'active',
        'onboarding_completed' => 1,
        
        // Additional profile details
        'eye_color' => $eyeColors[array_rand($eyeColors)],
        'hair_color' => $hairColors[array_rand($hairColors)],
        'zodiac_sign' => $zodiacSigns[array_rand($zodiacSigns)],
        'personality_type' => $personalityTypes[array_rand($personalityTypes)],
        'hometown' => $cities[array_rand($cities)],
        'languages_spoken' => json_encode(['English', rand(0, 10) > 5 ? 'French' : 'Spanish']),
        'wants_kids' => rand(0, 10) > 6 ? 'yes' : (rand(0, 10) > 5 ? 'no' : 'maybe'),
        'has_kids' => rand(0, 10) > 8 ? 'yes' : 'no',
        'travel_frequency' => ['rarely', 'sometimes', 'often', 'love to travel'][rand(0, 3)],
        
        'updated_at' => now(),
    ];
    
    try {
        $user->update($updateData);
        echo "✅ Updated user $i: $name ($gender, $age) - $occupation in $city\n";
    } catch (\Exception $e) {
        echo "❌ Error updating user $i: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Successfully updated top 100 users with comprehensive dating profiles!\n";
echo "📊 Profile includes:\n";
echo "   - Realistic names, ages, and photos\n";
echo "   - Detailed bios and occupations\n";
echo "   - Interests, hobbies, and preferences\n";
echo "   - Location data for Canadian cities\n";
echo "   - Gallery images from your 1-50.jpg collection\n";
echo "   - Dating preferences and activity stats\n";
echo "   - Premium and verification status\n";
echo "\n✨ Ready for comprehensive dating app testing!\n";

?>
