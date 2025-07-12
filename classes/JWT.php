<?php
require_once 'config/database.php';

class JWT {
    
    public static function encode($payload, $key = null, $algo = 'HS256') {
        if (!$key) {
            $key = JWT_SECRET;
        }
        
        $header = json_encode(['typ' => 'JWT', 'alg' => $algo]);
        $payload['exp'] = time() + JWT_EXPIRE;
        $payload['iat'] = time();
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64url_encode($header);
        $payloadEncoded = self::base64url_encode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $key, true);
        $signatureEncoded = self::base64url_encode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    public static function decode($jwt, $key = null) {
        if (!$key) {
            $key = JWT_SECRET;
        }
        
        $parts = explode('.', $jwt);
        if (count($parts) != 3) {
            return false;
        }
        
        $header = json_decode(self::base64url_decode($parts[0]), true);
        $payload = json_decode(self::base64url_decode($parts[1]), true);
        $signature = self::base64url_decode($parts[2]);
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $key, true);
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    public static function getCurrentUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        $payload = self::decode($token);
        
        if (!$payload) {
            return null;
        }
        
        // Get user from database
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT id, email, first_name, last_name, role, avatar, phone FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$payload['user_id']]);
        
        return $stmt->fetch();
    }
    
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

// Helper function to get authorization headers
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
?>