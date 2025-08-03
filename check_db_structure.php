<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check admin_users table structure
    echo "🔍 CHECKING admin_users TABLE STRUCTURE\n";
    echo "=====================================\n";
    
    $columns = DB::select("DESCRIBE admin_users");
    
    echo "Current columns in admin_users table:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
    // Check if token column exists
    $hasToken = collect($columns)->contains(function($column) {
        return $column->Field === 'token';
    });
    
    echo "\nToken column exists: " . ($hasToken ? "YES" : "NO") . "\n";
    
    if (!$hasToken) {
        echo "\n🔧 ADDING TOKEN COLUMN\n";
        echo "======================\n";
        
        DB::statement("ALTER TABLE admin_users ADD COLUMN token TEXT NULL");
        echo "✅ Token column added successfully!\n";
    }
    
    // Also check remember_token
    $hasRememberToken = collect($columns)->contains(function($column) {
        return $column->Field === 'remember_token';
    });
    
    echo "Remember token column exists: " . ($hasRememberToken ? "YES" : "NO") . "\n";
    
    if (!$hasRememberToken) {
        echo "\n🔧 ADDING REMEMBER_TOKEN COLUMN\n";
        echo "===============================\n";
        
        DB::statement("ALTER TABLE admin_users ADD COLUMN remember_token VARCHAR(100) NULL");
        echo "✅ Remember token column added successfully!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
