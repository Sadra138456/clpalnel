<?php
header('Content-Type: application/json');

// Test login functionality
try {
    // Test database connection first
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Test user credentials
    $email = 'admin@parsian.com';
    $password = 'admin123';
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found',
            'suggestion' => 'Create user with email: admin@parsian.com and password: admin123'
        ]);
        exit;
    }
    
    // Test password verification
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid password',
            'user_exists' => true,
            'password_hash' => $user['password']
        ]);
        exit;
    }
    
    // Test JWT generation
    require_once 'classes/JWT.php';
    $token = JWT::encode(['user_id' => $user['id'], 'role' => $user['role']]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Login test successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role']
        ],
        'token' => $token,
        'database_status' => 'connected'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>