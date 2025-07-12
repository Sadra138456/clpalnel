<?php
header('Content-Type: application/json');

// Simulate API request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/login';

// Test data
$testData = [
    'email' => 'admin@parsian.com',
    'password' => 'admin123'
];

// Capture output
ob_start();

try {
    // Include auth API
    require_once 'api/auth.php';
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'API Error: ' . $e->getMessage()
    ]);
}

$output = ob_get_clean();

// Check if output is valid JSON
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'success',
        'message' => 'API is working correctly',
        'output' => $json
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'API returned invalid JSON',
        'raw_output' => $output,
        'json_error' => json_last_error_msg()
    ]);
}
?>