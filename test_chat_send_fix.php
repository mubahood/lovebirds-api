<?php
/**
 * Test script to verify the chat-send fix
 * This simulates the mobile app sending a message without providing chat_head_id
 */

// Include the Laravel bootstrap
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use App\Models\ChatHead;
use App\Models\ChatMessage;

echo "=== Testing Chat Send Fix ===\n\n";

// Test scenario: User 5964 sends message to User 1000 (from the error log)
$senderId = 5964;
$receiverId = 1000;

echo "Test Scenario:\n";
echo "- Sender ID: $senderId\n";
echo "- Receiver ID: $receiverId\n";
echo "- Message: 'test message'\n";
echo "- Type: text\n\n";

// Check if users exist
$sender = User::find($senderId);
$receiver = User::find($receiverId);

if (!$sender) {
    echo "❌ ERROR: Sender (ID: $senderId) not found\n";
    exit;
}

if (!$receiver) {
    echo "❌ ERROR: Receiver (ID: $receiverId) not found\n";
    exit;
}

echo "✅ Users found:\n";
echo "   Sender: {$sender->name} (ID: {$sender->id})\n";
echo "   Receiver: {$receiver->name} (ID: {$receiver->id})\n\n";

// Check if ChatHead exists between these users
$existingChatHead = ChatHead::where(function($query) use ($senderId, $receiverId) {
    $query->where('customer_id', $senderId)
          ->where('product_owner_id', $receiverId);
})->orWhere(function($query) use ($senderId, $receiverId) {
    $query->where('customer_id', $receiverId)
          ->where('product_owner_id', $senderId);
})->where('type', 'dating')->first();

if ($existingChatHead) {
    echo "✅ Existing ChatHead found (ID: {$existingChatHead->id})\n";
} else {
    echo "ℹ️  No existing ChatHead found - will be created automatically\n";
}

echo "\n--- Testing API Endpoint ---\n";

// Simulate the API request
$requestData = [
    'receiver_id' => $receiverId,
    'content' => 'test message',
    'message_type' => 'text',
];

echo "Request data: " . json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

// Create the request object (simulating the mobile app request)
$request = new Illuminate\Http\Request();
$request->merge($requestData);

// Simulate authentication by setting the logged_in_user_id header
$request->headers->set('logged_in_user_id', $senderId);

// Create the controller and call the method
$controller = new App\Http\Controllers\ApiController();

try {
    $response = $controller->chat_send($request);
    $responseData = $response->getData(true);
    
    echo "✅ SUCCESS: API call completed\n";
    echo "Response code: " . $responseData['code'] . "\n";
    echo "Response message: " . $responseData['message'] . "\n";
    
    if ($responseData['code'] == 1) {
        echo "\n✅ Message sent successfully!\n";
        
        // Check the created/updated ChatHead
        $chatHead = ChatHead::where(function($query) use ($senderId, $receiverId) {
            $query->where('customer_id', $senderId)
                  ->where('product_owner_id', $receiverId);
        })->orWhere(function($query) use ($senderId, $receiverId) {
            $query->where('customer_id', $receiverId)
                  ->where('product_owner_id', $senderId);
        })->where('type', 'dating')->first();
        
        if ($chatHead) {
            echo "✅ ChatHead verified (ID: {$chatHead->id})\n";
            echo "   Last message: {$chatHead->last_message_body}\n";
            echo "   Last message time: {$chatHead->last_message_time}\n";
            
            // Check the message
            $message = ChatMessage::where('chat_head_id', $chatHead->id)
                                 ->where('sender_id', $senderId)
                                 ->where('receiver_id', $receiverId)
                                 ->latest()
                                 ->first();
            
            if ($message) {
                echo "✅ Message verified (ID: {$message->id})\n";
                echo "   Content: {$message->body}\n";
                echo "   Type: {$message->type}\n";
                echo "   Status: {$message->status}\n";
            } else {
                echo "❌ ERROR: Message not found in database\n";
            }
        } else {
            echo "❌ ERROR: ChatHead not found after message send\n";
        }
    } else {
        echo "\n❌ FAILED: " . $responseData['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: Exception occurred\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
