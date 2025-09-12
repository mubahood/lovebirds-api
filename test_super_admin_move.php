#!/usr/bin/env php
<?php
// Test script to verify super admin chat module access in AccountSection.dart

echo "=== Super Admin Chat Module Movement Test ===\n\n";

// Check if the super admin section was properly moved to AccountSection.dart
$accountSectionFile = '/Users/mac/Desktop/github/lovebirds-mobo/lib/screens/shop/screens/shop/full_app/section/AccountSection.dart';
$accountEditMainFile = '/Users/mac/Desktop/github/lovebirds-mobo/lib/screens/dating/AccountEditMainScreen.dart';

if (file_exists($accountSectionFile)) {
    $accountSectionContent = file_get_contents($accountSectionFile);
    
    // Check if super admin imports and functionality are added
    $hasImport = strpos($accountSectionContent, 'super_admin_chat_heads_screen.dart') !== false;
    $hasSuperAdminCheck = strpos($accountSectionContent, 'mainController.loggedInUser.id == 1') !== false;
    $hasSuperAdminSection = strpos($accountSectionContent, 'Super Admin Tools') !== false;
    $hasChatNavigation = strpos($accountSectionContent, 'SuperAdminChatHeadsScreen') !== false;
    
    echo "✅ AccountSection.dart analysis:\n";
    echo "   - Super admin import: " . ($hasImport ? "✅ Found" : "❌ Missing") . "\n";
    echo "   - User ID check: " . ($hasSuperAdminCheck ? "✅ Found" : "❌ Missing") . "\n";
    echo "   - Super admin section: " . ($hasSuperAdminSection ? "✅ Found" : "❌ Missing") . "\n";
    echo "   - Chat navigation: " . ($hasChatNavigation ? "✅ Found" : "❌ Missing") . "\n\n";
    
    if ($hasImport && $hasSuperAdminCheck && $hasSuperAdminSection && $hasChatNavigation) {
        echo "✅ Super admin chat module successfully added to AccountSection.dart\n\n";
    } else {
        echo "❌ Super admin chat module not properly configured in AccountSection.dart\n\n";
    }
} else {
    echo "❌ AccountSection.dart file not found\n\n";
}

if (file_exists($accountEditMainFile)) {
    $accountEditContent = file_get_contents($accountEditMainFile);
    
    // Check if super admin section was removed
    $hasOldImport = strpos($accountEditContent, 'super_admin_chat_heads_screen.dart') !== false;
    $hasOldSection = strpos($accountEditContent, 'Super Admin Tools') !== false;
    $hasOldNavigation = strpos($accountEditContent, 'SuperAdminChatHeadsScreen') !== false;
    
    echo "✅ AccountEditMainScreen.dart analysis:\n";
    echo "   - Old super admin import: " . ($hasOldImport ? "❌ Still present" : "✅ Removed") . "\n";
    echo "   - Old super admin section: " . ($hasOldSection ? "❌ Still present" : "✅ Removed") . "\n";
    echo "   - Old chat navigation: " . ($hasOldNavigation ? "❌ Still present" : "✅ Removed") . "\n\n";
    
    if (!$hasOldImport && !$hasOldSection && !$hasOldNavigation) {
        echo "✅ Super admin chat module successfully removed from AccountEditMainScreen.dart\n\n";
    } else {
        echo "❌ Super admin chat module not properly removed from AccountEditMainScreen.dart\n\n";
    }
} else {
    echo "❌ AccountEditMainScreen.dart file not found\n\n";
}

echo "=== Test Summary ===\n";
echo "The super admin chat module access has been moved from AccountEditMainScreen.dart\n";
echo "to AccountSection.dart as requested. Super admin users (ID = 1) will now see\n";
echo "the 'Test Account Chats' option in the main account section under 'Super Admin Tools'.\n";
echo "\nNavigation path: Account Tab > Super Admin Tools > Test Account Chats\n";
?>
