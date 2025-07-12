<?php
// Database Configuration for Shared Hosting
// کپی کنید به database.php و اطلاعات خود را وارد کنید

class Database {
    private $host = 'localhost';                    // معمولاً localhost
    private $db_name = 'your_database_name';        // نام دیتابیس خود را وارد کنید
    private $username = 'your_username';            // نام کاربری MySQL
    private $password = 'your_password';            // رمز عبور MySQL
    private $charset = 'utf8mb4';
    private $conn;

    public function connect() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo 'خطا در اتصال دیتابیس: ' . $e->getMessage();
            die();
        }

        return $this->conn;
    }
}

// JWT Configuration
define('JWT_SECRET', 'your-super-secret-key-change-this-in-production-' . time());
define('JWT_EXPIRE', 3600 * 24 * 7); // 7 days

// SMS Configuration (Kavenegar)
define('SMS_API_KEY', 'your-kavenegar-api-key');    // API Key کاوه‌نگار
define('SMS_SENDER', '10004346');                    // شماره ارسال

// Site Configuration  
define('SITE_URL', 'https://yourdomain.com');       // آدرس سایت خود
define('UPLOAD_PATH', 'uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
?>