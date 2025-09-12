#!/usr/bin/env php
<?php
// Create sample test account chat data for demonstration

require_once 'get_fresh_auth_token.php';

echo "=== Creating Sample Test Account Chat Data ===\n\n";

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "lovebirds";
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

try {
    $pdo = new PDO("mysql:unix_socket=$socket;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get test accounts
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM admin_users WHERE is_test_account = 'Yes' LIMIT 2");
    $stmt->execute();
    $testAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($testAccounts) < 2) {
        echo "❌ Need at least 2 test accounts. Current: " . count($testAccounts) . "\n";
        exit(1);
    }
    
    // Get some regular users
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM admin_users WHERE is_test_account != 'Yes' LIMIT 3");
    $stmt->execute();
    $regularUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($regularUsers) < 2) {
        echo "❌ Need at least 2 regular users. Current: " . count($regularUsers) . "\n";
        exit(1);
    }
    
    echo "✅ Found " . count($testAccounts) . " test accounts and " . count($regularUsers) . " regular users\n\n";
    
    // Create chat heads and messages
    $chatHeadsCreated = 0;
    $messagesCreated = 0;
    
    foreach ($testAccounts as $testAccount) {
        foreach ($regularUsers as $index => $regularUser) {
            if ($index > 1) break; // Only use first 2 regular users per test account
            
            // Check if chat head already exists
            $stmt = $pdo->prepare("
                SELECT id FROM chat_heads 
                WHERE (product_owner_id = ? AND customer_id = ?) 
                   OR (product_owner_id = ? AND customer_id = ?)
            ");
            $stmt->execute([
                $testAccount['id'], $regularUser['id'],
                $regularUser['id'], $testAccount['id']
            ]);
            
            if ($stmt->fetch()) {
                echo "ℹ️  Chat already exists between {$testAccount['first_name']} and {$regularUser['first_name']}\n";
                continue;
            }
            
            // Create chat head
            $stmt = $pdo->prepare("
                INSERT INTO chat_heads (product_owner_id, customer_id, type, created_at, updated_at) 
                VALUES (?, ?, 'dating', NOW(), NOW())
            ");
            $stmt->execute([$testAccount['id'], $regularUser['id']]);
            $chatHeadId = $pdo->lastInsertId();
            $chatHeadsCreated++;
            
            echo "✅ Created chat head between {$testAccount['first_name']} and {$regularUser['first_name']}\n";
            
            // Create some sample messages
            $messages = [
                ['sender' => $regularUser, 'body' => "Hey {$testAccount['first_name']}, how are you doing?"],
                ['sender' => $testAccount, 'body' => "Hi {$regularUser['first_name']}! I'm doing great, thanks for asking! How about you?"],
                ['sender' => $regularUser, 'body' => "I'm doing well too! Nice to meet you on this app."],
                ['sender' => $testAccount, 'body' => "Same here! What brings you to the app?"],
                ['sender' => $regularUser, 'body' => "Looking to meet new people and maybe find something special. You?"],
            ];
            
            foreach ($messages as $msgIndex => $message) {
                $stmt = $pdo->prepare("
                    INSERT INTO chat_messages (chat_head_id, sender_id, receiver_id, body, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE), DATE_SUB(NOW(), INTERVAL ? MINUTE))
                ");
                $minutesAgo = 60 - ($msgIndex * 10); // Messages spread over time
                $receiverId = ($message['sender']['id'] == $testAccount['id']) ? $regularUser['id'] : $testAccount['id'];
                $stmt->execute([
                    $chatHeadId, 
                    $message['sender']['id'], 
                    $receiverId,
                    $message['body'],
                    $minutesAgo,
                    $minutesAgo
                ]);
                $messagesCreated++;
            }
            
            // Update chat head with last message info
            $stmt = $pdo->prepare("
                UPDATE chat_heads 
                SET updated_at = (
                    SELECT MAX(created_at) FROM chat_messages WHERE chat_head_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$chatHeadId, $chatHeadId]);
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✅ Chat heads created: $chatHeadsCreated\n";
    echo "✅ Messages created: $messagesCreated\n";
    
    // Verify data
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM chat_heads ch
        JOIN admin_users ta ON (ch.product_owner_id = ta.id OR ch.customer_id = ta.id)
        WHERE ta.is_test_account = 'Yes'
    ");
    $stmt->execute();
    $totalChatHeads = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM chat_messages cm
        JOIN chat_heads ch ON cm.chat_head_id = ch.id
        JOIN admin_users ta ON (ch.product_owner_id = ta.id OR ch.customer_id = ta.id)
        WHERE ta.is_test_account = 'Yes'
    ");
    $stmt->execute();
    $totalMessages = $stmt->fetch()['count'];
    
    echo "\n=== Verification ===\n";
    echo "✅ Total test account chat heads: $totalChatHeads\n";
    echo "✅ Total test account messages: $totalMessages\n";
    
    if ($totalChatHeads > 0) {
        echo "\n🎉 Super admin should now see test account chats in the app!\n";
    } else {
        echo "\n⚠️  No test account chats found. May need to create more data.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
