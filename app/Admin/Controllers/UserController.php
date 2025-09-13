<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->quickSearch('id', 'username', 'name', 'email', 'first_name', 'last_name', 'phone_number');


        $grid->model()->orderBy('id', 'desc');
        $grid->column('id', __('Id'))->sortable();
        $grid->column('avatar', __('Avatar'))
            ->lightbox(['width' => 50, 'height' => 50])
            ->sortable(); 
        //name
        $grid->column('name', __('Name'))->sortable()->editable();
        $grid->column('first_name', __('First name'))->sortable()->editable();
        $grid->column('last_name', __('Last name'))->sortable()->editable();
        $grid->column('username', __('Username'))->hide();
        $grid->column('created_at', __('Joined'))->sortable()
            ->display(function ($createdAt) {
                return date('Y-m-d', strtotime($createdAt));
            })->filter('range', 'date');
        $grid->column('phone_number', __('Phone number'))->sortable();
        $grid->column('address', __('Address'))->hide();
        $grid->column('sex', __('Sex'))->filter([
            'Male' => __('Male'),
            'Female' => __('Female')
        ])->sortable()
        ->label([
            'Male' => 'success',
            'Female' => 'danger'
        ]); 
        $grid->column('dob', __('Dob'))->sortable();
        $grid->column('email', __('Email'))->sortable();
        $grid->column('secret_code', __('Secret code'))->hide();
        $grid->column('bio', __('Bio'))->editable('textarea')->sortable();
        $grid->column('country', __('Country'))->editable();
        $grid->column('state', __('State'))->editable();
        $grid->column('city', __('City'))->editable();
        $grid->column('is_test_account', __('Is Test Account'))
            ->using(['Yes' => 'Yes', 'No' => 'No'])
            ->filter([
                'Yes' => 'Yes',
                'No' => 'No'
            ])->sortable()
            ->editable('select', ['Yes' => 'Yes', 'No' => 'No']);

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('Username'));
        $show->field('password', __('Password'));
        $show->field('name', __('Name'));
        $show->field('avatar', __('Avatar'));
        $show->field('remember_token', __('Remember token'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('terms_of_service_accepted', __('Terms of service accepted'));
        $show->field('privacy_policy_accepted', __('Privacy policy accepted'));
        $show->field('community_guidelines_accepted', __('Community guidelines accepted'));
        $show->field('marketing_emails_consent', __('Marketing emails consent'));
        $show->field('data_processing_consent', __('Data processing consent'));
        $show->field('content_moderation_consent', __('Content moderation consent'));
        $show->field('terms_accepted_date', __('Terms accepted date'));
        $show->field('privacy_accepted_date', __('Privacy accepted date'));
        $show->field('guidelines_accepted_date', __('Guidelines accepted date'));
        $show->field('notification_preferences', __('Notification preferences'));
        $show->field('push_notifications', __('Push notifications'));
        $show->field('email_notifications', __('Email notifications'));
        $show->field('profile_visibility', __('Profile visibility'));
        $show->field('content_filtering', __('Content filtering'));
        $show->field('safe_mode', __('Safe mode'));
        $show->field('location_sharing', __('Location sharing'));
        $show->field('analytics_consent', __('Analytics consent'));
        $show->field('crash_reporting', __('Crash reporting'));
        $show->field('company_id', __('Company id'));
        $show->field('first_name', __('First name'));
        $show->field('last_name', __('Last name'));
        $show->field('phone_number', __('Phone number'));
        $show->field('phone_number_2', __('Phone number 2'));
        $show->field('address', __('Address'));
        $show->field('sex', __('Sex'));
        $show->field('dob', __('Dob'));
        $show->field('status', __('Status'));
        $show->field('email', __('Email'));
        $show->field('secret_code', __('Secret code'));
        $show->field('profile_photos', __('Profile photos'));
        $show->field('bio', __('Bio'));
        $show->field('tagline', __('Tagline'));
        $show->field('phone_country_name', __('Phone country name'));
        $show->field('phone_country_code', __('Phone country code'));
        $show->field('phone_country_international', __('Phone country international'));
        $show->field('sexual_orientation', __('Sexual orientation'));
        $show->field('height_cm', __('Height cm'));
        $show->field('body_type', __('Body type'));
        $show->field('country', __('Country'));
        $show->field('state', __('State'));
        $show->field('city', __('City'));
        $show->field('latitude', __('Latitude'));
        $show->field('longitude', __('Longitude'));
        $show->field('last_online_at', __('Last online at'));
        $show->field('online_status', __('Online status'));
        $show->field('looking_for', __('Looking for'));
        $show->field('interested_in', __('Interested in'));
        $show->field('age_range_min', __('Age range min'));
        $show->field('age_range_max', __('Age range max'));
        $show->field('max_distance_km', __('Max distance km'));
        $show->field('smoking_habit', __('Smoking habit'));
        $show->field('drinking_habit', __('Drinking habit'));
        $show->field('pet_preference', __('Pet preference'));
        $show->field('religion', __('Religion'));
        $show->field('political_views', __('Political views'));
        $show->field('languages_spoken', __('Languages spoken'));
        $show->field('education_level', __('Education level'));
        $show->field('occupation', __('Occupation'));
        $show->field('email_verified', __('Email verified'));
        $show->field('phone_verified', __('Phone verified'));
        $show->field('verification_code', __('Verification code'));
        $show->field('failed_login_attempts', __('Failed login attempts'));
        $show->field('last_password_change', __('Last password change'));
        $show->field('subscription_tier', __('Subscription tier'));
        $show->field('subscription_expires', __('Subscription expires'));
        $show->field('credits_balance', __('Credits balance'));
        $show->field('profile_views', __('Profile views'));
        $show->field('likes_received', __('Likes received'));
        $show->field('matches_count', __('Matches count'));
        $show->field('completed_profile_pct', __('Completed profile pct'));
        $show->field('wants_kids', __('Wants kids'));
        $show->field('has_kids', __('Has kids'));
        $show->field('kids_count', __('Kids count'));
        $show->field('interests', __('Interests'));
        $show->field('lifestyle', __('Lifestyle'));
        $show->field('relationship_type', __('Relationship type'));
        $show->field('relationship_status', __('Relationship status'));
        $show->field('eye_color', __('Eye color'));
        $show->field('hair_color', __('Hair color'));
        $show->field('ethnicity', __('Ethnicity'));
        $show->field('deal_breakers', __('Deal breakers'));
        $show->field('ideal_partner', __('Ideal partner'));
        $show->field('exercise_frequency', __('Exercise frequency'));
        $show->field('personality_type', __('Personality type'));
        $show->field('social_media_links', __('Social media links'));
        $show->field('communication_style', __('Communication style'));
        $show->field('first_date_preference', __('First date preference'));
        $show->field('date_ideas', __('Date ideas'));
        $show->field('travel_frequency', __('Travel frequency'));
        $show->field('distance_preference', __('Distance preference'));
        $show->field('photo_verified', __('Photo verified'));
        $show->field('identity_verified', __('Identity verified'));
        $show->field('verification_documents', __('Verification documents'));
        $show->field('profile_created_at', __('Profile created at'));
        $show->field('last_profile_update', __('Last profile update'));
        $show->field('total_likes_sent', __('Total likes sent'));
        $show->field('total_messages_sent', __('Total messages sent'));
        $show->field('total_profile_visits', __('Total profile visits'));
        $show->field('boost_active', __('Boost active'));
        $show->field('boost_expires_at', __('Boost expires at'));
        $show->field('super_likes_remaining', __('Super likes remaining'));
        $show->field('premium_features_expire', __('Premium features expire'));
        $show->field('matching_preferences', __('Matching preferences'));
        $show->field('matching_score_threshold', __('Matching score threshold'));
        $show->field('show_me', __('Show me'));
        $show->field('show_age', __('Show age'));
        $show->field('show_distance', __('Show distance'));
        $show->field('last_seen_visibility', __('Last seen visibility'));
        $show->field('read_receipts', __('Read receipts'));
        $show->field('typing_indicator', __('Typing indicator'));
        $show->field('account_status', __('Account status'));
        $show->field('suspension_reason', __('Suspension reason'));
        $show->field('suspension_expires_at', __('Suspension expires at'));
        $show->field('reports_count', __('Reports count'));
        $show->field('blocks_count', __('Blocks count'));
        $show->field('hometown', __('Hometown'));
        $show->field('current_city', __('Current city'));
        $show->field('languages_fluent', __('Languages fluent'));
        $show->field('cultural_background', __('Cultural background'));
        $show->field('zodiac_sign', __('Zodiac sign'));
        $show->field('profile_completion_steps', __('Profile completion steps'));
        $show->field('onboarding_completed', __('Onboarding completed'));
        $show->field('app_preferences', __('App preferences'));
        $show->field('notification_settings', __('Notification settings'));
        $show->field('token', __('Token'));
        $show->field('subscription_status', __('Subscription status'));
        $show->field('subscription_plan', __('Subscription plan'));
        $show->field('subscription_expires_at', __('Subscription expires at'));
        $show->field('pending_stripe_payment_id', __('Pending stripe payment id'));
        $show->field('subscription_updated_at', __('Subscription updated at'));
        $show->field('pending_subscription_plan', __('Pending subscription plan'));
        $show->field('pending_stripe_payment_url', __('Pending stripe payment url'));
        $show->field('subscription_started_at', __('Subscription started at'));
        $show->field('is_test_account', __('Is test account'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->text('username', __('Username')); 
        $form->text('name', __('Name'));
        $form->image('avatar', __('Avatar'));
        $form->textarea('remember_token', __('Remember token'));
        $form->text('terms_of_service_accepted', __('Terms of service accepted'));
        $form->text('privacy_policy_accepted', __('Privacy policy accepted'));
        $form->text('community_guidelines_accepted', __('Community guidelines accepted'));
        $form->text('marketing_emails_consent', __('Marketing emails consent'));
        $form->text('data_processing_consent', __('Data processing consent'));
        $form->text('content_moderation_consent', __('Content moderation consent'));
        $form->datetime('terms_accepted_date', __('Terms accepted date'))->default(date('Y-m-d H:i:s'));
        $form->datetime('privacy_accepted_date', __('Privacy accepted date'))->default(date('Y-m-d H:i:s'));
        $form->datetime('guidelines_accepted_date', __('Guidelines accepted date'))->default(date('Y-m-d H:i:s'));
        $form->text('notification_preferences', __('Notification preferences'));
        $form->text('push_notifications', __('Push notifications'));
        $form->text('email_notifications', __('Email notifications'));
        $form->text('profile_visibility', __('Profile visibility'))->default('Public');
        $form->text('content_filtering', __('Content filtering'))->default('On');
        $form->text('safe_mode', __('Safe mode'))->default('On');
        $form->text('location_sharing', __('Location sharing'));
        $form->text('analytics_consent', __('Analytics consent'));
        $form->text('crash_reporting', __('Crash reporting'));
        $form->number('company_id', __('Company id'));
        $form->textarea('first_name', __('First name'));
        $form->textarea('last_name', __('Last name'));
        $form->textarea('phone_number', __('Phone number'));
        $form->textarea('phone_number_2', __('Phone number 2'));
        $form->textarea('address', __('Address'));
        $form->textarea('sex', __('Sex'));
        $form->date('dob', __('Dob'))->default(date('Y-m-d'));
        $form->text('status', __('Status'))->default('active');
        $form->email('email', __('Email'));
        $form->text('secret_code', __('Secret code')); 
        $form->textarea('bio', __('Bio'));
        $form->text('tagline', __('Tagline'));
        $form->text('phone_country_name', __('Phone country name'));
        $form->text('phone_country_code', __('Phone country code'));
        $form->text('phone_country_international', __('Phone country international'));
        $form->text('sexual_orientation', __('Sexual orientation'));
        $form->number('height_cm', __('Height cm'));
        $form->text('body_type', __('Body type'));
        $form->text('country', __('Country'));
        $form->text('state', __('State'));
        $form->text('city', __('City'));
        $form->decimal('latitude', __('Latitude'));
        $form->decimal('longitude', __('Longitude'));
        $form->datetime('last_online_at', __('Last online at'))->default(date('Y-m-d H:i:s'));
        $form->text('online_status', __('Online status'))->default('Offline');
        $form->textarea('looking_for', __('Looking for'));
        $form->textarea('interested_in', __('Interested in'));
        $form->number('age_range_min', __('Age range min'));
        $form->number('age_range_max', __('Age range max'));
        $form->number('max_distance_km', __('Max distance km'));
        $form->text('smoking_habit', __('Smoking habit'));
        $form->text('drinking_habit', __('Drinking habit'));
        $form->text('pet_preference', __('Pet preference'));
        $form->text('religion', __('Religion'));
        $form->text('political_views', __('Political views'));
        $form->textarea('languages_spoken', __('Languages spoken'));
        $form->text('education_level', __('Education level'));
        $form->text('occupation', __('Occupation'));
        $form->text('email_verified', __('Email verified'))->default('No');
        $form->text('phone_verified', __('Phone verified'))->default('No');
        $form->text('verification_code', __('Verification code'));
        $form->number('failed_login_attempts', __('Failed login attempts'));
        $form->datetime('last_password_change', __('Last password change'))->default(date('Y-m-d H:i:s'));
        $form->text('subscription_tier', __('Subscription tier'));
        $form->datetime('subscription_expires', __('Subscription expires'))->default(date('Y-m-d H:i:s'));
        $form->number('credits_balance', __('Credits balance'));
        $form->number('profile_views', __('Profile views'));
        $form->number('likes_received', __('Likes received'));
        $form->number('matches_count', __('Matches count'));
        $form->number('completed_profile_pct', __('Completed profile pct'));
        $form->text('wants_kids', __('Wants kids'));
        $form->text('has_kids', __('Has kids'));
        $form->number('kids_count', __('Kids count'));
        $form->textarea('interests', __('Interests'));
        $form->textarea('lifestyle', __('Lifestyle'));
        $form->text('relationship_type', __('Relationship type'));
        $form->text('relationship_status', __('Relationship status'));
        $form->text('eye_color', __('Eye color'));
        $form->text('hair_color', __('Hair color'));
        $form->text('ethnicity', __('Ethnicity'));
        $form->textarea('deal_breakers', __('Deal breakers'));
        $form->textarea('ideal_partner', __('Ideal partner'));
        $form->text('exercise_frequency', __('Exercise frequency'));
        $form->text('personality_type', __('Personality type'));
        $form->textarea('social_media_links', __('Social media links'));
        $form->text('communication_style', __('Communication style'));
        $form->text('first_date_preference', __('First date preference'));
        $form->textarea('date_ideas', __('Date ideas'));
        $form->text('travel_frequency', __('Travel frequency'));
        $form->text('distance_preference', __('Distance preference'));
        $form->text('photo_verified', __('Photo verified'))->default('No');
        $form->text('identity_verified', __('Identity verified'))->default('No');
        $form->textarea('verification_documents', __('Verification documents'));
        $form->datetime('profile_created_at', __('Profile created at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('last_profile_update', __('Last profile update'))->default(date('Y-m-d H:i:s'));
        $form->number('total_likes_sent', __('Total likes sent'));
        $form->number('total_messages_sent', __('Total messages sent'));
        $form->number('total_profile_visits', __('Total profile visits'));
        $form->text('boost_active', __('Boost active'))->default('No');
        $form->datetime('boost_expires_at', __('Boost expires at'))->default(date('Y-m-d H:i:s'));
        $form->text('super_likes_remaining', __('Super likes remaining'));
        $form->datetime('premium_features_expire', __('Premium features expire'))->default(date('Y-m-d H:i:s'));
        $form->textarea('matching_preferences', __('Matching preferences'));
        $form->decimal('matching_score_threshold', __('Matching score threshold'))->default(0.50);
        $form->text('show_me', __('Show me'));
        $form->text('show_age', __('Show age'))->default('Yes');
        $form->text('show_distance', __('Show distance'))->default('Yes');
        $form->text('last_seen_visibility', __('Last seen visibility'))->default('Yes');
        $form->text('read_receipts', __('Read receipts'))->default('Yes');
        $form->text('typing_indicator', __('Typing indicator'))->default('Yes');
        $form->text('account_status', __('Account status'))->default('Active');
        $form->textarea('suspension_reason', __('Suspension reason'));
        $form->datetime('suspension_expires_at', __('Suspension expires at'))->default(date('Y-m-d H:i:s'));
        $form->number('reports_count', __('Reports count'));
        $form->number('blocks_count', __('Blocks count'));
        $form->text('hometown', __('Hometown'));
        $form->text('current_city', __('Current city'));
        $form->text('languages_fluent', __('Languages fluent'));
        $form->text('cultural_background', __('Cultural background'));
        $form->text('zodiac_sign', __('Zodiac sign'));
        $form->textarea('profile_completion_steps', __('Profile completion steps'));
        $form->text('onboarding_completed', __('Onboarding completed'))->default('No');
        $form->textarea('app_preferences', __('App preferences'));
        $form->textarea('notification_settings', __('Notification settings'));
        $form->textarea('token', __('Token'));
        $form->text('subscription_status', __('Subscription status'))->default('free');
        $form->text('subscription_plan', __('Subscription plan'));
        $form->datetime('subscription_expires_at', __('Subscription expires at'))->default(date('Y-m-d H:i:s'));
        $form->text('pending_stripe_payment_id', __('Pending stripe payment id'));
        $form->datetime('subscription_updated_at', __('Subscription updated at'))->default(date('Y-m-d H:i:s'));
        $form->text('pending_subscription_plan', __('Pending subscription plan'));
        $form->textarea('pending_stripe_payment_url', __('Pending stripe payment url'));
        $form->datetime('subscription_started_at', __('Subscription started at'))->default(date('Y-m-d H:i:s'));
        $form->text('is_test_account', __('Is test account'))->default('No');

        return $form;
    }
}
