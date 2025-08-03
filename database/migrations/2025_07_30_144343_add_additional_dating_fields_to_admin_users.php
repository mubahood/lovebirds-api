<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('admin_users', 'wants_kids')) {
                // Kids & Family Planning
                $table->string('wants_kids')->nullable(); // Yes, No, Maybe, Not Sure
                $table->string('has_kids')->nullable(); // Yes, No
                $table->integer('kids_count')->nullable(); // Number of kids they have
            }
            
            if (!Schema::hasColumn('admin_users', 'interests')) {
                // Additional Profile Info
                $table->text('interests')->nullable(); // JSON array of interests/hobbies
                $table->text('lifestyle')->nullable(); // JSON array of lifestyle choices
                $table->string('relationship_type')->nullable(); // Serious, Casual, Friendship, etc.
                $table->string('relationship_status')->nullable(); // Single, Divorced, Widowed, etc.
            }
            
            if (!Schema::hasColumn('admin_users', 'eye_color')) {
                // Physical Attributes
                $table->string('eye_color')->nullable();
                $table->string('hair_color')->nullable();
                $table->string('ethnicity')->nullable();
            }
            
            if (!Schema::hasColumn('admin_users', 'deal_breakers')) {
                // Additional Preferences
                $table->text('deal_breakers')->nullable(); // JSON array of deal breakers
                $table->text('ideal_partner')->nullable(); // Description of ideal partner
                $table->string('exercise_frequency')->nullable(); // Never, Rarely, Sometimes, Often, Daily
            }
            
            if (!Schema::hasColumn('admin_users', 'personality_type')) {
                // Social & Personality
                $table->string('personality_type')->nullable(); // Introvert, Extrovert, Ambivert
                $table->text('social_media_links')->nullable(); // JSON array of social links
                $table->string('communication_style')->nullable(); // Texting, Calling, Video calls, In person
            }
            
            if (!Schema::hasColumn('admin_users', 'first_date_preference')) {
                // Dating Specific
                $table->string('first_date_preference')->nullable(); // Coffee, Dinner, Activity, etc.
                $table->text('date_ideas')->nullable(); // JSON array of preferred date activities
                $table->string('travel_frequency')->nullable(); // Never, Rarely, Sometimes, Often
                $table->string('distance_preference')->nullable(); // Local, Regional, Long Distance
            }
            
            if (!Schema::hasColumn('admin_users', 'photo_verified')) {
                // Verification & Safety
                $table->string('photo_verified')->nullable()->default('No'); // Yes, No, Pending
                $table->string('identity_verified')->nullable()->default('No'); // Yes, No, Pending
                $table->text('verification_documents')->nullable(); // JSON array of uploaded docs
            }
            
            if (!Schema::hasColumn('admin_users', 'profile_created_at')) {
                // Activity & Engagement
                $table->datetime('profile_created_at')->nullable();
                $table->datetime('last_profile_update')->nullable();
                $table->integer('total_likes_sent')->nullable()->default(0);
                $table->integer('total_messages_sent')->nullable()->default(0);
                $table->integer('total_profile_visits')->nullable()->default(0);
            }
            
            if (!Schema::hasColumn('admin_users', 'boost_active')) {
                // Premium Features
                $table->string('boost_active')->nullable()->default('No'); // Yes, No
                $table->datetime('boost_expires_at')->nullable();
                $table->string('super_likes_remaining')->nullable()->default('0');
                $table->datetime('premium_features_expire')->nullable();
            }
            
            if (!Schema::hasColumn('admin_users', 'matching_preferences')) {
                // Matching Algorithm Data
                $table->text('matching_preferences')->nullable(); // JSON complex preferences
                $table->decimal('matching_score_threshold', 3, 2)->nullable()->default(0.50); // 0.00 to 1.00
                $table->string('show_me')->nullable(); // Men, Women, Everyone
                $table->string('show_age')->nullable()->default('Yes'); // Yes, No
                $table->string('show_distance')->nullable()->default('Yes'); // Yes, No
            }
            
            // Skip profile_visibility as it already exists
            if (!Schema::hasColumn('admin_users', 'last_seen_visibility')) {
                // Privacy & Visibility (skip profile_visibility as it exists)
                $table->string('last_seen_visibility')->nullable()->default('Yes'); // Yes, No
                $table->string('read_receipts')->nullable()->default('Yes'); // Yes, No
                $table->string('typing_indicator')->nullable()->default('Yes'); // Yes, No
            }
            
            if (!Schema::hasColumn('admin_users', 'account_status')) {
                // Moderation & Safety
                $table->string('account_status')->nullable()->default('Active'); // Active, Suspended, Banned, Deactivated
                $table->text('suspension_reason')->nullable();
                $table->datetime('suspension_expires_at')->nullable();
                $table->integer('reports_count')->nullable()->default(0);
                $table->integer('blocks_count')->nullable()->default(0);
            }
            
            if (!Schema::hasColumn('admin_users', 'hometown')) {
                // Geographic & Cultural
                $table->string('hometown')->nullable();
                $table->string('current_city')->nullable();
                $table->json('languages_fluent')->nullable(); // Languages they speak fluently
                $table->string('cultural_background')->nullable();
                $table->string('zodiac_sign')->nullable();
            }
            
            if (!Schema::hasColumn('admin_users', 'profile_completion_steps')) {
                // Additional Metadata
                $table->text('profile_completion_steps')->nullable(); // JSON tracking completion
                $table->string('onboarding_completed')->nullable()->default('No'); // Yes, No
                $table->text('app_preferences')->nullable(); // JSON app settings
                $table->text('notification_settings')->nullable(); // JSON notification preferences
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn([
                'wants_kids', 'has_kids', 'kids_count',
                'interests', 'lifestyle', 'relationship_type', 'relationship_status',
                'eye_color', 'hair_color', 'ethnicity',
                'deal_breakers', 'ideal_partner', 'exercise_frequency',
                'personality_type', 'social_media_links', 'communication_style',
                'first_date_preference', 'date_ideas', 'travel_frequency', 'distance_preference',
                'photo_verified', 'identity_verified', 'verification_documents',
                'profile_created_at', 'last_profile_update', 'total_likes_sent', 
                'total_messages_sent', 'total_profile_visits',
                'boost_active', 'boost_expires_at', 'super_likes_remaining', 'premium_features_expire',
                'matching_preferences', 'matching_score_threshold', 'show_me', 'show_age', 'show_distance',
                'profile_visibility', 'last_seen_visibility', 'read_receipts', 'typing_indicator',
                'account_status', 'suspension_reason', 'suspension_expires_at', 'reports_count', 'blocks_count',
                'hometown', 'current_city', 'languages_fluent', 'cultural_background', 'zodiac_sign',
                'profile_completion_steps', 'onboarding_completed', 'app_preferences', 'notification_settings'
            ]);
        });
    }
};
