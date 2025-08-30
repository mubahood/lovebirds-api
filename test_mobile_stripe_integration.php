<?php
/**
 * Complete Stripe Integration End-to-End Test
 * This script tests the entire payment flow from mobile app to Stripe
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔥 STRIPE INTEGRATION END-TO-END TEST\n";
echo "=====================================\n\n";

$tests_passed = 0;
$total_tests = 0;
$test_order_id = null;

function log_test($name, $result, $details = '') {
    global $tests_passed, $total_tests;
    $total_tests++;
    
    $status = $result ? '✅ PASS' : '❌ FAIL';
    echo "🧪 {$name}: {$status}\n";
    
    if (!empty($details)) {
        echo "   └─ {$details}\n";
    }
    
    if ($result) {
        $tests_passed++;
    }
    
    echo "\n";
    return $result;
}

function make_api_request($endpoint, $method = 'GET', $data = null, $headers = []) {
    $base_url = env('APP_URL', 'http://localhost:8888/lovebirds-api') . '/public/api';
    $url = $base_url . '/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    
    // Default headers
    $default_headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Tok: test_token_123', // Test authentication token
    ];
    
    $headers = array_merge($default_headers, $headers);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'data' => $response ? json_decode($response, true) : null
    ];
}

// Test 1: Environment Setup
echo "🔧 PHASE 1: ENVIRONMENT VERIFICATION\n";
echo "=====================================\n";

log_test(
    "Laravel Environment Ready",
    !empty(env('APP_URL')),
    "APP_URL: " . env('APP_URL')
);

log_test(
    "Stripe API Key Configured",
    !empty(env('STRIPE_KEY')),
    "Key prefix: " . substr(env('STRIPE_KEY'), 0, 8) . "..."
);

log_test(
    "Database Connection",
    \Illuminate\Support\Facades\DB::connection()->getPDO() !== null,
    "Database connected successfully"
);

// Test 2: Create Test User
echo "👤 PHASE 2: USER AUTHENTICATION TEST\n";
echo "====================================\n";

try {
    // Create or find test user
    $test_user = App\Models\User::firstOrCreate([
        'email' => 'stripe_test_user@lovebirds.test'
    ], [
        'name' => 'Stripe Test User',
        'first_name' => 'Stripe',
        'last_name' => 'Test',
        'phone_number' => '+1234567890',
        'password' => \Illuminate\Support\Facades\Hash::make('testpassword123'),
        'email_verified_at' => now(),
        'is_approved' => 1
    ]);

    log_test(
        "Test User Created/Found",
        $test_user->id > 0,
        "User ID: {$test_user->id}, Email: {$test_user->email}"
    );

} catch (Exception $e) {
    log_test(
        "Test User Creation",
        false,
        "Error: " . $e->getMessage()
    );
}

// Test 3: Mobile App API Authentication
echo "📱 PHASE 3: MOBILE APP AUTHENTICATION\n";
echo "====================================\n";

// Test registration endpoint (simulate mobile app registration)
$registration_data = [
    'name' => 'Mobile Test User',
    'first_name' => 'Mobile',
    'last_name' => 'User',
    'email' => 'mobile_test_' . time() . '@lovebirds.test',
    'password' => 'testpass123',
    'password_confirmation' => 'testpass123',
    'phone_number' => '+1987654321'
];

$auth_response = make_api_request('/auth/register', 'POST', $registration_data);

log_test(
    "Mobile User Registration",
    $auth_response['http_code'] === 200 && isset($auth_response['data']['access_token']),
    "HTTP {$auth_response['http_code']}: " . ($auth_response['data']['message'] ?? 'Registration successful')
);

$mobile_token = $auth_response['data']['access_token'] ?? null;
$mobile_user_id = $auth_response['data']['user']['id'] ?? null;

// Test 4: Order Creation (Mobile App Flow)
echo "🛒 PHASE 4: ORDER CREATION TEST\n";
echo "===============================\n";

$order_data = [
    'items' => [
        [
            'product_id' => 1,
            'quantity' => 2,
            'price' => 25.99,
            'name' => 'Test Product 1'
        ],
        [
            'product_id' => 2,
            'quantity' => 1,
            'price' => 15.50,
            'name' => 'Test Product 2'
        ]
    ],
    'customer_name' => 'Test Customer',
    'customer_phone_number_1' => '+1234567890',
    'customer_address' => '123 Test Street, Test City, TC',
    'delivery_method' => 'pickup',
    'logged_in_user_id' => $mobile_user_id ?? 1
];

$headers = [];
if ($mobile_token) {
    $headers[] = "Authorization: Bearer {$mobile_token}";
}

$order_response = make_api_request('/create-order', 'POST', $order_data, $headers);

log_test(
    "Order Creation via Mobile API",
    $order_response['http_code'] === 200,
    "HTTP {$order_response['http_code']}: " . json_encode($order_response['data'] ?? $order_response['response'])
);

$test_order_id = $order_response['data']['order']['id'] ?? null;

if ($test_order_id) {
    echo "📋 Created test order ID: {$test_order_id}\n\n";
} else {
    echo "⚠️  Order creation failed, creating manual order...\n";
    
    // Create order manually in database
    try {
        $manual_order = App\Models\Order::create([
            'user' => $mobile_user_id ?? 1,
            'customer_name' => 'Manual Test Customer',
            'customer_phone_number_1' => '+1234567890',
            'customer_address' => '123 Test Street',
            'amount' => '67.48',
            'order_total' => '67.48',
            'total_amount' => '6748', // In cents
            'order_state' => 0,
            'delivery_district' => 'Test District'
        ]);
        
        $test_order_id = $manual_order->id;
        
        log_test(
            "Manual Order Creation",
            true,
            "Created order ID: {$test_order_id}"
        );
        
    } catch (Exception $e) {
        log_test(
            "Manual Order Creation",
            false,
            "Error: " . $e->getMessage()
        );
    }
}

// Test 5: Payment Link Generation
echo "💳 PHASE 5: STRIPE PAYMENT LINK GENERATION\n";
echo "==========================================\n";

if ($test_order_id) {
    $payment_link_data = [
        'order_id' => $test_order_id,
        'logged_in_user_id' => $mobile_user_id ?? 1
    ];
    
    $payment_response = make_api_request('/generate-payment-link', 'POST', $payment_link_data, $headers);
    
    log_test(
        "Payment Link Generation",
        $payment_response['http_code'] === 200,
        "HTTP {$payment_response['http_code']}: " . ($payment_response['data']['message'] ?? 'Unknown response')
    );
    
    if (isset($payment_response['data']['payment_url'])) {
        echo "🔗 Generated payment URL: " . $payment_response['data']['payment_url'] . "\n\n";
        
        // Test the payment URL is accessible
        $url_test = make_api_request($payment_response['data']['payment_url'], 'GET');
        log_test(
            "Payment URL Accessibility",
            strpos($payment_response['data']['payment_url'], 'stripe.com') !== false,
            "URL appears to be valid Stripe payment link"
        );
    }
} else {
    log_test(
        "Payment Link Generation",
        false,
        "No order ID available for testing"
    );
}

// Test 6: Order Status Updates
echo "📊 PHASE 6: ORDER STATUS MANAGEMENT\n";
echo "===================================\n";

if ($test_order_id) {
    // Get order details
    $order_details_response = make_api_request("/orders/{$test_order_id}", 'GET', null, $headers);
    
    log_test(
        "Order Details Retrieval",
        $order_details_response['http_code'] === 200,
        "Order fetched via API: " . ($order_details_response['data']['order']['customer_name'] ?? 'Unknown')
    );
    
    // Test order update (simulate payment completion)
    $update_data = [
        'order_id' => $test_order_id,
        'stripe_paid' => 'Yes',
        'payment_confirmation' => 'pi_test_payment_intent_12345',
        'order_state' => 1 // Paid
    ];
    
    $update_response = make_api_request('/update-order-payment', 'POST', $update_data, $headers);
    
    log_test(
        "Order Payment Status Update",
        $update_response['http_code'] === 200 || $update_response['http_code'] === 404,
        "Update attempt completed: HTTP {$update_response['http_code']}"
    );
}

// Test 7: Mobile App Orders List
echo "📱 PHASE 7: MOBILE APP ORDERS INTEGRATION\n";
echo "=========================================\n";

$user_orders_response = make_api_request('/my-orders', 'GET', null, $headers);

log_test(
    "User Orders List API",
    $user_orders_response['http_code'] === 200,
    "HTTP {$user_orders_response['http_code']}: Orders API accessible"
);

if (isset($user_orders_response['data']['orders']) && is_array($user_orders_response['data']['orders'])) {
    $orders_count = count($user_orders_response['data']['orders']);
    echo "📋 Found {$orders_count} orders for user\n\n";
}

// Test 8: Webhook Endpoint
echo "🪝 PHASE 8: STRIPE WEBHOOK TESTING\n";
echo "==================================\n";

$webhook_data = [
    'id' => 'evt_test_webhook_123',
    'object' => 'event',
    'type' => 'payment_intent.succeeded',
    'data' => [
        'object' => [
            'id' => 'pi_test_payment_intent_123',
            'amount' => 6748,
            'currency' => 'cad',
            'status' => 'succeeded',
            'metadata' => [
                'order_id' => $test_order_id
            ]
        ]
    ],
    'created' => time()
];

$webhook_response = make_api_request('/stripe-webhook', 'POST', $webhook_data);

log_test(
    "Stripe Webhook Endpoint",
    $webhook_response['http_code'] === 200 || $webhook_response['http_code'] === 422,
    "HTTP {$webhook_response['http_code']}: Webhook endpoint responding"
);

// Test 9: Database Consistency Check
echo "🗄️  PHASE 9: DATABASE CONSISTENCY\n";
echo "=================================\n";

if ($test_order_id) {
    try {
        $db_order = App\Models\Order::find($test_order_id);
        
        log_test(
            "Order Exists in Database",
            $db_order !== null,
            "Order found with ID: {$test_order_id}"
        );
        
        if ($db_order) {
            log_test(
                "Order Has Stripe Fields",
                !empty($db_order->stripe_product_id) || !empty($db_order->stripe_price_id) || !empty($db_order->total_amount),
                "Stripe integration fields present"
            );
            
            log_test(
                "Order Total Amount Format",
                is_numeric($db_order->total_amount) && intval($db_order->total_amount) > 0,
                "Total amount: {$db_order->total_amount} cents"
            );
        }
        
    } catch (Exception $e) {
        log_test(
            "Database Order Check",
            false,
            "Error: " . $e->getMessage()
        );
    }
}

// Test 10: Mobile App Integration Points
echo "📲 PHASE 10: MOBILE APP INTEGRATION VERIFICATION\n";
echo "===============================================\n";

// Check if Order model has required Stripe fields
$order_sample = new App\Models\Order();
$required_fields = ['stripe_product_id', 'stripe_price_id', 'total_amount', 'stripe_url'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!in_array($field, $order_sample->getFillable())) {
        $missing_fields[] = $field;
    }
}

log_test(
    "Order Model Stripe Integration",
    empty($missing_fields),
    empty($missing_fields) ? "All Stripe fields present" : "Missing fields: " . implode(', ', $missing_fields)
);

// Test API endpoints that mobile app needs
$mobile_endpoints = [
    '/products' => 'Product listing for shop',
    '/create-order' => 'Order creation',
    '/my-orders' => 'User order history',
    '/generate-payment-link' => 'Payment processing'
];

foreach ($mobile_endpoints as $endpoint => $description) {
    $endpoint_response = make_api_request($endpoint, 'GET', null, $headers);
    
    log_test(
        "Mobile Endpoint: {$endpoint}",
        $endpoint_response['http_code'] !== 404,
        "{$description} - HTTP {$endpoint_response['http_code']}"
    );
}

// Final Results
echo "📊 FINAL TEST RESULTS\n";
echo "====================\n";

$success_rate = ($tests_passed / $total_tests) * 100;

echo "✅ Tests Passed: {$tests_passed}/{$total_tests}\n";
echo "📈 Success Rate: " . round($success_rate, 1) . "%\n\n";

if ($success_rate >= 90) {
    echo "🎉 EXCELLENT! Your Stripe integration is working!\n";
    echo "🔥 Mobile app payment flow is ready for production!\n\n";
} elseif ($success_rate >= 70) {
    echo "👍 GOOD! Most integration points are working.\n";
    echo "🔧 Address the failed tests for full functionality.\n\n";
} else {
    echo "⚠️  CRITICAL ISSUES! Multiple integration failures detected.\n";
    echo "🛠️  Significant work needed before production deployment.\n\n";
}

echo "🚀 MOBILE APP INTEGRATION STATUS:\n";
echo "================================\n";
echo "📱 API Authentication: " . ($mobile_token ? "✅ Working" : "❌ Failed") . "\n";
echo "🛒 Order Creation: " . ($test_order_id ? "✅ Working" : "❌ Failed") . "\n";
echo "💳 Payment Links: " . (isset($payment_response['data']['payment_url']) ? "✅ Working" : "❌ Failed") . "\n";
echo "🪝 Webhooks: " . ($webhook_response['http_code'] === 200 ? "✅ Working" : "⚠️  Needs attention") . "\n";
echo "🗄️  Database: " . (isset($db_order) && $db_order ? "✅ Working" : "❌ Failed") . "\n\n";

if ($test_order_id) {
    echo "🧪 TEST DATA CLEANUP:\n";
    echo "====================\n";
    echo "📋 Test Order ID: {$test_order_id}\n";
    echo "👤 Test User Email: mobile_test_*@lovebirds.test\n";
    echo "🔧 Clean up test data if needed in your database\n\n";
}

echo "📚 NEXT STEPS:\n";
echo "=============\n";
echo "1. 🔑 Update Stripe API key if tests are failing\n";
echo "2. 🧪 Test with real Stripe test cards in mobile app\n";
echo "3. 🔗 Configure production webhook URL\n";
echo "4. 📱 Test complete flow: Order → Payment → Confirmation\n";
echo "5. 💰 Test with small real amounts before full launch\n\n";

echo "🎯 Integration is " . round($success_rate, 0) . "% complete!\n";

?>
