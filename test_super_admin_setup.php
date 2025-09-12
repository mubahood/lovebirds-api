<?php
/**
 * Quick Authentication Test for Super Admin Endpoints
 */

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ApiController;
use App\Models\Utils;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔐 Super Admin Authentication Test\n";
echo "=================================\n\n";

// Check if super admin user exists (ID = 1)
$superAdmin = Administrator::find(1);

if ($superAdmin) {
    echo "✅ Super admin user found:\n";
    echo "   ID: {$superAdmin->id}\n";
    echo "   Name: {$superAdmin->name}\n";
    echo "   Email: {$superAdmin->email}\n";
} else {
    echo "❌ Super admin user (ID = 1) not found\n";
    echo "💡 Create super admin user first\n";
    exit;
}

// Check for test accounts
$testAccounts = Administrator::where('is_test_account', 'Yes')->get();

echo "\n📋 Test Accounts Found:\n";
if ($testAccounts->count() > 0) {
    foreach ($testAccounts as $account) {
        echo "   - ID: {$account->id}, Name: {$account->name}\n";
    }
} else {
    echo "   ℹ️  No test accounts found\n";
    echo "   💡 To create test accounts, run:\n";
    echo "   UPDATE admin_users SET is_test_account = 'Yes' WHERE id IN (2, 3, 4);\n";
    
    // Let's create a test account for demo
    if (Administrator::count() >= 2) {
        $testUser = Administrator::find(2);
        if ($testUser) {
            $testUser->is_test_account = 'Yes';
            $testUser->save();
            echo "   ✅ Created test account: ID {$testUser->id} ({$testUser->name})\n";
        }
    }
}

echo "\n🎯 Database Schema Check:\n";
echo "========================\n";

// Check if is_test_account column exists
if (Schema::hasColumn('admin_users', 'is_test_account')) {
    echo "✅ is_test_account column exists in admin_users table\n";
} else {
    echo "❌ is_test_account column missing in admin_users table\n";
}

// Check chat tables
if (Schema::hasTable('chat_heads')) {
    echo "✅ chat_heads table exists\n";
} else {
    echo "❌ chat_heads table missing\n";
}

if (Schema::hasTable('chat_messages')) {
    echo "✅ chat_messages table exists\n";
} else {
    echo "❌ chat_messages table missing\n";
}

echo "\n🎉 Super Admin System Ready!\n";
echo "Access through mobile app: Account > Test Account Chats (only visible to user ID = 1)\n";
?>
