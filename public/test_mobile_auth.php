<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

// Test mobile app authentication connectivity
echo json_encode([
    'test_endpoint' => 'Mobile App Authentication Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'Backend Ready',
    'test_credentials' => [
        'email' => 'admin@gmail.com',
        'password' => '123456'
    ],
    'instructions' => [
        '1. Use email: admin@gmail.com, password: 123456',
        '2. Send POST to: /api/auth/login',
        '3. Get JWT token from response',
        '4. Use token in Authorization header: Bearer {token}',
        '5. Test dating endpoints: /api/swipe-discovery'
    ],
    'test_user_count' => User::count(),
    'ready_for_mobile_testing' => true
]);
?>
