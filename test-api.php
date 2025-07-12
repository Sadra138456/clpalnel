<?php
// Direct API Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 تست مستقیم API</h2>";

// Test Auth API directly
echo "<h3>🔐 تست Auth API:</h3>";

// Simulate login request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/login';

// Capture output
ob_start();

try {
    include 'api/auth.php';
} catch (Exception $e) {
    echo "❌ خطا در API: " . $e->getMessage();
}

$output = ob_get_clean();

echo "<h4>📤 خروجی API:</h4>";
echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars($output);
echo "</pre>";

// Check if output is valid JSON
echo "<h4>🔍 بررسی JSON:</h4>";
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ خروجی JSON معتبر است<br>";
    echo "<strong>محتوا:</strong> " . print_r($json, true);
} else {
    echo "❌ خروجی JSON نامعتبر است<br>";
    echo "<strong>خطا:</strong> " . json_last_error_msg() . "<br>";
    echo "<strong>احتمالاً مشکل:</strong><br>";
    echo "- خطای PHP در کد<br>";
    echo "- مشکل در اتصال دیتابیس<br>";
    echo "- فایل config ناقص<br>";
}

// Test manual JSON response
echo "<h3>📝 تست JSON دستی:</h3>";
header('Content-Type: application/json');
echo json_encode(['test' => 'success', 'message' => 'API در حال کار است']);
?>