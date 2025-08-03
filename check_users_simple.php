<?php

require_once 'vendor/autoload.php';
use App\Models\User;

echo "=== CHECKING AVAILABLE USERS ===\n";
$users = User::take(10)->get(['id', 'name', 'email']);
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
}

// Try to create a simple user for testing if none exist
if ($users->count() == 0) {
    echo "\nNo users found. Creating test user...\n";
    $testUser = new User();
    $testUser->name = 'Test User';
    $testUser->email = 'test@example.com';
    $testUser->password = bcrypt('password123');
    $testUser->save();
    echo "Test user created with ID: {$testUser->id}\n";
}

?>
