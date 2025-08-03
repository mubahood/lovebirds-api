<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

try {
    echo "🔍 CHECKING TEST USER PASSWORDS\n";
    echo "===============================\n";
    
    $test_emails = [
        'sarah.test@example.com',
        'michael.test@example.com', 
        'emma.test@example.com',
        'david.test@example.com'
    ];
    
    foreach ($test_emails as $email) {
        $user = User::where('email', $email)->first();
        if ($user) {
            echo "📧 {$user->email} (ID: {$user->id})\n";
            echo "   Password Hash: " . substr($user->password, 0, 20) . "...\n";
            
            // Test common passwords
            $test_passwords = ['testpass123', 'password123', 'test123', 'password'];
            foreach ($test_passwords as $password) {
                if (Hash::check($password, $user->password)) {
                    echo "   ✅ Password: $password\n";
                    break;
                }
            }
            echo "\n";
        }
    }
    
    // Let's just reset all test users to have 'testpass123'
    echo "🔧 RESETTING ALL TEST USER PASSWORDS\n";
    echo "===================================\n";
    
    foreach ($test_emails as $email) {
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make('testpass123');
            $user->save();
            echo "✅ Reset password for {$user->email}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
