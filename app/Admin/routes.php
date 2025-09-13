<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    // LOVEBIRDS DATING APP DASHBOARD ROUTES
    $router->get('dashboard', 'DatingDashboardController@index')->name('home');
    
    // Dating Analytics Dashboard
    $router->get('dating-analytics', 'DatingDashboardController@datingAnalytics')->name('dating.analytics');
    $router->get('dating-engagement', 'DatingDashboardController@datingEngagement')->name('dating.engagement');
    $router->get('dating-discovery', 'DatingDashboardController@discoveryPerformance')->name('dating.discovery');
    
    // Revenue & Monetization Dashboard  
    $router->get('revenue-dashboard', 'RevenueDashboardController@index')->name('revenue.dashboard');
    $router->get('subscription-metrics', 'RevenueDashboardController@subscriptions')->name('revenue.subscriptions');
    $router->get('purchase-analytics', 'RevenueDashboardController@purchases')->name('revenue.purchases');
    
    // Marketplace Analytics Dashboard
    $router->get('marketplace-dashboard', 'MarketplaceDashboardController@index')->name('marketplace.dashboard');
    $router->get('marketplace-orders', 'MarketplaceDashboardController@orders')->name('marketplace.orders');
    $router->get('marketplace-products', 'MarketplaceDashboardController@products')->name('marketplace.products');
    
    // User Management Dashboard
    $router->get('user-management', 'UserManagementDashboardController@index')->name('users.dashboard');
    $router->get('user-demographics', 'UserManagementDashboardController@demographics')->name('users.demographics');
    $router->get('user-activity', 'UserManagementDashboardController@activity')->name('users.activity');
    
    // Safety & Moderation Dashboard (Enhanced)
    $router->get('safety-dashboard', 'SafetyModerationDashboardController@index')->name('safety.dashboard');
    $router->get('content-moderation', 'SafetyModerationDashboardController@moderation')->name('safety.moderation');
    $router->get('user-reports', 'SafetyModerationDashboardController@reports')->name('safety.reports');
    
    // Engagement & Gamification Dashboard
    $router->get('engagement-dashboard', 'EngagementDashboardController@index')->name('engagement.dashboard');
    $router->get('user-retention', 'EngagementDashboardController@retention')->name('engagement.retention');
    $router->get('feature-usage', 'EngagementDashboardController@features')->name('engagement.features');
    
    // Technical Performance Dashboard
    $router->get('performance-dashboard', 'PerformanceDashboardController@index')->name('performance.dashboard');
    $router->get('technical-metrics', 'PerformanceDashboardController@technical')->name('performance.technical');
    $router->get('system-health', 'PerformanceDashboardController@system')->name('performance.system');

    $router->resource('products', ProductController::class);
    $router->resource('scraper-models', ScraperModelController::class);
    $router->resource('movies-active', MovieModelController::class);
    $router->resource('movies-series', MovieModelController::class);
    $router->resource('movies-movies', MovieModelController::class);
    $router->resource('movies-inactive', MovieModelController::class);
    $router->resource('movies-content-is-video', MovieModelController::class);
    $router->resource('movies-processed', MovieModelController::class);
    $router->resource('movies-not-processed', MovieModelController::class);


    $router->resource('movies', MovieModelController::class);
    $router->resource('series-movies', SeriesMovieController::class);

    $router->resource('companies', CompanyController::class);
    $router->resource('stock-categories', StockCategoryController::class);
    $router->resource('stock-sub-categories', StockSubCategoryController::class);
    $router->resource('financial-periods', FinancialPeriodController::class);
    $router->resource('employees', EmployeesController::class);
    $router->resource('stock-items', StockItemController::class);
    $router->resource('stock-records', StockRecordController::class);
    $router->resource('companies-edit', CompanyEditController::class);
    $router->resource('africa-app', AfricaTalkingResponseController::class);
    $router->resource('links', LinkController::class);
    $router->resource('pages', PageController::class);
    $router->resource('schools', SchoolController::class);
    $router->resource('learning-materials-categories', LearningMaterialCategoryController::class);
    $router->resource('learning-materials', LearningMaterialPostController::class);
    $router->resource('gens', GenController::class);
    $router->resource('movie-views', MovieViewController::class);
    $router->resource('movie-likes', MovieLikeController::class);

    $router->resource('my-counters', MyCounterController::class);
    $router->resource('movie-downloads', MovieDownloadController::class);
    $router->resource('product-categories', ProductCategoryController::class);

    $router->resource('content-moderation-logs', ContentModerationLogController::class);

    // Content Moderation Admin Routes
    $router->get('moderation', 'ModerationAdminController@index')->name('moderation.index');
    $router->get('moderation/reports', 'ModerationAdminController@reports')->name('moderation.reports');
    $router->get('moderation/reports/{id}', 'ModerationAdminController@showReport')->name('moderation.reports.show');
    $router->post('moderation/reports/{id}/action', 'ModerationAdminController@actionReport')->name('moderation.reports.action');
    $router->post('moderation/reports/bulk-action', 'ModerationAdminController@bulkAction')->name('moderation.reports.bulk');
    $router->get('moderation/blocks', 'ModerationAdminController@blocks')->name('moderation.blocks');
    $router->get('moderation/logs', 'ModerationAdminController@logs')->name('moderation.logs');
    $router->get('moderation/statistics', 'ModerationAdminController@statistics')->name('moderation.statistics');
    $router->get('moderation/statistics/export', 'ModerationAdminController@exportStatistics')->name('moderation.statistics.export');
    $router->resource('users', UserController::class);
    $router->resource('chat-heads', ChatHeadController::class);
    $router->resource('chat-messages', ChatMessageController::class);


    // AJAX endpoints for moderation
    $router->get('moderation/reports/{id}', 'ModerationAdminController@getReport')->name('moderation.reports.show');
    $router->get('moderation/blocks/{id}', 'ModerationAdminController@getBlock')->name('moderation.blocks.show');
    $router->get('moderation/logs/{id}', 'ModerationAdminController@getLog')->name('moderation.logs.show');


    // Action endpoints
    $router->put('moderation/reports/{id}/status', 'ModerationAdminController@updateReportStatus')->name('moderation.reports.status');
    $router->put('moderation/blocks/{id}/unblock', 'ModerationAdminController@unblockUser')->name('moderation.blocks.unblock');
    $router->delete('moderation/blocks/{id}', 'ModerationAdminController@deleteBlock')->name('moderation.blocks.delete');




    //https://omulimisa.org/api/v1/e-learning/inbound-outbound
    //https://omulimisa.org/api/v1/e-learning/events
});
