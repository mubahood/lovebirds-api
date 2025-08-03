<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserLike;
use App\Models\UserMatch;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users with dating profiles
        $users = [
            [
                'username' => 'alex_heart',
                'email' => 'alex@lovebirds.test',
                'password' => Hash::make('password123'),
                'name' => 'Alex Johnson',
                'bio' => 'Adventure seeker and coffee lover. Looking for someone to explore the world with!',
                'dob' => '1996-01-15', // Age 28
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'phone_number' => '+1234567890',
                'sex' => 'Male',
                'sexual_orientation' => 'Straight',
                'looking_for' => 'Long-term relationship',
                'interests' => 'Travel, Photography, Hiking, Coffee',
                'occupation' => 'Software Engineer',
                'education_level' => 'Bachelor\'s Degree',
                'height_cm' => 183, // 6'0"
                'wants_kids' => 'Yes',
                'smoking_habit' => 'No',
                'drinking_habit' => 'Socially',
                'religion' => 'Agnostic',
                'personality_type' => 'Extrovert',
                'relationship_status' => 'Single',
                'email_verified' => 1,
                'phone_verified' => 1,
                'photo_verified' => 1,
                'subscription_tier' => 'Premium',
                'subscription_expires' => Carbon::now()->addMonth(),
                'online_status' => 'online',
                'last_online_at' => Carbon::now(),
                'completed_profile_pct' => 95,
                'max_distance_km' => 50,
                'age_range_min' => 22,
                'age_range_max' => 35,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'username' => 'sarah_love',
                'email' => 'sarah@lovebirds.test',
                'password' => Hash::make('password123'),
                'name' => 'Sarah Wilson',
                'bio' => 'Yoga instructor and nature enthusiast. Love peaceful moments and meaningful conversations.',
                'dob' => '1998-06-20', // Age 26
                'city' => 'Los Angeles',
                'state' => 'CA',
                'country' => 'USA',
                'latitude' => 34.0522,
                'longitude' => -118.2437,
                'phone_number' => '+1234567891',
                'sex' => 'Female',
                'sexual_orientation' => 'Straight',
                'looking_for' => 'Serious dating',
                'interests' => 'Yoga, Meditation, Reading, Art',
                'occupation' => 'Yoga Instructor',
                'education_level' => 'Master\'s Degree',
                'height_cm' => 168, // 5'6"
                'wants_kids' => 'Maybe',
                'smoking_habit' => 'No',
                'drinking_habit' => 'Rarely',
                'religion' => 'Buddhist',
                'personality_type' => 'Introvert',
                'relationship_status' => 'Single',
                'email_verified' => 1,
                'phone_verified' => 0,
                'photo_verified' => 1,
                'subscription_tier' => 'Basic',
                'subscription_expires' => null,
                'online_status' => 'away',
                'last_online_at' => Carbon::now()->subHours(2),
                'completed_profile_pct' => 85,
                'max_distance_km' => 30,
                'age_range_min' => 25,
                'age_range_max' => 32,
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subHours(2),
            ],
            [
                'username' => 'mike_adventure',
                'email' => 'mike@lovebirds.test',
                'password' => Hash::make('password123'),
                'name' => 'Mike Davis',
                'bio' => 'Outdoor enthusiast and musician. Always up for a new adventure or a quiet night with good music.',
                'dob' => '1992-03-10', // Age 32
                'city' => 'Denver',
                'state' => 'CO',
                'country' => 'USA',
                'latitude' => 39.7392,
                'longitude' => -104.9903,
                'phone_number' => '+1234567892',
                'sex' => 'Male',
                'sexual_orientation' => 'Straight',
                'looking_for' => 'Casual dating',
                'interests' => 'Mountain Biking, Guitar, Rock Climbing, Concerts',
                'occupation' => 'Marketing Manager',
                'education_level' => 'Bachelor\'s Degree',
                'height_cm' => 178, // 5'10"
                'wants_kids' => 'No',
                'has_kids' => 'Yes',
                'kids_count' => 1,
                'smoking_habit' => 'No',
                'drinking_habit' => 'Regularly',
                'religion' => 'Christian',
                'personality_type' => 'Extrovert',
                'relationship_status' => 'Divorced',
                'email_verified' => 1,
                'phone_verified' => 1,
                'photo_verified' => 0,
                'subscription_tier' => 'Premium',
                'subscription_expires' => Carbon::now()->addWeeks(2),
                'online_status' => 'online',
                'last_online_at' => Carbon::now()->subMinutes(30),
                'completed_profile_pct' => 78,
                'max_distance_km' => 75,
                'age_range_min' => 24,
                'age_range_max' => 38,
                'created_at' => Carbon::now()->subWeeks(3),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'username' => 'emma_creative',
                'email' => 'emma@lovebirds.test',
                'password' => Hash::make('password123'),
                'name' => 'Emma Thompson',
                'bio' => 'Artist and foodie. Love creating beautiful things and trying new cuisines around the city.',
                'dob' => '1995-09-14', // Age 29
                'city' => 'Austin',
                'state' => 'TX',
                'country' => 'USA',
                'latitude' => 30.2672,
                'longitude' => -97.7431,
                'phone_number' => '+1234567893',
                'sex' => 'Female',
                'sexual_orientation' => 'Bisexual',
                'looking_for' => 'Open to possibilities',
                'interests' => 'Painting, Cooking, Wine Tasting, Museums',
                'occupation' => 'Graphic Designer',
                'education_level' => 'Bachelor\'s Degree',
                'height_cm' => 163, // 5'4"
                'wants_kids' => 'Not Sure',
                'smoking_habit' => 'Occasionally',
                'drinking_habit' => 'Socially',
                'religion' => 'Spiritual',
                'personality_type' => 'Ambivert',
                'relationship_status' => 'Single',
                'email_verified' => 1,
                'phone_verified' => 1,
                'photo_verified' => 1,
                'subscription_tier' => 'VIP',
                'subscription_expires' => Carbon::now()->addMonths(2),
                'online_status' => 'online',
                'last_online_at' => Carbon::now()->subHours(1),
                'completed_profile_pct' => 92,
                'max_distance_km' => 40,
                'age_range_min' => 26,
                'age_range_max' => 35,
                'created_at' => Carbon::now()->subMonths(2),
                'updated_at' => Carbon::now()->subHours(1),
            ],
            [
                'username' => 'david_tech',
                'email' => 'david@lovebirds.test',
                'password' => Hash::make('password123'),
                'name' => 'David Chen',
                'bio' => 'Tech entrepreneur and fitness enthusiast. Building the future while staying healthy and happy.',
                'dob' => '1989-12-08', // Age 35
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'USA',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'phone_number' => '+1234567894',
                'sex' => 'Male',
                'sexual_orientation' => 'Straight',
                'looking_for' => 'Long-term relationship',
                'interests' => 'Technology, Fitness, Startups, Travel',
                'occupation' => 'CEO',
                'education_level' => 'PhD',
                'height_cm' => 173, // 5'8"
                'wants_kids' => 'Yes',
                'smoking_habit' => 'No',
                'drinking_habit' => 'Socially',
                'religion' => 'None',
                'personality_type' => 'Extrovert',
                'relationship_status' => 'Single',
                'email_verified' => 1,
                'phone_verified' => 1,
                'photo_verified' => 1,
                'subscription_tier' => 'VIP',
                'subscription_expires' => Carbon::now()->addMonths(9),
                'online_status' => 'online',
                'last_online_at' => Carbon::now()->subMinutes(15),
                'completed_profile_pct' => 98,
                'max_distance_km' => 25,
                'age_range_min' => 28,
                'age_range_max' => 40,
                'created_at' => Carbon::now()->subMonths(4),
                'updated_at' => Carbon::now()->subMinutes(15),
            ]
        ];

        // Insert users into database
        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Created ' . count($users) . ' dating users successfully!');

        // Create some likes and matches for testing
        $this->createTestLikesAndMatches();
    }

    private function createTestLikesAndMatches()
    {
        $users = User::where('email', 'like', '%@lovebirds.test')->get();
        
        if ($users->count() < 2) {
            $this->command->warn('Not enough users to create likes and matches');
            return;
        }

        // Map usernames to actual IDs
        $userIds = [];
        foreach ($users as $user) {
            if (str_contains($user->email, 'alex@')) $userIds['alex'] = $user->id;
            if (str_contains($user->email, 'sarah@')) $userIds['sarah'] = $user->id;
            if (str_contains($user->email, 'mike@')) $userIds['mike'] = $user->id;
            if (str_contains($user->email, 'emma@')) $userIds['emma'] = $user->id;
            if (str_contains($user->email, 'david@')) $userIds['david'] = $user->id;
        }

        // Create some likes using actual user IDs
        $likes = [
            ['liker_id' => $userIds['alex'], 'liked_user_id' => $userIds['sarah'], 'type' => 'like'],
            ['liker_id' => $userIds['sarah'], 'liked_user_id' => $userIds['alex'], 'type' => 'like'], // Mutual like - will create match
            ['liker_id' => $userIds['alex'], 'liked_user_id' => $userIds['mike'], 'type' => 'like'],
            ['liker_id' => $userIds['mike'], 'liked_user_id' => $userIds['emma'], 'type' => 'like'],
            ['liker_id' => $userIds['emma'], 'liked_user_id' => $userIds['mike'], 'type' => 'like'], // Mutual like - will create match
            ['liker_id' => $userIds['sarah'], 'liked_user_id' => $userIds['david'], 'type' => 'dislike'], // Dislike
            ['liker_id' => $userIds['david'], 'liked_user_id' => $userIds['alex'], 'type' => 'like'],
        ];

        foreach ($likes as $like) {
            UserLike::create([
                'liker_id' => $like['liker_id'],
                'liked_user_id' => $like['liked_user_id'],
                'type' => $like['type'],
                'status' => 'active',
                'liked_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->command->info('Created test likes and matches successfully!');
    }
}
