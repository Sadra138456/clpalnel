<?php
header('Content-Type: application/json');

// Test database connection
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    if ($conn) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Database connection successful',
            'database_config' => [
                'host' => 'localhost',
                'db_name' => 'your_database_name',
                'username' => 'your_username'
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed',
            'error' => 'Connection returned null'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection error',
        'error' => $e->getMessage()
    ]);
}
?>