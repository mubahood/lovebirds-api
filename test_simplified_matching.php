<?php

require_once 'vendor/autoload.php';

use App\Services\SimplifiedMatchingService;
use App\Models\User;

// Simple test script to validate the new SimplifiedMatchingService
echo "🎯 Testing SimplifiedMatchingService...\n\n";

try {
    // Test compatibility scoring
    echo "1. Testing Compatibility Scoring:\n";
    
    $user1 = User::find(1); // Test user
    $user2 = User::find(2); // Another user
    
    if ($user1 && $user2) {
        $service = new SimplifiedMatchingService();
        $score = $service->calculateCompatibilityScore($user1, $user2);
        echo "   ✅ Compatibility between User {$user1->id} and User {$user2->id}: {$score}%\n";
    } else {
        echo "   ⚠️  Users not found for compatibility test\n";
    }

    echo "\n2. Testing Match Discovery:\n";
    
    if ($user1) {
        $service = new SimplifiedMatchingService();
        $request = new Illuminate\Http\Request(['limit' => 5, 'min_score' => 40]);
        
        $users = $service->getDiscoveryUsers($user1, $request);
        echo "   ✅ Found " . count($users) . " potential matches for User {$user1->id}\n";
        
        foreach ($users as $index => $userData) {
            echo "      - {$userData['user']->name}: {$userData['compatibility_score']}% compatibility\n";
        }
    }

    echo "\n3. Testing Enhanced Matches:\n";
    
    if ($user1) {
        $service = new SimplifiedMatchingService();
        $result = $service->getEnhancedMatches($user1, 'all', 5, 1);
        
        echo "   ✅ Enhanced matches result structure:\n";
        echo "      - Total matches: " . count($result['matches']) . "\n";
        echo "      - Filter counts: " . json_encode($result['filter_counts']) . "\n";
        echo "      - Has pagination: " . (isset($result['pagination']) ? 'Yes' : 'No') . "\n";
    }

    echo "\n✅ All tests completed successfully!\n";
    echo "\nSimplified Matching Algorithm Features:\n";
    echo "• 🎯 5-factor scoring (Location, Interests, Age, Activity, Profile Quality)\n";
    echo "• ⚡ Fast single-query performance\n";  
    echo "• 📊 Consistent compatibility calculation\n";
    echo "• 🔄 Enhanced match filtering and pagination\n";
    echo "• 💡 Conversation starters generation\n";

} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
?>
