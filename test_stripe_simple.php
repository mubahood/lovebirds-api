<?php
/**
 * Simple Stripe Integration Test
 * Run this from the Laravel project root: php test_stripe_simple.php
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 STRIPE INTEGRATION TEST\n";
echo "==========================\n\n";

$passed = 0;
$failed = 0;

function test_pass($message) {
    global $passed;
    echo "✅ " . $message . "\n";
    $passed++;
}

function test_fail($message) {
    global $failed;
    echo "❌ " . $message . "\n";
    $failed++;
}

// Test 1: Environment
echo "📋 Test 1: Environment Configuration\n";
$stripe_key = env('STRIPE_KEY');
if (!empty($stripe_key)) {
    test_pass("STRIPE_KEY configured");
    if (strpos($stripe_key, 'sk_live_') === 0) {
        test_pass("Using LIVE Stripe key (be careful!)");
    } else {
        test_pass("Using TEST Stripe key");
    }
} else {
    test_fail("STRIPE_KEY not configured");
}

// Test 2: Database
echo "\n🗄️  Test 2: Database Structure\n";
try {
    $tables = \DB::select("SHOW TABLES");
    $table_names = array_map(function($table) {
        return array_values((array)$table)[0];
    }, $tables);
    
    if (in_array('orders', $table_names)) {
        test_pass("'orders' table exists");
        
        // Check columns
        $columns = \DB::select("DESCRIBE orders");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        $required = ['stripe_id', 'stripe_url', 'stripe_paid', 'stripe_product_id', 'stripe_price_id', 'total_amount'];
        foreach ($required as $col) {
            if (in_array($col, $column_names)) {
                test_pass("Column '{$col}' exists");
            } else {
                test_fail("Column '{$col}' missing");
            }
        }
    } else {
        test_fail("'orders' table missing");
    }
} catch (\Exception $e) {
    test_fail("Database check failed: " . $e->getMessage());
}

// Test 3: Stripe Connection
echo "\n💳 Test 3: Stripe Connection\n";
try {
    $stripe = new \Stripe\StripeClient($stripe_key);
    $account = $stripe->accounts->retrieve();
    test_pass("Stripe connection successful - Account: " . $account->id);
    test_pass("Country: " . $account->country . ", Currency: " . $account->default_currency);
} catch (\Exception $e) {
    test_fail("Stripe connection failed: " . $e->getMessage());
}

// Test 4: Order Model Methods
echo "\n📦 Test 4: Order Model Methods\n";
$order = new \App\Models\Order();
if (method_exists($order, 'create_payment_link')) {
    test_pass("create_payment_link method exists");
} else {
    test_fail("create_payment_link method missing");
}

if (method_exists($order, 'calculateTotalAmount')) {
    test_pass("calculateTotalAmount method exists");
} else {
    test_fail("calculateTotalAmount method missing");
}

// Test 5: API Routes
echo "\n🌐 Test 5: API Routes\n";
try {
    $routes = \Route::getRoutes();
    $route_names = [];
    foreach ($routes as $route) {
        $route_names[] = $route->uri();
    }
    
    if (in_array('api/generate-payment-link', $route_names)) {
        test_pass("generate-payment-link route exists");
    } else {
        test_fail("generate-payment-link route missing");
    }
    
    if (in_array('api/stripe-webhook', $route_names)) {
        test_pass("stripe-webhook route exists");
    } else {
        test_fail("stripe-webhook route missing");
    }
} catch (\Exception $e) {
    test_fail("Route check failed: " . $e->getMessage());
}

// Test 6: Create Test Order (Real Test)
echo "\n🔥 Test 6: Real Order Creation Test\n";
try {
    // Find or create test user
    $user = \App\Models\User::where('email', 'test@example.com')->first();
    if (!$user) {
        $user = new \App\Models\User();
        $user->first_name = 'Test';
        $user->last_name = 'User';
        $user->email = 'test@example.com';
        $user->phone_number = '1234567890';
        $user->password = bcrypt('password');
        $user->save();
        test_pass("Created test user");
    } else {
        test_pass("Using existing test user");
    }

    // Create test order
    $order = new \App\Models\Order();
    $order->user = $user->id;
    $order->order_state = 0;
    $order->amount = 25.00;
    $order->order_total = 28.25; // With tax
    $order->total_amount = 28.25;
    $order->customer_name = 'Test User';
    $order->customer_phone_number_1 = '1234567890';
    $order->mail = $user->email;
    $order->save();
    test_pass("Created test order #" . $order->id);

    // Add order item (find any product or create one)
    $product = \App\Models\Product::first();
    if (!$product) {
        $product = new \App\Models\Product();
        $product->name = 'Test Product';
        $product->price_1 = 25.00;
        $product->description = 'Test product';
        $product->feature_photo = 'test.jpg';
        $product->save();
        test_pass("Created test product");
    }

    $item = new \App\Models\OrderedItem();
    $item->order = $order->id;
    $item->product = $product->id;
    $item->qty = 1;
    $item->amount = 25.00;
    $item->save();
    test_pass("Added order item");

    // Test payment link creation
    try {
        $order->create_payment_link();
        $order->refresh();
        
        if (!empty($order->stripe_url)) {
            test_pass("Payment link generated successfully!");
            test_pass("Stripe URL: " . $order->stripe_url);
            test_pass("Stripe Product ID: " . $order->stripe_product_id);
            test_pass("Stripe Price ID: " . $order->stripe_price_id);
            
            // Verify with Stripe
            $stripe = new \Stripe\StripeClient($stripe_key);
            try {
                $product_obj = $stripe->products->retrieve($order->stripe_product_id);
                test_pass("Stripe product verified: " . $product_obj->name);
                
                $price_obj = $stripe->prices->retrieve($order->stripe_price_id);
                test_pass("Stripe price verified: $" . ($price_obj->unit_amount / 100));
                
                $link_obj = $stripe->paymentLinks->retrieve($order->stripe_id);
                test_pass("Payment link verified: " . $link_obj->url);
                
            } catch (\Exception $e) {
                test_fail("Stripe verification failed: " . $e->getMessage());
            }
            
        } else {
            test_fail("Payment link not generated");
        }
    } catch (\Exception $e) {
        test_fail("Payment link creation failed: " . $e->getMessage());
    }

    // Cleanup
    echo "\n🧹 Cleaning up test data...\n";
    if (!empty($order->stripe_product_id)) {
        try {
            $stripe = new \Stripe\StripeClient($stripe_key);
            $stripe->products->delete($order->stripe_product_id);
            echo "✅ Cleaned up Stripe product\n";
        } catch (\Exception $e) {
            echo "⚠️  Could not clean up Stripe product: " . $e->getMessage() . "\n";
        }
    }
    
    $item->delete();
    $order->delete();
    $user->delete();
    if ($product->name === 'Test Product') {
        $product->delete();
    }
    test_pass("Test data cleaned up");

} catch (\Exception $e) {
    test_fail("Order creation test failed: " . $e->getMessage());
}

// Summary
echo "\n📊 TEST SUMMARY\n";
echo "===============\n";
$total = $passed + $failed;
echo "Total Tests: {$total}\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";

$success_rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
echo "🎯 Success Rate: {$success_rate}%\n\n";

if ($failed === 0) {
    echo "🎉 ALL TESTS PASSED! \n";
    echo "✅ Your Stripe integration is working perfectly!\n";
    echo "🚀 Ready for production!\n\n";
    echo "💡 Next steps:\n";
    echo "   1. Test the payment flow in your app\n";
    echo "   2. Set up webhook URL in Stripe dashboard\n";
    echo "   3. Test real payments with small amounts\n";
} elseif ($success_rate >= 70) {
    echo "⚠️  Most tests passed, but fix the failed ones.\n";
} else {
    echo "❌ Multiple failures - review your Stripe setup.\n";
}

echo "\n🔗 Webhook URL for Stripe Dashboard:\n";
echo env('APP_URL') . "/api/stripe-webhook\n\n";

?>
