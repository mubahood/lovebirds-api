<?php
/**
 * Stripe Integration Test Suite
 * Tests the complete Stripe payment integration functionality
 */

require_once 'vendor/autoload.php';

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderedItem;
use Illuminate\Support\Facades\DB;

class StripeIntegrationTester
{
    private $stripe_key;
    private $base_url;
    private $test_results = [];

    public function __construct()
    {
        $this->stripe_key = env('STRIPE_KEY');
        $this->base_url = env('APP_URL', 'http://localhost:8888/lovebirds-api/public');
    }

    public function runAllTests()
    {
        echo "🚀 STRIPE INTEGRATION TEST SUITE\n";
        echo "================================\n\n";

        $this->testEnvironmentConfiguration();
        $this->testDatabaseStructure();
        $this->testStripeConnection();
        $this->testOrderCreation();
        $this->testPaymentLinkGeneration();
        $this->testAPIEndpoints();
        $this->testWebhookFunctionality();

        $this->printSummary();
    }

    private function testEnvironmentConfiguration()
    {
        echo "📋 Test 1: Environment Configuration\n";
        echo "------------------------------------\n";

        // Test Stripe Key
        if (!empty($this->stripe_key)) {
            $this->pass("✅ STRIPE_KEY is configured");
            
            if (strpos($this->stripe_key, 'sk_live_') === 0) {
                $this->pass("✅ Using LIVE Stripe key");
                echo "   🟡 WARNING: You're using a LIVE key! Make sure you're ready for real payments.\n";
            } elseif (strpos($this->stripe_key, 'sk_test_') === 0) {
                $this->pass("✅ Using TEST Stripe key");
            } else {
                $this->fail("❌ Invalid STRIPE_KEY format");
            }
        } else {
            $this->fail("❌ STRIPE_KEY not configured");
        }

        // Test APP_URL
        $app_url = env('APP_URL');
        if (!empty($app_url)) {
            $this->pass("✅ APP_URL is configured: {$app_url}");
        } else {
            $this->fail("❌ APP_URL not configured");
        }

        echo "\n";
    }

