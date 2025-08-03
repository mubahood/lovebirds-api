<?php

/**
 * Get User IDs for Photo Likes Testing
 * Fetches the actual user IDs from our test data for proper testing
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Get our test users
$testUserEmails = [
    'sarah.test@example.com',
    'michael.test@example.com', 
    'emma.test@example.com',
    'david.test@example.com',
    'jessica.test@example.com',
    'alex.test@example.com'
];

echo "🔍 FETCHING TEST USER IDS\n";
echo "=========================\n";

$userIds = [];
foreach ($testUserEmails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $userIds[] = $user->id;
        echo "✅ {$user->name} (ID: {$user->id}) - {$email}\n";
    } else {
        echo "❌ User not found: {$email}\n";
    }
}

echo "\n📊 User IDs for testing: " . implode(', ', $userIds) . "\n";
echo "📝 Use these IDs in your test scripts!\n";

// Create a simple config file for the test script
$config = [
    'user_ids' => $userIds,
    'user_emails' => $testUserEmails
];

file_put_contents('test_config.json', json_encode($config, JSON_PRETTY_PRINT));
echo "💾 Saved test configuration to test_config.json\n";

?>
