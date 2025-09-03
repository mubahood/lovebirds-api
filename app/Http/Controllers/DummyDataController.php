<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DummyDataController extends Controller
{
    // African countries and cities
    private $africanCountries = [
        'Nigeria' => ['Lagos', 'Abuja', 'Kano', 'Ibadan', 'Port Harcourt', 'Benin City', 'Maiduguri', 'Zaria', 'Aba', 'Jos'],
        'Kenya' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale', 'Garissa', 'Kakamega'],
        'Uganda' => ['Kampala', 'Gulu', 'Lira', 'Mbarara', 'Jinja', 'Busia', 'Iganga', 'Mukono', 'Kasese', 'Kabale'],
        'Tanzania' => ['Dar es Salaam', 'Dodoma', 'Mwanza', 'Arusha', 'Mbeya', 'Morogoro', 'Tabora', 'Kigoma', 'Bukoba', 'Iringa'],
        'Ghana' => ['Accra', 'Kumasi', 'Tamale', 'Takoradi', 'Cape Coast', 'Tema', 'Koforidua', 'Sunyani', 'Ho', 'Wa'],
        'South Africa' => ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria', 'Port Elizabeth', 'Bloemfontein', 'East London', 'Pietermaritzburg', 'Polokwane', 'Rustenburg'],
        'Rwanda' => ['Kigali', 'Butare', 'Gitarama', 'Ruhengeri', 'Gisenyi', 'Cyangugu', 'Kibungo', 'Byumba', 'Kibuye', 'Gikongoro'],
        'Ethiopia' => ['Addis Ababa', 'Dire Dawa', 'Mekelle', 'Gondar', 'Dessie', 'Jimma', 'Jijiga', 'Shashamane', 'Bahir Dar', 'Hawassa'],
    ];

    private $africanNames = [
        'male_first' => ['Kwame', 'Kofi', 'Olu', 'Chidi', 'Emeka', 'Tunde', 'Segun', 'Femi', 'Kola', 'Biodun', 'Wale', 'Dele', 'Kayode', 'Bayo', 'Lanre', 'Gbenga', 'Rotimi', 'Folarin', 'Babatunde', 'Olumide', 'Samuel', 'David', 'Michael', 'Emmanuel', 'Joseph', 'Daniel', 'John', 'Paul', 'Peter', 'James', 'Kamau', 'Mwangi', 'Kariuki', 'Njoroge', 'Kimani', 'Wanjiku', 'Kiprotich', 'Kipchoge', 'Mutua', 'Musyoka'],
        'female_first' => ['Amara', 'Kemi', 'Funmi', 'Bisi', 'Yemi', 'Tola', 'Shade', 'Bukola', 'Ronke', 'Sade', 'Folake', 'Bunmi', 'Dupe', 'Kehinde', 'Taiwo', 'Adunni', 'Bolanle', 'Modupe', 'Jumoke', 'Bisola', 'Grace', 'Faith', 'Joy', 'Peace', 'Mercy', 'Blessing', 'Gift', 'Love', 'Hope', 'Patience', 'Wanjiku', 'Njeri', 'Wairimu', 'Nyokabi', 'Wangari', 'Wanjiru', 'Mumbi', 'Gathoni', 'Muthoni', 'Njambi'],
        'last' => ['Adebayo', 'Ogundimu', 'Olumide', 'Fashola', 'Ogbonnaya', 'Nwosu', 'Okoro', 'Okafor', 'Eze', 'Chukwu', 'Afolabi', 'Babatunde', 'Oduya', 'Salami', 'Ibrahim', 'Mohammed', 'Abubakar', 'Usman', 'Aliyu', 'Garba', 'Kamau', 'Mwangi', 'Kariuki', 'Njoroge', 'Kimani', 'Wanjiku', 'Kiprotich', 'Kipchoge', 'Mutua', 'Musyoka', 'Mwamba', 'Kasongo', 'Kabila', 'Tshisekedi', 'Lumumba', 'Kagame', 'Museveni', 'Kenyatta', 'Mandela', 'Tutu']
    ];

    private $africanInterests = [
        'Afrobeat Music', 'Highlife Music', 'Jollof Rice Cooking', 'Traditional Dancing', 'Drumming',
        'Football (Soccer)', 'Basketball', 'Athletics', 'Boxing', 'Wrestling',
        'Nollywood Movies', 'African Literature', 'Poetry', 'Storytelling', 'Art & Crafts',
        'Church Activities', 'Community Service', 'Volunteer Work', 'Social Justice',
        'Business & Entrepreneurship', 'Technology', 'Fashion Design', 'Photography',
        'Traveling within Africa', 'Cultural Festivals', 'Traditional Cuisine', 'Farming',
        'Education & Teaching', 'Healthcare', 'Engineering', 'Law & Justice',
        'Music Production', 'DJ-ing', 'Event Planning', 'Beauty & Makeup',
        'Fitness & Gym', 'Yoga', 'Running', 'Swimming', 'Cycling'
    ];

    private $africanOccupations = [
        'Software Developer', 'Teacher', 'Nurse', 'Doctor', 'Lawyer', 'Engineer',
        'Entrepreneur', 'Business Owner', 'Marketing Manager', 'Sales Executive',
        'Accountant', 'Financial Analyst', 'Banker', 'Insurance Agent',
        'Government Worker', 'Civil Servant', 'Police Officer', 'Military Officer',
        'Journalist', 'Media Producer', 'Radio Presenter', 'TV Host',
        'Fashion Designer', 'Graphic Designer', 'Photographer', 'Artist',
        'Chef', 'Restaurant Owner', 'Event Planner', 'Tour Guide',
        'Farmer', 'Agricultural Officer', 'Veterinarian', 'Environmental Scientist',
        'Pastor', 'Imam', 'Religious Leader', 'Community Leader',
        'Student', 'University Lecturer', 'Researcher', 'Consultant'
    ];

    private $africanLanguages = [
        'English', 'Swahili', 'Yoruba', 'Igbo', 'Hausa', 'Amharic', 'Oromo',
        'Kikuyu', 'Luo', 'Luganda', 'Kinyarwanda', 'Shona', 'Ndebele',
        'Zulu', 'Xhosa', 'Afrikaans', 'Twi', 'Ga', 'Ewe', 'Fante',
        'Wolof', 'Mandinka', 'Fulani', 'Arabic', 'French', 'Portuguese'
    ];

    private $africanUniversities = [
        'University of Lagos', 'University of Ibadan', 'Obafemi Awolowo University',
        'University of Nigeria, Nsukka', 'Ahmadu Bello University',
        'University of Nairobi', 'Kenyatta University', 'Moi University',
        'Makerere University', 'Kyambogo University', 'Uganda Christian University',
        'University of Dar es Salaam', 'Sokoine University', 'Mzumbe University',
        'University of Ghana', 'Kwame Nkrumah University', 'University of Cape Coast',
        'University of Cape Town', 'University of Witwatersrand', 'Stellenbosch University',
        'University of Rwanda', 'Addis Ababa University', 'Jimma University'
    ];

    public function generateDummyData(Request $request)
    {
        try {
            // Get latest 150 users
            $latest_users = User::where([])
                ->orderBy('id', 'asc')
                ->limit(150)
                ->get();

            $updated_count = 0;
            
            // Generate avatar images array
            $female_avatars = [];
            $male_avatars = [];
            
            for ($x = 1; $x < 51; $x++) {
                $img = 'images/' . $x . '.jpg';
                if ($x < 26) {
                    $female_avatars[] = $img;
                } else {
                    $male_avatars[] = $img;
                }
            }

            foreach ($latest_users as $user) {
                // Randomly assign gender if not set
                $gender = $user->sex ?: (rand(0, 1) ? 'Male' : 'Female');
                
                // Select appropriate name and avatar based on gender
                if ($gender === 'Female') {
                    $first_name = $this->africanNames['female_first'][array_rand($this->africanNames['female_first'])];
                    $avatar = $female_avatars[array_rand($female_avatars)];
                } else {
                    $first_name = $this->africanNames['male_first'][array_rand($this->africanNames['male_first'])];
                    $avatar = $male_avatars[array_rand($male_avatars)];
                }
                
                $last_name = $this->africanNames['last'][array_rand($this->africanNames['last'])];
                
                // Select random African country and city
                $country = array_rand($this->africanCountries);
                $cities = $this->africanCountries[$country];
                $city = $cities[array_rand($cities)];
                
                // Generate age between 18-45
                $age = rand(18, 45);
                $dob = Carbon::now()->subYears($age)->subDays(rand(1, 365))->format('Y-m-d');
                
                // Generate coordinates for the city (approximate)
                $coordinates = $this->getCityCoordinates($country, $city);
                
                // Select random interests (3-7 interests)
                $interests = array_rand(array_flip($this->africanInterests), rand(3, 7));
                
                // Select random languages (2-4 languages)
                $languages = array_rand(array_flip($this->africanLanguages), rand(2, 4));
                
                // Generate lifestyle choices
                $lifestyle_options = ['Active', 'Social', 'Adventurous', 'Homebody', 'Spiritual', 'Career-focused', 'Family-oriented', 'Creative', 'Intellectual', 'Outdoorsy'];
                $lifestyle = array_rand(array_flip($lifestyle_options), rand(2, 4));

                // Update user with comprehensive data
                $user->update([
                    // Basic Info
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'name' => $first_name . ' ' . $last_name,
                    'avatar' => $avatar,
                    'sex' => $gender,
                    'dob' => $dob,
                    'phone_number' => $this->generateAfricanPhoneNumber($country),
                    
                    // Location
                    'country' => $country,
                    'city' => $city,
                    'current_city' => $city,
                    'latitude' => $coordinates['lat'],
                    'longitude' => $coordinates['lng'],
                    
                    // Profile Details
                    'bio' => $this->generateAfricanBio($gender, $first_name, $interests),
                    'tagline' => $this->generateTagline($gender),
                    'sexual_orientation' => $this->getRandomValue(['Straight', 'Gay', 'Bisexual'], [85, 10, 5]),
                    'height_cm' => $gender === 'Male' ? rand(165, 195) : rand(155, 180),
                    'body_type' => $this->getRandomValue(['Slim', 'Average', 'Athletic', 'Curvy', 'Plus Size'], [20, 40, 25, 10, 5]),
                    'eye_color' => $this->getRandomValue(['Brown', 'Dark Brown', 'Black', 'Hazel'], [60, 25, 10, 5]),
                    'hair_color' => $this->getRandomValue(['Black', 'Dark Brown', 'Brown'], [70, 20, 10]),
                    'ethnicity' => $this->getEthnicityByCountry($country),
                    
                    // Dating Preferences
                    'looking_for' => $this->getRandomValue(['Serious Relationship', 'Casual Dating', 'Friendship', 'Marriage'], [45, 25, 15, 15]),
                    'interested_in' => $gender === 'Male' ? 'Female' : ($gender === 'Female' ? 'Male' : 'Both'),
                    'age_range_min' => max(18, $age - rand(3, 8)),
                    'age_range_max' => min(50, $age + rand(5, 12)),
                    'max_distance_km' => rand(10, 100),
                    'relationship_status' => $this->getRandomValue(['Single', 'Divorced', 'Widowed', 'Never Married'], [60, 15, 5, 20]),
                    'relationship_type' => $this->getRandomValue(['Serious', 'Casual', 'Open to Both'], [50, 20, 30]),
                    
                    // Lifestyle
                    'smoking_habit' => $this->getRandomValue(['Never', 'Occasionally', 'Socially', 'Regularly'], [70, 15, 10, 5]),
                    'drinking_habit' => $this->getRandomValue(['Never', 'Occasionally', 'Socially', 'Regularly'], [30, 35, 30, 5]),
                    'exercise_frequency' => $this->getRandomValue(['Daily', 'Often', 'Sometimes', 'Rarely', 'Never'], [15, 25, 35, 20, 5]),
                    'pet_preference' => $this->getRandomValue(['Love pets', 'Like pets', 'Allergic to pets', 'No preference'], [40, 35, 10, 15]),
                    'religion' => $this->getRandomValue(['Christianity', 'Islam', 'Traditional', 'Other', 'Not religious'], [60, 25, 8, 4, 3]),
                    'political_views' => $this->getRandomValue(['Liberal', 'Conservative', 'Moderate', 'Not political'], [25, 30, 35, 10]),
                    
                    // Personal Details
                    'wants_kids' => $this->getRandomValue(['Yes', 'No', 'Maybe', 'Already have kids'], [45, 20, 25, 10]),
                    'has_kids' => $this->getRandomValue(['No', 'Yes'], [75, 25]),
                    'kids_count' => rand(0, 3),
                    'personality_type' => $this->getRandomValue(['Extrovert', 'Introvert', 'Ambivert'], [40, 30, 30]),
                    'communication_style' => $this->getRandomValue(['Texting', 'Calling', 'Video calls', 'In person'], [35, 25, 20, 20]),
                    'first_date_preference' => $this->getRandomValue(['Coffee/Tea', 'Dinner', 'Activity', 'Drinks', 'Lunch'], [30, 25, 20, 15, 10]),
                    
                    // Education & Career
                    'education_level' => $this->getRandomValue(['University', 'College', 'High School', 'Masters', 'PhD'], [40, 25, 20, 12, 3]),
                    'occupation' => $this->africanOccupations[array_rand($this->africanOccupations)],
                    
                    // Languages & Culture
                    'languages_spoken' => json_encode($languages),
                    'languages_fluent' => json_encode(array_slice($languages, 0, 2)),
                    'cultural_background' => $this->getCulturalBackground($country),
                    
                    // Interests & Lifestyle
                    'interests' => json_encode($interests),
                    'lifestyle' => json_encode($lifestyle),
                    'travel_frequency' => $this->getRandomValue(['Often', 'Sometimes', 'Rarely', 'Never'], [20, 40, 30, 10]),
                    'zodiac_sign' => $this->getZodiacSign($dob),
                    
                    // Profile Settings
                    'profile_visibility' => 'Public',
                    'show_age' => 'Yes',
                    'show_distance' => 'Yes',
                    'last_seen_visibility' => $this->getRandomValue(['Yes', 'No'], [70, 30]),
                    'read_receipts' => $this->getRandomValue(['Yes', 'No'], [60, 40]),
                    'typing_indicator' => 'Yes',
                    
                    // Account Status
                    'account_status' => 'Active',
                    'email_verified' => 'Yes',
                    'phone_verified' => $this->getRandomValue(['Yes', 'No'], [80, 20]),
                    'photo_verified' => $this->getRandomValue(['Yes', 'No'], [60, 40]),
                    'identity_verified' => $this->getRandomValue(['Yes', 'No'], [40, 60]),
                    'onboarding_completed' => 'Yes',
                    
                    // Activity Stats
                    'profile_views' => rand(10, 500),
                    'likes_received' => rand(5, 200),
                    'matches_count' => rand(2, 50),
                    'total_likes_sent' => rand(20, 300),
                    'total_messages_sent' => rand(10, 150),
                    'total_profile_visits' => rand(50, 800),
                    'completed_profile_pct' => rand(75, 100),
                    
                    // Premium Features
                    'super_likes_remaining' => rand(0, 5),
                    'credits_balance' => rand(0, 100),
                    'subscription_status' => $this->getRandomValue(['free', 'premium', 'plus'], [70, 20, 10]),
                    
                    // Privacy & Legal
                    'terms_of_service_accepted' => 'Yes',
                    'privacy_policy_accepted' => 'Yes',
                    'community_guidelines_accepted' => 'Yes',
                    'marketing_emails_consent' => $this->getRandomValue(['Yes', 'No'], [60, 40]),
                    'data_processing_consent' => 'Yes',
                    'content_moderation_consent' => 'Yes',
                    'terms_accepted_date' => Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s'),
                    'privacy_accepted_date' => Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s'),
                    'guidelines_accepted_date' => Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s'),
                    
                    // Notification Settings
                    'push_notifications' => 'Yes',
                    'email_notifications' => $this->getRandomValue(['Yes', 'No'], [70, 30]),
                    'content_filtering' => 'On',
                    'safe_mode' => 'On',
                    'location_sharing' => $this->getRandomValue(['Yes', 'No'], [80, 20]),
                    'analytics_consent' => 'Yes',
                    'crash_reporting' => 'Yes',
                    
                    // Matching Settings
                    'matching_score_threshold' => number_format(rand(50, 90) / 100, 2),
                    'show_me' => $gender === 'Male' ? 'Female' : ($gender === 'Female' ? 'Male' : 'Both'),
                    'distance_preference' => rand(10, 100),
                    
                    // Timestamps
                    'last_online_at' => Carbon::now()->subMinutes(rand(1, 10080))->format('Y-m-d H:i:s'), // Within last week
                    'profile_created_at' => Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s'),
                    'last_profile_update' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
                    'last_password_change' => Carbon::now()->subDays(rand(30, 180))->format('Y-m-d H:i:s'),
                    
                    // Profile Photos (JSON array of multiple photos)
                    'profile_photos' => json_encode([$avatar, $this->getAdditionalPhoto($gender)]),
                    
                    // Additional Data
                    'deal_breakers' => $this->generateDealBreakers(),
                    'ideal_partner' => $this->generateIdealPartner($gender),
                    'date_ideas' => $this->generateAfricanDateIdeas(),
                ]);

                $updated_count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updated_count} users with comprehensive African dummy data!",
                'updated_count' => $updated_count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating dummy data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateAfricanPhoneNumber($country)
    {
        $country_codes = [
            'Nigeria' => '+234',
            'Kenya' => '+254',
            'Uganda' => '+256',
            'Tanzania' => '+255',
            'Ghana' => '+233',
            'South Africa' => '+27',
            'Rwanda' => '+250',
            'Ethiopia' => '+251'
        ];
        
        $code = $country_codes[$country] ?? '+234';
        return $code . rand(700000000, 999999999);
    }

    private function getCityCoordinates($country, $city)
    {
        // Approximate coordinates for major African cities
        $coordinates = [
            'Lagos' => ['lat' => 6.5244, 'lng' => 3.3792],
            'Nairobi' => ['lat' => -1.2921, 'lng' => 36.8219],
            'Kampala' => ['lat' => 0.3476, 'lng' => 32.5825],
            'Accra' => ['lat' => 5.6037, 'lng' => -0.1870],
            'Cape Town' => ['lat' => -33.9249, 'lng' => 18.4241],
            'Kigali' => ['lat' => -1.9441, 'lng' => 30.0619],
            'Addis Ababa' => ['lat' => 9.1450, 'lng' => 38.7578],
        ];
        
        if (isset($coordinates[$city])) {
            return $coordinates[$city];
        }
        
        // Default to approximate coordinates with some randomness
        return [
            'lat' => rand(-35, 15) + (rand(0, 9999) / 10000),
            'lng' => rand(-20, 50) + (rand(0, 9999) / 10000)
        ];
    }

    private function generateAfricanBio($gender, $name, $interests)
    {
        $templates = [
            "Hello! I'm {$name}, a vibrant person from beautiful Africa 🌍. I love " . implode(', ', array_slice($interests, 0, 3)) . ". Looking to meet someone special who shares my passion for life and African culture! 💕",
            "African {$gender} with a heart full of love ❤️. I enjoy " . implode(' and ', array_slice($interests, 0, 2)) . ". Family is everything to me. Let's create beautiful memories together! 🥰",
            "Proudly African! 🇺🇬 I'm passionate about " . implode(', ', array_slice($interests, 0, 3)) . ". Seeking genuine connection with someone who values honesty, respect, and love. Ubuntu! 🤝",
            "Life is beautiful and I'm here to share it with someone special! I love " . implode(' & ', array_slice($interests, 0, 2)) . ". African by birth, global by nature. Let's explore life together! ✨"
        ];
        
        return $templates[array_rand($templates)];
    }

    private function generateTagline($gender)
    {
        $taglines = [
            "African Queen looking for her King 👑",
            "Gentleman seeking his African Princess 💎",
            "Living life with Ubuntu spirit 🌍",
            "Proudly African, globally minded 🌟",
            "Love, laugh, live African! ❤️",
            "Seeking genuine connection 💕",
            "African beauty inside and out ✨",
            "Strong African roots, bigger dreams 🌳",
            "Family first, love always 👨‍👩‍👧‍👦",
            "Dancing through life with joy 💃🕺"
        ];
        
        return $taglines[array_rand($taglines)];
    }

    private function getEthnicityByCountry($country)
    {
        $ethnicities = [
            'Nigeria' => ['Yoruba', 'Igbo', 'Hausa', 'Fulani', 'Ijaw', 'Kanuri', 'Ibibio', 'Tiv'],
            'Kenya' => ['Kikuyu', 'Luhya', 'Luo', 'Kalenjin', 'Kamba', 'Kisii', 'Meru'],
            'Uganda' => ['Baganda', 'Banyakole', 'Basoga', 'Bakiga', 'Iteso', 'Langi', 'Acholi'],
            'Tanzania' => ['Sukuma', 'Nyamwezi', 'Chagga', 'Hehe', 'Makonde', 'Yao'],
            'Ghana' => ['Akan', 'Mole-Dagbon', 'Ewe', 'Ga-Dangme', 'Gurma', 'Guan'],
            'South Africa' => ['Zulu', 'Xhosa', 'Afrikaner', 'Pedi', 'Tswana', 'Sotho', 'Tsonga'],
            'Rwanda' => ['Hutu', 'Tutsi', 'Twa'],
            'Ethiopia' => ['Oromo', 'Amhara', 'Somali', 'Tigray', 'Sidama', 'Gurage']
        ];
        
        $country_ethnicities = $ethnicities[$country] ?? ['African'];
        return $country_ethnicities[array_rand($country_ethnicities)];
    }

    private function getCulturalBackground($country)
    {
        $backgrounds = [
            'Nigeria' => 'Rich Nigerian heritage with diverse tribal traditions',
            'Kenya' => 'Kenyan culture with strong community values',
            'Uganda' => 'Ugandan traditions rooted in hospitality and respect',
            'Tanzania' => 'Tanzanian culture with Swahili influences',
            'Ghana' => 'Ghanaian heritage with Akan traditions',
            'South Africa' => 'South African rainbow nation culture',
            'Rwanda' => 'Rwandan culture focused on unity and progress',
            'Ethiopia' => 'Ancient Ethiopian traditions and Orthodox heritage'
        ];
        
        return $backgrounds[$country] ?? 'Rich African cultural heritage';
    }

    private function generateDealBreakers()
    {
        $deal_breakers = [
            'Dishonesty and lies',
            'Disrespect towards family',
            'No ambition or goals',
            'Smoking heavily',
            'Excessive drinking',
            'Violence or aggression',
            'Infidelity',
            'Lack of spiritual values',
            'Poor hygiene',
            'Laziness',
            'Disrespect towards women/men',
            'Financial irresponsibility'
        ];
        
        return implode(', ', array_rand(array_flip($deal_breakers), rand(3, 5)));
    }

    private function generateIdealPartner($gender)
    {
        $male_ideals = [
            'A beautiful, intelligent African woman who values family and tradition. Someone who is independent but appreciates partnership. Must be honest, loving, and supportive.',
            'Looking for my African queen - someone who is caring, ambitious, and shares my values. A woman who can cook traditional food and build a strong family together.',
            'Seeking a genuine, God-fearing woman with a kind heart. Someone who respects African culture and wants to build something meaningful together.'
        ];
        
        $female_ideals = [
            'A strong, respectful African man who treats women with dignity. Someone ambitious, family-oriented, and spiritually grounded. A true gentleman and provider.',
            'Looking for my African king - someone who is responsible, loving, and protective. A man who values family, has goals, and treats me like a queen.',
            'Seeking a hardworking, honest man with integrity. Someone who respects tradition, loves God, and wants to build a beautiful family together.'
        ];
        
        if ($gender === 'Male') {
            return $female_ideals[array_rand($female_ideals)];
        } else {
            return $male_ideals[array_rand($male_ideals)];
        }
    }

    private function generateAfricanDateIdeas()
    {
        $ideas = [
            'Beach walk at sunrise',
            'Traditional restaurant dinner',
            'Local cultural festival',
            'Church service together',
            'Cooking traditional food',
            'Dancing to Afrobeat music',
            'Visit to art gallery',
            'Picnic in the park',
            'Movie at local cinema',
            'Shopping at local market',
            'Coffee at nice café',
            'Visit to museum',
            'Boat ride',
            'Community event',
            'Sports event'
        ];
        
        return implode(', ', array_rand(array_flip($ideas), rand(3, 5)));
    }

    private function getZodiacSign($dob)
    {
        $signs = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];
        $month = (int)date('m', strtotime($dob));
        $day = (int)date('d', strtotime($dob));
        
        // Simple zodiac calculation
        $zodiac_dates = [
            1 => [20, 'Aquarius', 'Capricorn'],
            2 => [19, 'Pisces', 'Aquarius'],
            3 => [21, 'Aries', 'Pisces'],
            4 => [20, 'Taurus', 'Aries'],
            5 => [21, 'Gemini', 'Taurus'],
            6 => [21, 'Cancer', 'Gemini'],
            7 => [23, 'Leo', 'Cancer'],
            8 => [23, 'Virgo', 'Leo'],
            9 => [23, 'Libra', 'Virgo'],
            10 => [23, 'Scorpio', 'Libra'],
            11 => [22, 'Sagittarius', 'Scorpio'],
            12 => [22, 'Capricorn', 'Sagittarius']
        ];
        
        return $day >= $zodiac_dates[$month][0] ? $zodiac_dates[$month][1] : $zodiac_dates[$month][2];
    }

    private function getAdditionalPhoto($gender)
    {
        // Return additional photo from the same gender pool
        if ($gender === 'Female') {
            return 'images/' . rand(1, 25) . '.jpg';
        } else {
            return 'images/' . rand(26, 50) . '.jpg';
        }
    }

    private function getRandomValue($options, $weights = null)
    {
        if ($weights === null) {
            return $options[array_rand($options)];
        }
        
        $total = array_sum($weights);
        $random = rand(1, $total);
        $current = 0;
        
        foreach ($options as $index => $option) {
            $current += $weights[$index];
            if ($random <= $current) {
                return $option;
            }
        }
        
        return $options[0];
    }
}
