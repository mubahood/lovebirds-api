<?php

/**
 * Generate Test Data for Photo Likes/Dislikes System
 * Creates realistic test scenarios with multiple users and various like patterns
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UserLike;
use App\Models\UserMatch;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "🧪 GENERATING PHOTO LIKES TEST DATA\n";
echo "===================================\n\n";

// Clean existing test data first
echo "🧹 Cleaning existing test data...\n";
DB::table('user_likes')->truncate();
DB::table('user_matches')->truncate();

// Create test users for comprehensive like testing
$testUsers = [
    [
        'name' => 'Sarah Johnson',
        'email' => 'sarah.test@example.com',
        'first_name' => 'Sarah',
        'last_name' => 'Johnson',
        'username' => 'sarah.test@example.com',
        'password' => Hash::make('123456'),
        'gender' => 'female',
        'interested_in' => 'male',
        'dob' => '1995-03-15',
        'bio' => 'Love hiking, photography, and coffee dates! Looking for someone genuine.',
        'city' => 'Los Angeles',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 34.0522,
        'longitude' => -118.2437,
        'age_range_min' => 25,
        'age_range_max' => 35,
        'account_status' => 'Active',
        'email_verified' => 'Yes',
        'phone_verified' => 'Yes',
        'subscription_tier' => 'Premium',
        'subscription_expires' => now()->addMonths(3),
        'interests' => json_encode(['hiking', 'photography', 'coffee', 'travel', 'movies']),
        'education_level' => 'Bachelor',
        'occupation' => 'Photographer',
        'religion' => 'Christian',
        'smoking_habit' => 'never',
        'drinking_habit' => 'socially'
    ],
    [
        'name' => 'Michael Chen',
        'email' => 'michael.test@example.com',
        'first_name' => 'Michael',
        'last_name' => 'Chen',
        'username' => 'michael.test@example.com',
        'password' => Hash::make('123456'),
        'gender' => 'male',
        'interested_in' => 'female',
        'dob' => '1992-07-20',
        'bio' => 'Software engineer who loves rock climbing and cooking. Always up for an adventure!',
        'city' => 'Los Angeles',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 34.0622,
        'longitude' => -118.2537,
        'age_range_min' => 22,
        'age_range_max' => 32,
        'account_status' => 'Active',
        'email_verified' => 'Yes',
        'phone_verified' => 'Yes',
        'subscription_tier' => 'Free',
        'interests' => json_encode(['programming', 'rock_climbing', 'cooking', 'travel', 'tech']),
        'education_level' => 'Master',
        'occupation' => 'Software Engineer',
        'religion' => 'Buddhist',
        'smoking_habit' => 'never',
        'drinking_habit' => 'occasionally'
    ],
    [
        'name' => 'Emma Wilson',
        'email' => 'emma.test@example.com',
        'first_name' => 'Emma',
        'last_name' => 'Wilson',
        'username' => 'emma.test@example.com',
        'password' => Hash::make('123456'),
        'gender' => 'female',
        'interested_in' => 'male',
        'dob' => '1997-11-08',
        'bio' => 'Art student with a passion for painting and live music. Looking for creative souls!',
        'city' => 'Los Angeles',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 34.0422,
        'longitude' => -118.2337,
        'age_range_min' => 24,
        'age_range_max' => 35,
        'account_status' => 'Active',
        'email_verified' => 'Yes',
        'phone_verified' => 'Yes',
        'subscription_tier' => 'Premium',
        'subscription_expires' => now()->addMonths(1),
        'interests' => json_encode(['art', 'painting', 'music', 'concerts', 'coffee']),
        'education_level' => 'Bachelor',
        'occupation' => 'Artist',
        'religion' => 'Spiritual',
        'smoking_habit' => 'never',
        'drinking_habit' => 'socially'
    ],
    [
        'name' => 'David Rodriguez',
        'email' => 'david.test@example.com',
        'first_name' => 'David',
        'last_name' => 'Rodriguez',
        'username' => 'david.test@example.com',
        'password' => Hash::make('123456'),
        'gender' => 'male',
        'interested_in' => 'female',
        'dob' => '1990-05-12',
        'bio' => 'Fitness trainer and nutrition enthusiast. Love outdoor activities and healthy living.',
        'city' => 'Los Angeles',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 34.0722,
        'longitude' => -118.2637,
        'age_range_min' => 23,
        'age_range_max' => 33,
        'account_status' => 'Active',
        'email_verified' => 'Yes',
        'phone_verified' => 'Yes',
        'subscription_tier' => 'Free',
        'interests' => json_encode(['fitness', 'nutrition', 'hiking', 'yoga', 'health']),
        'education_level' => 'Bachelor',
        'occupation' => 'Fitness Trainer',
        'religion' => 'Catholic',
        'smoking_habit' => 'never',
        'drinking_habit' => 'rarely'
    ],
    [
        'name' => 'Jessica Taylor',
        'email' => 'jessica.test@example.com',
        'first_name' => 'Jessica',
        'last_name' => 'Taylor',
        'username' => 'jessica.test@example.com',
        'password' => Hash::make('123456'),
        'gender' => 'female',
        'interested_in' => 'male',
        'dob' => '1994-09-25',
        'bio' => 'Marketing professional who loves traveling and trying new restaurants. Foodie at heart!',
        'city' => 'Los Angeles',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 34.0322,
        'longitude' => -118.2237,
        'age_range_min' => 26,
        'age_range_max' => 38,
        'account_status' => 'Active',
        'email_verified' => 'Yes',
        'phone_verified' => 'Yes',
        'subscription_tier' => 'Premium',
        'subscription_expires' => now()->addMonths(6),
        'interests' => json_encode(['travel', 'food', 'marketing', 'wine', 'culture']),
        'education_level' => 'Master',
        'occupation' => 'Marketing Manager',
        'religion' => 'Christian',
        'smoking_habit' => 'never',
        'drinking_habit' => 'socially'
    ],
    [
        'name' => 'Alex Thompson',
        'email' => 'alex.test@example.com',
        'first_name' => 'Alex',
        'last_name' => 'Thompson',
        'username' => 'alex.test@example.com',
        'password' => Hash::make('123456'),
        'gender' => 'male',
        'interested_in' => 'female',
        'dob' => '1993-12-03',
        'bio' => 'Musician and sound engineer. Love live music, vinyl records, and deep conversations.',
        'city' => 'Los Angeles',
        'state' => 'California',
        'country' => 'United States',
        'latitude' => 34.0822,
        'longitude' => -118.2737,
        'age_range_min' => 24,
        'age_range_max' => 34,
        'account_status' => 'Active',
        'email_verified' => 'Yes',
        'phone_verified' => 'Yes',
        'subscription_tier' => 'Free',
        'interests' => json_encode(['music', 'vinyl', 'concerts', 'audio', 'creativity']),
        'education_level' => 'Bachelor',
        'occupation' => 'Sound Engineer',
        'religion' => 'Agnostic',
        'smoking_habit' => 'never',
        'drinking_habit' => 'occasionally'
    ]
];

// Create or update users
$userIds = [];
foreach ($testUsers as $userData) {
    $user = User::where('email', $userData['email'])->first();
    if ($user) {
        $user->update($userData);
        echo "✅ Updated user: {$userData['name']}\n";
    } else {
        $user = User::create($userData);
        echo "✅ Created user: {$userData['name']}\n";
    }
    $userIds[$userData['first_name']] = $user->id;
}

echo "\n💖 Creating realistic like patterns...\n";

// Create diverse like scenarios
$likePatterns = [
    // Mutual likes (will create matches)
    ['liker' => 'Sarah', 'liked' => 'Michael', 'type' => 'like'],
    ['liker' => 'Michael', 'liked' => 'Sarah', 'type' => 'like'], // Match!
    
    ['liker' => 'Emma', 'liked' => 'David', 'type' => 'super_like', 'message' => 'Love your fitness journey! You inspire me! 💪'],
    ['liker' => 'David', 'liked' => 'Emma', 'type' => 'like'], // Match!
    
    ['liker' => 'Jessica', 'liked' => 'Alex', 'type' => 'like'],
    ['liker' => 'Alex', 'liked' => 'Jessica', 'type' => 'super_like', 'message' => 'Your travel photos are amazing! Would love to hear your stories! ✈️'], // Match!
    
    // One-sided likes (no matches yet)
    ['liker' => 'Sarah', 'liked' => 'David', 'type' => 'like'],
    ['liker' => 'Sarah', 'liked' => 'Alex', 'type' => 'super_like', 'message' => 'Your music taste is incredible! 🎵'],
    
    ['liker' => 'Michael', 'liked' => 'Emma', 'type' => 'like'],
    ['liker' => 'Michael', 'liked' => 'Jessica', 'type' => 'like'],
    
    ['liker' => 'Emma', 'liked' => 'Alex', 'type' => 'like'],
    ['liker' => 'Emma', 'liked' => 'Michael', 'type' => 'pass'], // Pass
    
    ['liker' => 'David', 'liked' => 'Sarah', 'type' => 'like'],
    ['liker' => 'David', 'liked' => 'Jessica', 'type' => 'like'],
    
    ['liker' => 'Jessica', 'liked' => 'Michael', 'type' => 'like'],
    ['liker' => 'Jessica', 'liked' => 'David', 'type' => 'pass'], // Pass
    
    ['liker' => 'Alex', 'liked' => 'Sarah', 'type' => 'like'],
    ['liker' => 'Alex', 'liked' => 'Emma', 'type' => 'pass'], // Pass
];

foreach ($likePatterns as $pattern) {
    $likerId = $userIds[$pattern['liker']];
    $likedUserId = $userIds[$pattern['liked']];
    
    $likeData = [
        'liker_id' => $likerId,
        'liked_user_id' => $likedUserId,
        'type' => $pattern['type'],
        'status' => 'Active',
        'liked_at' => now()->subMinutes(rand(1, 1440)), // Random time within last 24 hours
        'message' => $pattern['message'] ?? null
    ];
    
    UserLike::create($likeData);
    
    $actionType = $pattern['type'] === 'pass' ? 'passed' : $pattern['type'] . 'd';
    echo "   💝 {$pattern['liker']} {$actionType} {$pattern['liked']}\n";
}

echo "\n🔍 Checking for matches and creating them...\n";

// Process mutual likes to create matches
$mutualLikes = UserLike::select('liker_id', 'liked_user_id')
    ->whereIn('type', ['like', 'super_like'])
    ->where('status', 'Active')
    ->get()
    ->groupBy(function($like) {
        return min($like->liker_id, $like->liked_user_id) . '_' . max($like->liker_id, $like->liked_user_id);
    })
    ->filter(function($group) {
        return $group->count() >= 2; // Both users liked each other
    });

foreach ($mutualLikes as $group) {
    $userIds = $group->pluck('liker_id')->merge($group->pluck('liked_user_id'))->unique();
    if ($userIds->count() === 2) {
        $user1Id = $userIds->first();
        $user2Id = $userIds->last();
        
        // Mark likes as mutual
        UserLike::where(function($query) use ($user1Id, $user2Id) {
            $query->where('liker_id', $user1Id)->where('liked_user_id', $user2Id);
        })->orWhere(function($query) use ($user1Id, $user2Id) {
            $query->where('liker_id', $user2Id)->where('liked_user_id', $user1Id);
        })->update(['is_mutual' => 'Yes']);
        
        // Create match if it doesn't exist
        $existingMatch = UserMatch::where(function($query) use ($user1Id, $user2Id) {
            $query->where('user_id', $user1Id)->where('matched_user_id', $user2Id);
        })->orWhere(function($query) use ($user1Id, $user2Id) {
            $query->where('user_id', $user2Id)->where('matched_user_id', $user1Id);
        })->first();
        
        if (!$existingMatch) {
            UserMatch::create([
                'user_id' => $user1Id,
                'matched_user_id' => $user2Id,
                'status' => 'Active',
                'matched_at' => now(),
                'compatibility_score' => rand(75, 95)
            ]);
            
            $user1 = User::find($user1Id);
            $user2 = User::find($user2Id);
            echo "   💕 Match created: {$user1->first_name} ↔ {$user2->first_name}\n";
        }
    }
}

echo "\n📊 LIKE SYSTEM TEST DATA SUMMARY\n";
echo "================================\n";

$stats = [
    'total_users' => User::count(),
    'total_likes' => UserLike::whereIn('type', ['like', 'super_like'])->count(),
    'total_super_likes' => UserLike::where('type', 'super_like')->count(),
    'total_passes' => UserLike::where('type', 'pass')->count(),
    'total_matches' => UserMatch::count(),
    'mutual_likes' => UserLike::where('is_mutual', 'Yes')->count(),
    'premium_users' => User::where('subscription_tier', 'Premium')->count(),
    'free_users' => User::where('subscription_tier', 'Free')->count()
];

foreach ($stats as $key => $value) {
    $label = ucwords(str_replace('_', ' ', $key));
    echo "📈 {$label}: {$value}\n";
}

echo "\n🎯 TEST SCENARIOS READY:\n";
echo "========================\n";
echo "✅ Mutual likes → Automatic matches\n";
echo "✅ One-sided likes → Waiting for response\n";
echo "✅ Super likes with messages\n";
echo "✅ Pass actions (swipe left)\n";
echo "✅ Mixed subscription types (Free vs Premium)\n";
echo "✅ Diverse user demographics\n";
echo "✅ Various interests and compatibility factors\n";
echo "✅ GPS coordinates for location testing\n\n";

echo "🚀 Ready to test the Photo Likes/Dislikes System!\n";
echo "Run: php test_photo_likes_system.php\n";

?>
