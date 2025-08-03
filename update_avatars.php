<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// List of available avatar images (1.jpg through 50.jpg)
$availableAvatars = [];
for ($i = 1; $i <= 50; $i++) {
    $availableAvatars[] = "images/{$i}.jpg";
}

// Get all users without proper avatars
$users = User::whereNull('avatar')
    ->orWhere('avatar', '')
    ->orWhere('avatar', 'images/1.jpg') // Reset users who all have the same default
    ->get();

echo "Found " . count($users) . " users to update with avatars.\n";

$avatarIndex = 0;

foreach ($users as $user) {
    // Assign avatar in a round-robin fashion
    $selectedAvatar = $availableAvatars[$avatarIndex % count($availableAvatars)];
    
    $user->avatar = $selectedAvatar;
    $user->save();
    
    echo "Updated user {$user->id} ({$user->name}) with avatar: {$selectedAvatar}\n";
    
    $avatarIndex++;
}

echo "Avatar assignment completed successfully!\n";
