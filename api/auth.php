<?php
require_once '../config/database.php';
require_once '../classes/JWT.php';

header('Content-Type: application/json');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

// Database connection
$db = new Database();
$conn = $db->connect();

// Response function
function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Validate required fields
function validateRequired($data, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return $missing;
}

// Routes
switch ($path) {
    case '/login':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $missing = validateRequired($data, ['email', 'password']);
        if (!empty($missing)) {
            response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            response(['error' => 'Invalid credentials'], 401);
        }
        
        $token = JWT::encode(['user_id' => $user['id'], 'role' => $user['role']]);
        
        response([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'avatar' => $user['avatar'],
                'phone' => $user['phone']
            ]
        ]);
        
        break;
    
    case '/register':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $missing = validateRequired($data, ['email', 'password', 'first_name', 'last_name']);
        if (!empty($missing)) {
            response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            response(['error' => 'User already exists'], 409);
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, first_name, last_name, phone, role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['email'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            'user'
        ]);
        
        $userId = $conn->lastInsertId();
        
        // Generate token
        $token = JWT::encode(['user_id' => $userId, 'role' => 'user']);
        
        response([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role' => 'user',
                'avatar' => null,
                'phone' => $data['phone'] ?? null
            ]
        ], 201);
        
        break;
    
    case '/google-login':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $missing = validateRequired($data, ['google_id', 'email', 'first_name', 'last_name']);
        if (!empty($missing)) {
            response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        // Check if user exists by Google ID
        $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? AND is_active = 1");
        $stmt->execute([$data['google_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Check if user exists by email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Link Google account to existing user
                $stmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $stmt->execute([$data['google_id'], $user['id']]);
            } else {
                // Create new user
                $stmt = $conn->prepare("
                    INSERT INTO users (email, password, first_name, last_name, google_id, role) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $data['email'],
                    password_hash(uniqid(), PASSWORD_DEFAULT), // Random password
                    $data['first_name'],
                    $data['last_name'],
                    $data['google_id'],
                    'user'
                ]);
                
                $userId = $conn->lastInsertId();
                
                // Get new user
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            }
        }
        
        $token = JWT::encode(['user_id' => $user['id'], 'role' => $user['role']]);
        
        response([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'avatar' => $user['avatar'],
                'phone' => $user['phone']
            ]
        ]);
        
        break;
    
    case '/verify-token':
        if ($method !== 'GET') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $user = JWT::getCurrentUser();
        
        if (!$user) {
            response(['error' => 'Invalid token'], 401);
        }
        
        response(['user' => $user]);
        
        break;
    
    case '/refresh-token':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $user = JWT::getCurrentUser();
        
        if (!$user) {
            response(['error' => 'Invalid token'], 401);
        }
        
        $token = JWT::encode(['user_id' => $user['id'], 'role' => $user['role']]);
        
        response(['token' => $token]);
        
        break;
    
    default:
        response(['error' => 'Endpoint not found'], 404);
}
?>