    private function testDatabaseStructure()
    {
        echo "🗄️  Test 2: Database Structure\n";
        echo "-----------------------------\n";

        try {
            // Check orders table exists
            if (Schema::hasTable('orders')) {
                $this->pass("✅ 'orders' table exists");
            } else {
                $this->fail("❌ 'orders' table missing");
                return;
            }

            // Check required columns
            $required_columns = [
                'stripe_id', 'stripe_url', 'stripe_paid', 
                'stripe_product_id', 'stripe_price_id', 'total_amount'
            ];

            foreach ($required_columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $this->pass("✅ Column '{$column}' exists");
                } else {
                    $this->fail("❌ Column '{$column}' missing");
                }
            }

            // Check ordered_items table
            if (Schema::hasTable('ordered_items')) {
                $this->pass("✅ 'ordered_items' table exists");
            } else {
                $this->fail("❌ 'ordered_items' table missing");
            }

        } catch (\Exception $e) {
            $this->fail("❌ Database connection failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function testStripeConnection()
    {
        echo "💳 Test 3: Stripe Connection\n";
        echo "---------------------------\n";

        try {
            $stripe = new \Stripe\StripeClient($this->stripe_key);
            
            // Test account retrieval
            $account = $stripe->accounts->retrieve();
            $this->pass("✅ Stripe connection successful");
            $this->pass("✅ Account ID: " . $account->id);
            $this->pass("✅ Country: " . $account->country);
            $this->pass("✅ Currency: " . $account->default_currency);

            // Test product creation (and cleanup)
            $test_product = $stripe->products->create([
                'name' => 'Test Product - ' . time(),
                'description' => 'Test product for integration verification'
            ]);
            $this->pass("✅ Can create Stripe products");

            // Test price creation
            $test_price = $stripe->prices->create([
                'unit_amount' => 2000, // $20.00
                'currency' => 'cad',
                'product' => $test_product->id,
            ]);
            $this->pass("✅ Can create Stripe prices");

            // Test payment link creation
            $test_payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                    [
                        'price' => $test_price->id,
                        'quantity' => 1,
                    ],
                ],
            ]);
            $this->pass("✅ Can create Stripe payment links");
            $this->pass("✅ Test payment link: " . $test_payment_link->url);

            // Cleanup test objects
            $stripe->products->delete($test_product->id);
            $this->pass("✅ Stripe cleanup successful");

        } catch (\Exception $e) {
            $this->fail("❌ Stripe connection failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function testOrderCreation()
    {
        echo "📦 Test 4: Order Creation & Payment Link Auto-Generation\n";
        echo "-------------------------------------------------------\n";

        try {
            // Find or create a test user
            $test_user = User::where('email', 'test@stripe-integration.com')->first();
            if (!$test_user) {
                $test_user = User::create([
                    'first_name' => 'Stripe',
                    'last_name' => 'Test',
                    'email' => 'test@stripe-integration.com',
                    'password' => bcrypt('password'),
                    'phone_number' => '1234567890',
                ]);
                $this->pass("✅ Created test user: " . $test_user->email);
            } else {
                $this->pass("✅ Using existing test user: " . $test_user->email);
            }

            // Find or create a test product
            $test_product = Product::where('name', 'LIKE', 'Stripe Test Product%')->first();
            if (!$test_product) {
                $test_product = Product::create([
                    'name' => 'Stripe Test Product - ' . time(),
                    'description' => 'Test product for Stripe integration',
                    'price_1' => '25.00',
                    'feature_photo' => 'test-product.jpg',
                    'category' => 1,
                ]);
                $this->pass("✅ Created test product: " . $test_product->name);
            } else {
                $this->pass("✅ Using existing test product: " . $test_product->name);
            }

            // Create test order
            $test_order = Order::create([
                'user' => $test_user->id,
                'order_state' => 0,
                'temporary_id' => 0,
                'amount' => 25.00,
                'order_total' => 28.25, // Including 13% tax
                'payment_confirmation' => '',
                'description' => 'Stripe integration test order',
                'mail' => $test_user->email,
                'customer_name' => $test_user->first_name . ' ' . $test_user->last_name,
                'customer_phone_number_1' => $test_user->phone_number,
            ]);

            $this->pass("✅ Created test order: #" . $test_order->id);

            // Create order item
            $order_item = OrderedItem::create([
                'order' => $test_order->id,
                'product' => $test_product->id,
                'qty' => 1,
                'amount' => $test_product->price_1,
                'color' => 'N/A',
                'size' => 'N/A',
            ]);

            $this->pass("✅ Created order item");

            // Test automatic payment link generation
            sleep(2); // Wait for any background processing
            $test_order->refresh();

            if (!empty($test_order->stripe_url)) {
                $this->pass("✅ Payment link auto-generated: " . $test_order->stripe_url);
                $this->pass("✅ Stripe ID: " . $test_order->stripe_id);
                $this->pass("✅ Stripe Product ID: " . $test_order->stripe_product_id);
                $this->pass("✅ Stripe Price ID: " . $test_order->stripe_price_id);
            } else {
                $this->fail("❌ Payment link was not auto-generated");
                
                // Try manual generation
                try {
                    $test_order->create_payment_link();
                    $test_order->refresh();
                    
                    if (!empty($test_order->stripe_url)) {
                        $this->pass("✅ Manual payment link generation successful");
                    } else {
                        $this->fail("❌ Manual payment link generation failed");
                    }
                } catch (\Exception $e) {
                    $this->fail("❌ Manual payment link generation error: " . $e->getMessage());
                }
            }

            // Store test order ID for later tests
            $this->test_order_id = $test_order->id;

        } catch (\Exception $e) {
            $this->fail("❌ Order creation test failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function testPaymentLinkGeneration()
    {
        echo "🔗 Test 5: Payment Link Generation Details\n";
        echo "-----------------------------------------\n";

        if (!isset($this->test_order_id)) {
            $this->fail("❌ No test order available");
            return;
        }

        try {
            $order = Order::find($this->test_order_id);
            
            if ($order && !empty($order->stripe_url)) {
                // Verify Stripe objects exist
                $stripe = new \Stripe\StripeClient($this->stripe_key);
                
                // Check product
                if (!empty($order->stripe_product_id)) {
                    try {
                        $product = $stripe->products->retrieve($order->stripe_product_id);
                        $this->pass("✅ Stripe product exists: " . $product->name);
                        
                        // Verify product name format
                        $expected_name = "Order #{$order->id} - ";
                        if (strpos($product->name, $expected_name) === 0) {
                            $this->pass("✅ Product name format correct: " . $product->name);
                        } else {
                            $this->fail("❌ Product name format incorrect: " . $product->name);
                        }
                    } catch (\Exception $e) {
                        $this->fail("❌ Stripe product not found: " . $e->getMessage());
                    }
                }

                // Check price
                if (!empty($order->stripe_price_id)) {
                    try {
                        $price = $stripe->prices->retrieve($order->stripe_price_id);
                        $this->pass("✅ Stripe price exists: CAD " . ($price->unit_amount / 100));
                        
                        // Verify price amount
                        $expected_amount = intval(floatval($order->total_amount) * 100);
                        if ($price->unit_amount == $expected_amount) {
                            $this->pass("✅ Price amount correct");
                        } else {
                            $this->fail("❌ Price amount mismatch. Expected: {$expected_amount}, Got: {$price->unit_amount}");
                        }
                    } catch (\Exception $e) {
                        $this->fail("❌ Stripe price not found: " . $e->getMessage());
                    }
                }

                // Check payment link
                try {
                    $payment_link = $stripe->paymentLinks->retrieve($order->stripe_id);
                    $this->pass("✅ Payment link exists and accessible");
                    $this->pass("✅ Payment link URL: " . $payment_link->url);
                    
                    if ($payment_link->active) {
                        $this->pass("✅ Payment link is active");
                    } else {
                        $this->fail("❌ Payment link is inactive");
                    }
                } catch (\Exception $e) {
                    $this->fail("❌ Payment link not accessible: " . $e->getMessage());
                }

            } else {
                $this->fail("❌ No payment link to test");
            }

        } catch (\Exception $e) {
            $this->fail("❌ Payment link verification failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function testAPIEndpoints()
    {
        echo "🌐 Test 6: API Endpoints\n";
        echo "----------------------\n";

        // Test generate-payment-link endpoint
        if (isset($this->test_order_id)) {
            try {
                $url = $this->base_url . "/api/generate-payment-link";
                $data = json_encode(['order_id' => $this->test_order_id]);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200) {
                    $response_data = json_decode($response, true);
                    if ($response_data && isset($response_data['code']) && $response_data['code'] === 1) {
                        $this->pass("✅ generate-payment-link endpoint working");
                    } else {
                        $this->fail("❌ generate-payment-link endpoint returned error: " . ($response_data['message'] ?? 'Unknown error'));
                    }
                } else {
                    $this->fail("❌ generate-payment-link endpoint HTTP error: " . $http_code);
                }
                
            } catch (\Exception $e) {
                $this->fail("❌ generate-payment-link endpoint test failed: " . $e->getMessage());
            }
        }

        // Test webhook endpoint
        try {
            $url = $this->base_url . "/api/stripe-webhook";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['type' => 'test_event']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $this->pass("✅ stripe-webhook endpoint accessible");
            } else {
                $this->fail("❌ stripe-webhook endpoint HTTP error: " . $http_code);
            }
            
        } catch (\Exception $e) {
            $this->fail("❌ stripe-webhook endpoint test failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function testWebhookFunctionality()
    {
        echo "🔔 Test 7: Webhook Functionality (Simulation)\n";
        echo "--------------------------------------------\n";

        if (!isset($this->test_order_id)) {
            $this->fail("❌ No test order available");
            return;
        }

        try {
            $order = Order::find($this->test_order_id);
            
            // Simulate payment completion webhook
            $original_status = $order->stripe_paid;
            $original_state = $order->order_state;
            
            // Create a mock payment_link completion event
            $mock_payment_link = [
                'id' => $order->stripe_id,
                'object' => 'payment_link',
                'metadata' => [
                    'order_id' => $order->id
                ]
            ];

            // Test the webhook handler directly
            $apiController = new \App\Http\Controllers\ApiController();
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($apiController);
            $method = $reflection->getMethod('handlePaymentLinkCompleted');
            $method->setAccessible(true);
            $method->invokeArgs($apiController, [$mock_payment_link]);

            // Refresh order and check if status updated
            $order->refresh();
            
            if ($order->stripe_paid === 'Yes' && $order->order_state == 1) {
                $this->pass("✅ Webhook handler correctly updates order status");
            } else {
                $this->fail("❌ Webhook handler did not update order status correctly");
            }

            // Reset order status for cleanup
            $order->stripe_paid = $original_status;
            $order->order_state = $original_state;
            $order->save();
            
        } catch (\Exception $e) {
            $this->fail("❌ Webhook functionality test failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function pass($message)
    {
        echo $message . "\n";
        $this->test_results['passed']++;
    }

    private function fail($message)
    {
        echo $message . "\n";
        $this->test_results['failed']++;
    }

    private function printSummary()
    {
        $total_tests = ($this->test_results['passed'] ?? 0) + ($this->test_results['failed'] ?? 0);
        $passed = $this->test_results['passed'] ?? 0;
        $failed = $this->test_results['failed'] ?? 0;

        echo "📊 TEST SUMMARY\n";
        echo "==============\n";
        echo "Total Tests: {$total_tests}\n";
        echo "✅ Passed: {$passed}\n";
        echo "❌ Failed: {$failed}\n";
        
        $success_rate = $total_tests > 0 ? round(($passed / $total_tests) * 100, 1) : 0;
        echo "🎯 Success Rate: {$success_rate}%\n\n";

        if ($failed === 0) {
            echo "🎉 ALL TESTS PASSED! Your Stripe integration is working perfectly!\n";
            echo "✅ Ready for production use!\n\n";
        } elseif ($success_rate >= 80) {
            echo "⚠️  Most tests passed, but there are some issues to address.\n";
            echo "🔧 Check the failed tests above and fix them.\n\n";
        } else {
            echo "❌ Several tests failed. Your Stripe integration needs attention.\n";
            echo "🚨 Please review and fix the issues before using in production.\n\n";
        }

        // Cleanup test data
        if (isset($this->test_order_id)) {
            echo "🧹 Cleaning up test data...\n";
            try {
                $order = Order::find($this->test_order_id);
                if ($order) {
                    // Delete Stripe objects first
                    if (!empty($order->stripe_product_id)) {
                        $stripe = new \Stripe\StripeClient($this->stripe_key);
                        try {
                            $stripe->products->delete($order->stripe_product_id);
                            echo "✅ Deleted test Stripe product\n";
                        } catch (\Exception $e) {
                            echo "⚠️  Could not delete Stripe product: " . $e->getMessage() . "\n";
                        }
                    }

                    // Delete order items
                    OrderedItem::where('order', $order->id)->delete();
                    
                    // Delete order
                    $order->delete();
                    echo "✅ Deleted test order\n";
                }

                // Delete test user if created
                $test_user = User::where('email', 'test@stripe-integration.com')->first();
                if ($test_user) {
                    $test_user->delete();
                    echo "✅ Deleted test user\n";
                }

            } catch (\Exception $e) {
                echo "⚠️  Cleanup warning: " . $e->getMessage() . "\n";
            }
        }

        echo "\n🏁 Test suite completed!\n";
    }
}

// Run the tests
$tester = new StripeIntegrationTester();
$tester->runAllTests();

?>
