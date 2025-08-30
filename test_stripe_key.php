<?php
/**
 * Stripe API Key Test & Update Guide
 * This script helps you test and update your Stripe configuration
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔑 STRIPE API KEY TEST\n";
echo "=====================\n\n";

$stripe_key = env('STRIPE_KEY');

if (empty($stripe_key)) {
    echo "❌ No STRIPE_KEY found in .env file\n";
    echo "📝 Add this line to your .env file:\n";
    echo "STRIPE_KEY=sk_test_your_key_here\n\n";
    exit;
}

echo "📋 Current Configuration:\n";
echo "STRIPE_KEY: " . substr($stripe_key, 0, 8) . "..." . substr($stripe_key, -6) . "\n";

if (strpos($stripe_key, 'sk_live_') === 0) {
    echo "🔴 LIVE KEY DETECTED\n";
    echo "⚠️  You're using a LIVE Stripe key!\n";
    echo "💰 Real payments will be processed!\n";
    echo "🔒 Make sure you're ready for production!\n\n";
} elseif (strpos($stripe_key, 'sk_test_') === 0) {
    echo "🟢 TEST KEY DETECTED\n";
    echo "✅ You're using a TEST Stripe key\n";
    echo "🧪 Only test payments will be processed\n\n";
} else {
    echo "❓ UNKNOWN KEY FORMAT\n";
    echo "⚠️  Key should start with 'sk_test_' or 'sk_live_'\n\n";
}

// Test Stripe connection
echo "🔌 Testing Stripe Connection...\n";
try {
    $stripe = new \Stripe\StripeClient($stripe_key);
    $account = $stripe->accounts->retrieve();
    
    echo "✅ Connection successful!\n";
    echo "📍 Account ID: " . $account->id . "\n";
    echo "🌍 Country: " . $account->country . "\n";
    echo "💱 Currency: " . $account->default_currency . "\n";
    echo "📧 Email: " . ($account->email ?? 'N/A') . "\n";
    echo "🏢 Business Type: " . ($account->business_type ?? 'N/A') . "\n\n";
    
    // Test product creation
    echo "🧪 Testing Product Creation...\n";
    $test_product = $stripe->products->create([
        'name' => 'LoveBirds Test Product - ' . date('Y-m-d H:i:s'),
        'description' => 'Test product for API verification'
    ]);
    echo "✅ Product created: " . $test_product->id . "\n";
    
    // Test price creation
    echo "🏷️  Testing Price Creation...\n";
    $test_price = $stripe->prices->create([
        'unit_amount' => 2500, // $25.00
        'currency' => 'cad',
        'product' => $test_product->id,
    ]);
    echo "✅ Price created: " . $test_price->id . " (CAD " . ($test_price->unit_amount / 100) . ")\n";
    
    // Test payment link creation
    echo "🔗 Testing Payment Link Creation...\n";
    $test_payment_link = $stripe->paymentLinks->create([
        'line_items' => [
            [
                'price' => $test_price->id,
                'quantity' => 1,
            ],
        ],
        'metadata' => [
            'test' => 'true',
            'source' => 'lovebirds_api_test'
        ]
    ]);
    echo "✅ Payment link created: " . $test_payment_link->url . "\n";
    echo "🆔 Payment link ID: " . $test_payment_link->id . "\n\n";
    
    // Cleanup
    echo "🧹 Cleaning up test objects...\n";
    $stripe->products->delete($test_product->id);
    echo "✅ Test objects cleaned up\n\n";
    
    echo "🎉 ALL STRIPE TESTS PASSED!\n";
    echo "✅ Your Stripe integration is ready to use!\n\n";
    
    if (strpos($stripe_key, 'sk_test_') === 0) {
        echo "💡 NEXT STEPS FOR TESTING:\n";
        echo "1. Create an order in your app\n";
        echo "2. Check that payment link is generated\n";
        echo "3. Use test card: 4242424242424242\n";
        echo "4. Any future expiry date and CVC\n";
        echo "5. Test webhook with ngrok or similar\n\n";
    } else {
        echo "💡 PRODUCTION READY!\n";
        echo "1. Set up webhook URL in Stripe dashboard\n";
        echo "2. Test with small real amounts first\n";
        echo "3. Monitor payments carefully\n\n";
    }
    
    echo "🔗 Webhook URL for Stripe Dashboard:\n";
    echo env('APP_URL') . "/api/stripe-webhook\n\n";
    
} catch (\Stripe\Exception\AuthenticationException $e) {
    echo "❌ Authentication failed!\n";
    echo "🔑 Your Stripe API key is invalid or expired\n\n";
    
    echo "🛠️  HOW TO FIX:\n";
    echo "1. Log in to your Stripe Dashboard: https://dashboard.stripe.com/\n";
    echo "2. Go to Developers > API Keys\n";
    echo "3. Copy your Secret Key (starts with sk_test_ or sk_live_)\n";
    echo "4. Update your .env file:\n";
    echo "   STRIPE_KEY=sk_test_your_new_key_here\n";
    echo "5. Clear Laravel config cache:\n";
    echo "   php artisan config:clear\n\n";
    
} catch (\Exception $e) {
    echo "❌ Stripe connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "🛠️  POSSIBLE SOLUTIONS:\n";
    echo "1. Check your internet connection\n";
    echo "2. Verify your API key in Stripe dashboard\n";
    echo "3. Make sure your server can reach stripe.com\n";
    echo "4. Check if you have firewall restrictions\n\n";
}

echo "📚 STRIPE INTEGRATION DOCUMENTATION:\n";
echo "- Stripe API: https://stripe.com/docs/api\n";
echo "- Payment Links: https://stripe.com/docs/payment-links\n";
echo "- Webhooks: https://stripe.com/docs/webhooks\n";
echo "- Test Cards: https://stripe.com/docs/testing#cards\n\n";

?>
