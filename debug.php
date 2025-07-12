<?php
// Debug file to check API responses
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug سیستم کلینیک دامپزشکی</h2>";

// Test 1: Database Connection
echo "<h3>1️⃣ تست اتصال دیتابیس:</h3>";
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    if ($conn) {
        echo "✅ اتصال دیتابیس موفق<br>";
        
        // Test query
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "📊 تعداد کاربران: " . $result['count'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ خطا در اتصال دیتابیس: " . $e->getMessage() . "<br>";
}

// Test 2: PHP Version and Extensions
echo "<h3>2️⃣ تست PHP:</h3>";
echo "🐘 PHP Version: " . phpversion() . "<br>";
echo "📦 PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "<br>";
echo "🔧 PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "<br>";
echo "🌐 cURL: " . (extension_loaded('curl') ? '✅' : '❌') . "<br>";
echo "🔒 OpenSSL: " . (extension_loaded('openssl') ? '✅' : '❌') . "<br>";

// Test 3: File Permissions
echo "<h3>3️⃣ تست فایل‌ها:</h3>";
$files = [
    'config/database.php',
    'api/auth.php',
    'classes/JWT.php',
    '.htaccess'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file موجود است<br>";
    } else {
        echo "❌ $file موجود نیست<br>";
    }
}

// Test 4: Direct API Test
echo "<h3>4️⃣ تست مستقیم API:</h3>";
echo "<a href='api/auth.php/login' target='_blank'>📡 تست API Login</a><br>";
echo "<a href='test-api.php' target='_blank'>🔧 تست کامل API</a><br>";

// Test 5: Headers Test
echo "<h3>5️⃣ تست Headers:</h3>";
echo "📋 Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "🌍 HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "📂 Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "📄 Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Test 6: URL Rewriting
echo "<h3>6️⃣ تست URL Rewriting:</h3>";
if (isset($_SERVER['HTTP_MOD_REWRITE'])) {
    echo "✅ mod_rewrite فعال است<br>";
} else {
    echo "⚠️ mod_rewrite status نامشخص<br>";
}

echo "<hr>";
echo "<p><strong>🔍 برای رفع مشکل:</strong></p>";
echo "<ol>";
echo "<li>اگر دیتابیس خطا داد، اطلاعات config/database.php را بررسی کنید</li>";
echo "<li>اگر فایلی موجود نیست، آن را آپلود کنید</li>";
echo "<li>لینک 'تست API Login' را کلیک کنید تا پاسخ API را ببینید</li>";
echo "</ol>";
?>