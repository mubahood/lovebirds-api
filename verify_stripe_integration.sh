#!/bin/bash

echo "🔧 STRIPE INTEGRATION VERIFICATION SCRIPT"
echo "=========================================="
echo ""

# Navigate to Laravel project
cd /Applications/MAMP/htdocs/lovebirds-api

# Check if Laravel project exists
if [ ! -f "artisan" ]; then
    echo "❌ Laravel project not found!"
    echo "Make sure you're running this from the correct directory"
    exit 1
fi

echo "📍 Current directory: $(pwd)"
echo ""

# Check .env file
echo "🔍 Checking .env configuration..."
if [ ! -f ".env" ]; then
    echo "❌ .env file not found!"
    echo "Copy .env.example to .env and configure it"
    exit 1
fi

# Check for required environment variables
echo "📋 Environment Variables:"
if grep -q "STRIPE_KEY=" .env; then
    echo "✅ STRIPE_KEY found"
else
    echo "❌ STRIPE_KEY missing from .env"
fi

if grep -q "STRIPE_WEBHOOK_SECRET=" .env; then
    echo "✅ STRIPE_WEBHOOK_SECRET found"
else
    echo "⚠️  STRIPE_WEBHOOK_SECRET missing (optional for testing)"
fi

echo ""

# Check database connection
echo "🗄️  Testing database connection..."
php artisan migrate:status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed"
    echo "Run: php artisan migrate"
fi

echo ""

# Check required tables
echo "🔍 Checking database tables..."
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

try {
    \$tables = ['orders', 'ordered_items', 'users'];
    foreach (\$tables as \$table) {
        if (Schema::hasTable(\$table)) {
            echo \"✅ Table '\$table' exists\n\";
        } else {
            echo \"❌ Table '\$table' missing\n\";
        }
    }
    
    // Check specific columns
    if (Schema::hasTable('orders')) {
        \$columns = ['stripe_product_id', 'stripe_price_id', 'total_amount'];
        foreach (\$columns as \$column) {
            if (Schema::hasColumn('orders', \$column)) {
                echo \"✅ Column 'orders.\$column' exists\n\";
            } else {
                echo \"❌ Column 'orders.\$column' missing\n\";
            }
        }
    }
} catch (Exception \$e) {
    echo \"❌ Database error: \" . \$e->getMessage() . \"\n\";
}
"

echo ""

# Test Stripe API key
echo "🔑 Testing Stripe API key..."
php test_stripe_key.php

echo ""

# Check routes
echo "🛤️  Checking API routes..."
php artisan route:list | grep -E "(generate-payment-link|stripe-webhook)" || echo "⚠️  Custom Stripe routes may not be registered"

echo ""

# Test order creation endpoint
echo "🧪 Testing order creation API..."
curl -X POST http://localhost:8888/lovebirds-api/public/api/create-order \
  -H "Content-Type: application/json" \
  -H "Tok: your_test_token" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "quantity": 2,
        "price": 25.99
      }
    ],
    "logged_in_user_id": 1
  }' \
  -w "\nHTTP Status: %{http_code}\n" \
  -s | head -20

echo ""

echo "🎯 INTEGRATION STATUS SUMMARY"
echo "=============================="
echo "If all tests pass above, your integration is ready!"
echo ""
echo "📝 NEXT STEPS:"
echo "1. Update your Stripe API key if it's expired"
echo "2. Test payment flow with test cards"
echo "3. Set up webhook URL in Stripe dashboard"
echo "4. Test webhook functionality"
echo ""
echo "🧪 TEST CARDS (for testing):"
echo "- Success: 4242424242424242"
echo "- Decline: 4000000000000002"
echo "- 3D Secure: 4000000000003220"
echo ""
echo "🔗 Webhook URL for Stripe:"
echo "$(grep APP_URL .env | cut -d'=' -f2)/api/stripe-webhook"
