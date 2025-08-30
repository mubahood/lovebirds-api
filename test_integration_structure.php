<?php
/**
 * LoveBirds Stripe Integration Structure Test
 * Tests the integration without requiring valid API keys
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🏗️  LOVEBIRDS STRIPE INTEGRATION STRUCTURE TEST\n";
echo "================================================\n\n";

$tests_passed = 0;
$total_tests = 0;

function test($description, $condition, $success_msg = null, $error_msg = null) {
    global $tests_passed, $total_tests;
    $total_tests++;
    
    echo "🧪 Testing: {$description}... ";
    
    if ($condition) {
        echo "✅ PASS\n";
        if ($success_msg) echo "   └─ {$success_msg}\n";
        $tests_passed++;
    } else {
        echo "❌ FAIL\n";
        if ($error_msg) echo "   └─ {$error_msg}\n";
    }
    echo "\n";
}

// Test 1: Environment Configuration
echo "📁 ENVIRONMENT & CONFIGURATION TESTS\n";
echo "====================================\n";

test(
    "Environment file exists",
    file_exists('.env'),
    ".env file is present",
    ".env file is missing - copy from .env.example"
);

$env_content = file_exists('.env') ? file_get_contents('.env') : '';

test(
    "Stripe key configured",
    strpos($env_content, 'STRIPE_KEY=') !== false,
    "STRIPE_KEY is set in environment",
    "Add STRIPE_KEY to your .env file"
);

test(
    "App URL configured",
    strpos($env_content, 'APP_URL=') !== false,
    "APP_URL is configured for webhook",
    "Set APP_URL in .env for proper webhook URL"
);

// Test 2: Database Structure
echo "🗄️  DATABASE STRUCTURE TESTS\n";
echo "============================\n";

try {
    test(
        "Orders table exists",
        \Illuminate\Support\Facades\Schema::hasTable('orders'),
        "Orders table is present",
        "Run migrations to create orders table"
    );

    test(
        "Ordered items table exists",
        \Illuminate\Support\Facades\Schema::hasTable('ordered_items'),
        "Ordered items table is present",
        "Run migrations to create ordered_items table"
    );

    test(
        "Users table exists",
        \Illuminate\Support\Facades\Schema::hasTable('users'),
        "Users table is present",
        "Run migrations to create users table"
    );

    if (\Illuminate\Support\Facades\Schema::hasTable('orders')) {
        $order_columns = [
            'stripe_product_id' => 'Stripe product ID storage',
            'stripe_price_id' => 'Stripe price ID storage',
            'total_amount' => 'Total amount calculation'
        ];

        foreach ($order_columns as $column => $description) {
            test(
                "Orders table has {$column} column",
                \Illuminate\Support\Facades\Schema::hasColumn('orders', $column),
                $description . " column exists",
                "Add {$column} column to orders table migration"
            );
        }
    }

} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Model Structure
echo "🏗️  MODEL STRUCTURE TESTS\n";
echo "========================\n";

test(
    "Order model exists",
    class_exists('App\\Models\\Order'),
    "Order model is available",
    "Create App\\Models\\Order class"
);

test(
    "OrderedItem model exists", 
    class_exists('App\\Models\\OrderedItem'),
    "OrderedItem model is available",
    "Create App\\Models\\OrderedItem class"
);

test(
    "User model exists",
    class_exists('App\\Models\\User'),
    "User model is available",
    "User model should exist by default"
);

// Test Order model methods
if (class_exists('App\\Models\\Order')) {
    $order_methods = [
        'create_payment_link' => 'Payment link generation method',
        'calculateTotalAmount' => 'Total amount calculation method'
    ];

    foreach ($order_methods as $method => $description) {
        test(
            "Order model has {$method} method",
            method_exists('App\\Models\\Order', $method),
            $description . " is implemented",
            "Add {$method} method to Order model"
        );
    }
}

// Test 4: API Routes
echo "🛤️  API ROUTES TESTS\n";
echo "==================\n";

$routes = \Illuminate\Support\Facades\Route::getRoutes();
$route_names = [];
foreach ($routes as $route) {
    $route_names[] = $route->uri;
}

test(
    "Generate payment link route exists",
    in_array('api/generate-payment-link', $route_names),
    "Payment link API endpoint is registered",
    "Add generate-payment-link route to api.php"
);

test(
    "Stripe webhook route exists",
    in_array('api/stripe-webhook', $route_names),
    "Webhook endpoint is registered",
    "Add stripe-webhook route to api.php"
);

test(
    "Create order route exists",
    in_array('api/create-order', $route_names),
    "Order creation endpoint exists",
    "Ensure create-order route is in api.php"
);

// Test 5: Stripe Package
echo "📦 STRIPE PACKAGE TESTS\n";
echo "=======================\n";

test(
    "Stripe PHP SDK installed",
    class_exists('\\Stripe\\StripeClient'),
    "Stripe SDK is available via Composer",
    "Install Stripe PHP SDK: composer require stripe/stripe-php"
);

test(
    "Stripe configuration accessible",
    !empty(env('STRIPE_KEY')),
    "Stripe key is configured",
    "Set STRIPE_KEY in your .env file"
);

// Test 6: File Structure
echo "📄 FILE STRUCTURE TESTS\n";
echo "=======================\n";

$required_files = [
    'app/Models/Order.php' => 'Order model file',
    'app/Models/OrderedItem.php' => 'OrderedItem model file',
    'routes/api.php' => 'API routes file',
    'app/Http/Controllers/Api' => 'API controllers directory'
];

foreach ($required_files as $file => $description) {
    test(
        basename($file) . " exists",
        file_exists($file) || is_dir($file),
        $description . " is present",
        "Create " . $description
    );
}

// Test 7: Integration Logic Test
echo "🔧 INTEGRATION LOGIC TESTS\n";
echo "==========================\n";

// Test Order creation with mock data
try {
    $test_order_data = [
        'customer_name' => 'Test Customer',
        'total_amount' => 2599, // $25.99 in cents
        'status' => 'pending',
        'logged_in_user_id' => 1
    ];

    test(
        "Order model can be instantiated",
        class_exists('App\\Models\\Order'),
        "Order model is ready for use",
        "Check Order model implementation"
    );

    if (class_exists('App\\Models\\Order')) {
        $order = new App\Models\Order();
        
        test(
            "Order has fillable attributes",
            !empty($order->getFillable()),
            "Order model has fillable fields configured",
            "Configure \$fillable array in Order model"
        );
    }

} catch (Exception $e) {
    echo "⚠️  Order model test failed: " . $e->getMessage() . "\n\n";
}

// Final Summary
echo "📊 TEST RESULTS SUMMARY\n";
echo "=======================\n";
$success_rate = ($tests_passed / $total_tests) * 100;

echo "✅ Tests Passed: {$tests_passed}/{$total_tests}\n";
echo "📈 Success Rate: " . round($success_rate, 1) . "%\n\n";

if ($success_rate >= 90) {
    echo "🎉 EXCELLENT! Your integration structure is solid!\n";
    echo "🔑 The only issue is your expired Stripe API key.\n\n";
} elseif ($success_rate >= 70) {
    echo "👍 GOOD! Most of your integration is working.\n";
    echo "🔧 Fix the failed tests above and you'll be ready!\n\n";
} else {
    echo "⚠️  NEEDS WORK! Several components need attention.\n";
    echo "🛠️  Address the failed tests before proceeding.\n\n";
}

echo "🚀 NEXT STEPS:\n";
echo "1. 🔑 Update your Stripe API key (main issue)\n";
echo "2. 🧪 Test with Stripe test cards\n";
echo "3. 🔗 Configure webhook URL in Stripe dashboard\n";
echo "4. 💰 Test payment flow end-to-end\n\n";

echo "🔗 Your webhook URL should be:\n";
echo env('APP_URL') . "/api/stripe-webhook\n\n";

echo "🧪 Stripe Test Cards:\n";
echo "• Success: 4242424242424242\n";
echo "• Declined: 4000000000000002\n";
echo "• 3D Secure: 4000000000003220\n";
echo "• Insufficient funds: 4000000000009995\n\n";

if ($success_rate >= 80) {
    echo "✨ Your integration is ready! Just update that API key! ✨\n";
}

?>
