<?php

/**
 * Create Default Company and Update Test Users
 * Fixes the company_id requirement for authentication
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;

echo "🏢 FIXING COMPANY REQUIREMENT FOR AUTHENTICATION\n";
echo "===============================================\n";

// Check if default company exists
$defaultCompany = Company::first();

if (!$defaultCompany) {
    echo "🏭 Creating default company...\n";
    $defaultCompany = Company::create([
        'name' => 'Lovebirds Dating',
        'email' => 'admin@lovebirds.com',
        'address' => 'Dating Platform',
        'phone_number' => '+1234567890',
        'details' => 'Default company for Lovebirds Dating app users',
        'status' => 'Active'
    ]);
    echo "✅ Created company: {$defaultCompany->name} (ID: {$defaultCompany->id})\n";
} else {
    echo "✅ Using existing company: {$defaultCompany->name} (ID: {$defaultCompany->id})\n";
}

// Update all test users to have the company_id
$testUserEmails = [
    'sarah.test@example.com',
    'michael.test@example.com', 
    'emma.test@example.com',
    'david.test@example.com',
    'jessica.test@example.com',
    'alex.test@example.com'
];

echo "\n👥 Updating test users with company_id...\n";
foreach ($testUserEmails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->company_id = $defaultCompany->id;
        $user->save();
        echo "✅ Updated {$user->name} - company_id: {$defaultCompany->id}\n";
    }
}

echo "\n🎉 Authentication setup complete!\n";
echo "Test users can now log in successfully.\n";

?>
