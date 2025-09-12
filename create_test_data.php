<?php
/**
 * Create Test Data for Super Admin Chat Management Testing
 */

require __DIR__ . '/vendor/autoload.php';

use Encore\Admin\Auth\Database\Administrator;
use App\Models\ChatHead;
use App\Models\ChatMessage;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎭 Creating Test Data for Super Admin Chat Management\n";
echo "===================================================\n\n";

// Step 1: Ensure we have test accounts
echo "📝 Step 1: Setting up test accounts\n";
echo "===================================\n";

$testAccountIds = [2, 3, 4];
foreach ($testAccountIds as $id) {
    $user = Administrator::find($id);
    if ($user) {
        $user->is_test_account = 'Yes';
        $user->save();
        echo "✅ Set user ID {$id} ({$user->name}) as test account\n";
    }
}

// Step 2: Check existing chat data
echo "\n📊 Step 2: Checking existing chat data\n";
echo "======================================\n";

$existingChatHeads = ChatHead::whereIn('customer_id', $testAccountIds)
    ->orWhereIn('product_owner_id', $testAccountIds)
    ->count();

echo "✅ Found {$existingChatHeads} existing chat heads involving test accounts\n";

// Step 3: Display sample data for testing
echo "\n📋 Step 3: Sample test account data\n";
echo "===================================\n";

$testAccounts = Administrator::where('is_test_account', 'Yes')->get();
foreach ($testAccounts as $account) {
    echo "Test Account: ID {$account->id} - {$account->name} ({$account->email})\n";
    
    // Check for chat heads involving this test account
    $chatHeads = ChatHead::where('customer_id', $account->id)
        ->orWhere('product_owner_id', $account->id)
        ->limit(3)
        ->get();
    
    if ($chatHeads->count() > 0) {
        echo "  Conversations:\n";
        foreach ($chatHeads as $head) {
            $otherUserId = ($head->customer_id == $account->id) ? $head->product_owner_id : $head->customer_id;
            $otherUser = Administrator::find($otherUserId) ?? User::find($otherUserId);
            $otherUserName = $otherUser ? $otherUser->name : 'Unknown User';
            
            $messageCount = ChatMessage::where('chat_head_id', $head->id)->count();
            echo "    - Chat Head ID {$head->id} with {$otherUserName} ({$messageCount} messages)\n";
        }
    } else {
        echo "  No active conversations\n";
    }
}

// Step 4: Instructions for testing
echo "\n🧪 Step 4: Testing Instructions\n";
echo "===============================\n";
echo "Now you can test the super admin features:\n\n";

echo "1. FLUTTER APP TESTING:\n";
echo "   - Login as super admin (user ID = 1)\n";
echo "   - Go to Account tab\n";
echo "   - Look for 'Super Admin Tools' section\n";
echo "   - Tap 'Test Account Chats'\n";
echo "   - Should see list of test account conversations\n\n";

echo "2. DIRECT API TESTING:\n";
echo "   curl -X GET 'http://localhost:8888/lovebirds-api/api/super-admin-chat-heads' \\\n";
echo "        -H 'Content-Type: application/json' \\\n";
echo "        -H 'Authorization: Bearer <super_admin_token>'\n\n";

echo "3. EXPECTED BEHAVIOR:\n";
echo "   - Super admin can see all test account conversations\n";
echo "   - Can view message history\n";
echo "   - Can send messages on behalf of test accounts\n";
echo "   - Regular users cannot access these features\n\n";

echo "4. TEST SCENARIOS:\n";
echo "   a) Access Control: Non-super admin users should be denied\n";
echo "   b) Chat Viewing: Super admin should see test account chats\n";
echo "   c) Message Sending: Super admin should be able to send as test account\n";
echo "   d) Real-time Updates: Changes should reflect immediately\n\n";

echo "🎯 Test data setup complete!\n";
echo "The system is ready for comprehensive testing.\n";
?>
