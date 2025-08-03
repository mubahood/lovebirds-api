<?php
// Simple test to check if dating fields exist in admin_users table
$host = '127.0.0.1';
$dbname = 'katogo';
$username = 'root';
$password = 'root';
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;unix_socket=$socket", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing admin_users table structure...\n";
    
    // Check if key dating fields exist
    $fields_to_check = [
        'bio', 'height_cm', 'body_type', 'interests', 'lifestyle', 
        'wants_kids', 'has_kids', 'relationship_type', 'education_level',
        'occupation', 'smoking_habit', 'drinking_habit', 'exercise_frequency',
        'looking_for', 'interested_in', 'age_range_min', 'age_range_max',
        'max_distance_km', 'profile_photos'
    ];
    
    $stmt = $pdo->query("DESCRIBE admin_users");
    $existing_fields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_fields[] = $row['Field'];
    }
    
    $missing_fields = [];
    $found_fields = [];
    
    foreach ($fields_to_check as $field) {
        if (in_array($field, $existing_fields)) {
            $found_fields[] = $field;
            echo "✅ Field '$field' exists\n";
        } else {
            $missing_fields[] = $field;
            echo "❌ Field '$field' missing\n";
        }
    }
    
    echo "\nSummary:\n";
    echo "Found " . count($found_fields) . " dating fields\n";
    echo "Missing " . count($missing_fields) . " dating fields\n";
    
    if (count($missing_fields) > 0) {
        echo "\nMissing fields: " . implode(', ', $missing_fields) . "\n";
    }
    
    // Test inserting sample data
    if (count($missing_fields) == 0) {
        echo "\n✅ All dating fields exist! Backend is ready for ProfileSetupWizardScreen.\n";
    } else {
        echo "\n❌ Missing fields need to be added to admin_users table.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please update database connection details in the test script.\n";
}
?>
