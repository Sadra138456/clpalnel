<?php
require_once 'config/database.php';

class SMS {
    
    public static function send($phone, $message, $user_id = null, $reservation_id = null, $message_type = 'custom') {
        $api_key = SMS_API_KEY;
        $sender = SMS_SENDER;
        
        // Clean phone number
        $phone = self::cleanPhone($phone);
        
        // Kavenegar API URL
        $url = "https://api.kavenegar.com/v1/{$api_key}/sms/send.json";
        
        $data = [
            'receptor' => $phone,
            'message' => $message,
            'sender' => $sender
        ];
        
        // Send SMS via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Parse response
        $result = json_decode($response, true);
        
        $status = 'failed';
        $message_id = null;
        $cost = 0;
        
        if ($httpCode == 200 && isset($result['return']['status']) && $result['return']['status'] == 200) {
            $status = 'sent';
            $message_id = $result['entries'][0]['messageid'] ?? null;
            $cost = $result['entries'][0]['cost'] ?? 0;
        }
        
        // Save to SMS archive
        self::saveToArchive($user_id, $phone, $message, $message_id, $status, $cost, $reservation_id, $message_type);
        
        return [
            'success' => $status == 'sent',
            'message_id' => $message_id,
            'cost' => $cost,
            'status' => $status,
            'response' => $result
        ];
    }
    
    public static function sendBulk($phones, $message, $user_id = null, $message_type = 'custom') {
        $results = [];
        
        foreach ($phones as $phone) {
            $result = self::send($phone, $message, $user_id, null, $message_type);
            $results[] = [
                'phone' => $phone,
                'success' => $result['success'],
                'message_id' => $result['message_id']
            ];
        }
        
        return $results;
    }
    
    private static function saveToArchive($user_id, $phone, $message, $message_id, $status, $cost, $reservation_id, $message_type) {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            INSERT INTO sms_archive (user_id, recipient_phone, message, message_id, status, cost, reservation_id, message_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$user_id, $phone, $message, $message_id, $status, $cost, $reservation_id, $message_type]);
    }
    
    public static function getArchive($user_id = null, $page = 1, $limit = 20, $filters = []) {
        $db = new Database();
        $conn = $db->connect();
        
        $where = "WHERE 1=1";
        $params = [];
        
        if ($user_id) {
            $where .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        if (isset($filters['phone']) && $filters['phone']) {
            $where .= " AND recipient_phone LIKE ?";
            $params[] = '%' . $filters['phone'] . '%';
        }
        
        if (isset($filters['status']) && $filters['status']) {
            $where .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['message_type']) && $filters['message_type']) {
            $where .= " AND message_type = ?";
            $params[] = $filters['message_type'];
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $where .= " AND DATE(sent_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $where .= " AND DATE(sent_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM sms_archive $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get paginated results
        $offset = ($page - 1) * $limit;
        $stmt = $conn->prepare("
            SELECT s.*, u.first_name, u.last_name, r.pet_name, r.owner_name
            FROM sms_archive s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN reservations r ON s.reservation_id = r.id
            $where
            ORDER BY sent_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public static function getStatistics($user_id = null, $days = 30) {
        $db = new Database();
        $conn = $db->connect();
        
        $where = "WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$days];
        
        if ($user_id) {
            $where .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_messages,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(cost) as total_cost,
                AVG(cost) as avg_cost
            FROM sms_archive 
            $where
        ");
        
        $stmt->execute($params);
        $stats = $stmt->fetch();
        
        // Get daily statistics
        $dailyStmt = $conn->prepare("
            SELECT 
                DATE(sent_at) as date,
                COUNT(*) as count,
                SUM(cost) as cost
            FROM sms_archive 
            $where
            GROUP BY DATE(sent_at)
            ORDER BY date DESC
        ");
        
        $dailyStmt->execute($params);
        $daily = $dailyStmt->fetchAll();
        
        return [
            'total_messages' => $stats['total_messages'] ?? 0,
            'sent_count' => $stats['sent_count'] ?? 0,
            'failed_count' => $stats['failed_count'] ?? 0,
            'total_cost' => $stats['total_cost'] ?? 0,
            'avg_cost' => $stats['avg_cost'] ?? 0,
            'success_rate' => $stats['total_messages'] > 0 ? round(($stats['sent_count'] / $stats['total_messages']) * 100, 2) : 0,
            'daily_stats' => $daily
        ];
    }
    
    public static function sendReservationReminder($reservation_id) {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            SELECT r.*, u.id as user_id, u.first_name, u.last_name
            FROM reservations r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            return false;
        }
        
        $message = "سلام {$reservation['owner_name']} عزیز\n";
        $message .= "یادآوری نوبت دامپزشکی شما:\n";
        $message .= "حیوان: {$reservation['pet_name']}\n";
        $message .= "تاریخ: {$reservation['reservation_date']}\n";
        $message .= "ساعت: {$reservation['reservation_time']}\n";
        $message .= "نوع خدمت: {$reservation['service_type']}\n";
        $message .= "لطفاً در زمان مقرر حضور داشته باشید.";
        
        $result = self::send($reservation['owner_phone'], $message, $reservation['user_id'], $reservation_id, 'reminder');
        
        if ($result['success']) {
            // Mark reminder as sent
            $updateStmt = $conn->prepare("UPDATE reservations SET reminder_sent = 1 WHERE id = ?");
            $updateStmt->execute([$reservation_id]);
        }
        
        return $result;
    }
    
    private static function cleanPhone($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to international format
        if (strlen($phone) == 11 && substr($phone, 0, 2) == '09') {
            $phone = '98' . substr($phone, 1);
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '9') {
            $phone = '98' . $phone;
        }
        
        return $phone;
    }
}
?>