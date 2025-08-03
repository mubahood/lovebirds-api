<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "🔧 FIXING REMEMBER_TOKEN COLUMN SIZE\n";
    echo "===================================\n";
    
    // Increase remember_token column size to handle JWTs
    DB::statement("ALTER TABLE admin_users MODIFY COLUMN remember_token TEXT NULL");
    echo "✅ Remember token column updated to TEXT!\n";
    
    echo "\n🔧 CHECKING TOKEN COLUMN SIZE\n";
    echo "============================\n";
    
    // Check current token column type
    $tokenColumn = DB::select("SHOW COLUMNS FROM admin_users WHERE Field = 'token'");
    if (!empty($tokenColumn)) {
        echo "Token column type: " . $tokenColumn[0]->Type . "\n";
        
        if ($tokenColumn[0]->Type !== 'text') {
            echo "Updating token column to TEXT...\n";
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN token TEXT NULL");
            echo "✅ Token column updated to TEXT!\n";
        } else {
            echo "✅ Token column is already TEXT!\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
