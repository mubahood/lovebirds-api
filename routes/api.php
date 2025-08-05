<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DynamicCrudController;
use App\Http\Controllers\ModerationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;


Route::post('auth/password-reset', [ApiController::class, 'password_reset']);
Route::post('auth/register', [ApiController::class, 'register']);
Route::post('auth/request-password-reset-code', [ApiController::class, 'request_password_reset_code']);
Route::post('auth/login', [ApiController::class, 'login']);

// Content filtering endpoint (used by app's automated systems)
Route::post('moderation/filter-content', [ModerationController::class, 'filterContent']);

Route::post('api/{model}', [ApiController::class, 'my_update']);
Route::get('api/{model}', [ApiController::class, 'my_list']);
Route::get('products-1', [ApiController::class, 'products_1']);
Route::post('file-uploading', [ApiController::class, 'file_uploading']);

// Profile Photo Management Endpoints
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::post('upload-profile-photos', [ApiController::class, 'upload_profile_photos']);
    Route::post('delete-profile-photo', [ApiController::class, 'delete_profile_photo']);
    Route::post('reorder-profile-photos', [ApiController::class, 'reorder_profile_photos']);
});

Route::middleware([JwtMiddleware::class])->group(function () {
    Route::post('disable-account', [ApiController::class, 'disable_account']);


    Route::post("post-media-upload", [ApiController::class, 'upload_media']);
    Route::post("upload-media-preview", [ApiController::class, 'upload_media_preview']); // Multimedia preview endpoint
    Route::post("product-create", [ApiController::class, "product_create"]);
    Route::post('products-delete', [ApiController::class, 'products_delete']);

    // Cart & Order Management
    Route::post('cart/submit-order', [ApiController::class, 'submit_order']);

    Route::get('me', [ApiController::class, 'me']);
    Route::get('manifest', [ApiController::class, 'manifest']);
    Route::get('users-list', [DynamicCrudController::class, 'users_list']);
    Route::get('/dynamic-list', [DynamicCrudController::class, 'index']);
    Route::post('/dynamic-save', [DynamicCrudController::class, 'save']);
    Route::post('/dynamic-delete', [DynamicCrudController::class, 'delete']);
    Route::POST("consultation-card-payment", [DynamicCrudController::class, 'consultation_card_payment']);

    Route::POST('chat-send', [ApiController::class, 'chat_send']);
    Route::get('chat-heads', [ApiController::class, 'chat_heads']);
    Route::get('chat-messages', [ApiController::class, 'chat_messages']);
    Route::POST('chat-mark-as-read', [ApiController::class, 'chat_mark_as_read']);
    Route::POST('chat-start', [ApiController::class, 'chat_start']);
    Route::POST('chat-delete', [ApiController::class, 'chat_delete']);

    // Enhanced Dating Chat Features
    Route::POST('chat-typing-indicator', [ApiController::class, 'chat_typing_indicator']);
    Route::get('chat-typing-status', [ApiController::class, 'chat_typing_status']);
    Route::POST('chat-add-reaction', [ApiController::class, 'chat_add_reaction']);
    Route::POST('chat-remove-reaction', [ApiController::class, 'chat_remove_reaction']);
    Route::POST('chat-block-user', [ApiController::class, 'chat_block_user']);
    Route::POST('chat-unblock-user', [ApiController::class, 'chat_unblock_user']);
    Route::get('chat-media-files', [ApiController::class, 'chat_media_files']);
    Route::get('chat-search-messages', [ApiController::class, 'chat_search_messages']);

    // Date Planning & Chat Enhancement
    Route::post('get-chat-messages', [ApiController::class, 'getChatMessages']);
    Route::post('send-message', [ApiController::class, 'sendMessage']);
    Route::post('get-restaurant-suggestions', [ApiController::class, 'getRestaurantSuggestions']);
    Route::post('get-date-activities', [ApiController::class, 'getDateActivities']);
    Route::post('get-popular-date-spots', [ApiController::class, 'getPopularDateSpots']);
    Route::post('save-planned-date', [ApiController::class, 'savePlannedDate']);
    Route::post('advanced-search', [ApiController::class, 'advancedSearch']);

    // Phase 7.2: Date Marketplace Booking Endpoints
    Route::post('book-restaurant', [ApiController::class, 'book_restaurant']);
    Route::post('book-activity', [ApiController::class, 'book_activity']);
    Route::get('get-date-packages', [ApiController::class, 'get_date_packages']);
    Route::post('book-date-package', [ApiController::class, 'book_date_package']);
    Route::get('get-booking-history', [ApiController::class, 'get_booking_history']);
    Route::post('cancel-booking', [ApiController::class, 'cancel_booking']);
    Route::get('get-available-time-slots', [ApiController::class, 'get_available_time_slots']);

    // Phase 7.2: Relationship Milestone Gift Suggestions
    Route::get('get-milestone-gift-suggestions', [ApiController::class, 'get_milestone_gift_suggestions']);
    Route::post('save-milestone-reminder', [ApiController::class, 'save_milestone_reminder']);

    // Phase 6.2: Chat Safety & Moderation endpoints //
    Route::post('analyze-message-safety', [ApiController::class, 'analyzeMessageSafety']);
    Route::post('report-unsafe-behavior', [ApiController::class, 'reportUnsafeBehavior']);
    Route::post('verify-meetup-consent', [ApiController::class, 'verifyMeetupConsent']);
    Route::post('check-photo-sharing-risk', [ApiController::class, 'checkPhotoSharingRisk']);
    Route::post('analyze-conversation-sentiment', [ApiController::class, 'analyzeConversationSentiment']);
    Route::post('emergency-safety-alert', [ApiController::class, 'emergencySafetyAlert']);

    // Advanced Dating Discovery System
    Route::get('discover-users', [ApiController::class, 'discover_users']);
    Route::get('discovery-stats', [ApiController::class, 'discovery_stats']);
    Route::get('smart-recommendations', [ApiController::class, 'smart_recommendations']);
    Route::get('swipe-discovery', [ApiController::class, 'swipe_discovery']);
    Route::get('search-users', [ApiController::class, 'search_users']);
    Route::get('nearby-users', [ApiController::class, 'nearby_users']);

    // Photo Likes/Dislikes System
    Route::post('swipe-action', [ApiController::class, 'swipe_action']);
    Route::get('who-liked-me', [ApiController::class, 'who_liked_me']);
    Route::get('my-matches', [ApiController::class, 'my_matches']);
    Route::post('undo-swipe', [ApiController::class, 'undo_swipe']);
    Route::get('swipe-stats', [ApiController::class, 'swipe_stats']);
    Route::get('profile-stats', [ApiController::class, 'profile_stats']);
    Route::get('recent-activity', [ApiController::class, 'recent_activity']);

    // Profile Boost Features
    Route::post('boost-profile', [ApiController::class, 'boost_profile']);
    Route::get('boost-status', [ApiController::class, 'boost_status']);
    Route::get('check-boost-availability', [ApiController::class, 'check_boost_availability']);
    Route::post('activate-boost', [ApiController::class, 'activate_boost']);

    // Premium Features & Advanced Filters
    Route::get('search-filters', [ApiController::class, 'search_filters']);
    Route::post('save-search-filters', [ApiController::class, 'save_search_filters']);
    Route::post('track-feature-usage', [ApiController::class, 'track_feature_usage']);
    Route::get('upgrade-recommendations', [ApiController::class, 'upgrade_recommendations']);

    // Content Moderation & Safety Routes
    Route::post('moderation/report-content', [ModerationController::class, 'reportContent']);
    Route::post('moderation/report', [ModerationController::class, 'reportContent']); // Alias for backward compatibility
    Route::post('moderation/block-user', [ModerationController::class, 'blockUser']);
    Route::post('moderation/unblock-user', [ModerationController::class, 'unblockUser']);
    Route::get('moderation/blocked-users', [ModerationController::class, 'getBlockedUsers']);
    Route::get('moderation/my-reports', [ModerationController::class, 'getUserReports']);
    Route::get('moderation/user-reports', [ModerationController::class, 'getUserReports']); // Alias
    Route::post('moderation/legal-consent', [ModerationController::class, 'updateLegalConsent']);
    Route::post('moderation/update-legal-consent', [ModerationController::class, 'updateLegalConsent']); // Alias
    Route::get('moderation/legal-consent-status', [ModerationController::class, 'getLegalConsentStatus']);

    // Admin-only moderation routes
    Route::get('moderation/dashboard', [ModerationController::class, 'getModerationDashboard']);
});



Route::post('save-view-progress', [ApiController::class, 'save_view_progress']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Subscription routes (temporarily without middleware for final testing)
Route::post('create_subscription_payment', [ApiController::class, 'create_subscription_payment']);
Route::post('check_subscription_payment', [ApiController::class, 'check_subscription_payment']);
Route::get('subscription_status', [ApiController::class, 'subscription_status']);
Route::post('test_user_activate_subscription', [ApiController::class, 'test_user_activate_subscription']);
