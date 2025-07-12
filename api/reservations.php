<?php
require_once '../config/database.php';
require_once '../classes/JWT.php';
require_once '../classes/SMS.php';

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

// Check authentication
$user = JWT::getCurrentUser();
if (!$user) {
    response(['error' => 'Authentication required'], 401);
}

// Parse path
$pathParts = explode('/', trim($path, '/'));
$reservationId = isset($pathParts[0]) && is_numeric($pathParts[0]) ? (int)$pathParts[0] : null;
$action = isset($pathParts[1]) ? $pathParts[1] : null;

// Routes
switch ($method) {
    case 'GET':
        if ($reservationId) {
            // Get single reservation
            $stmt = $conn->prepare("
                SELECT r.*, u.first_name as user_first_name, u.last_name as user_last_name
                FROM reservations r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reservationId]);
            $reservation = $stmt->fetch();
            
            if (!$reservation) {
                response(['error' => 'Reservation not found'], 404);
            }
            
            // Check permissions
            if ($user['role'] !== 'admin' && $reservation['user_id'] !== $user['id']) {
                response(['error' => 'Access denied'], 403);
            }
            
            response(['reservation' => $reservation]);
            
        } else {
            // Get all reservations with filters
            $where = "WHERE 1=1";
            $params = [];
            
            // Filter by user (non-admin users can only see their own)
            if ($user['role'] !== 'admin') {
                $where .= " AND r.user_id = ?";
                $params[] = $user['id'];
            }
            
            // Apply filters
            if (isset($_GET['status']) && $_GET['status']) {
                $where .= " AND r.status = ?";
                $params[] = $_GET['status'];
            }
            
            if (isset($_GET['date_from']) && $_GET['date_from']) {
                $where .= " AND r.reservation_date >= ?";
                $params[] = $_GET['date_from'];
            }
            
            if (isset($_GET['date_to']) && $_GET['date_to']) {
                $where .= " AND r.reservation_date <= ?";
                $params[] = $_GET['date_to'];
            }
            
            if (isset($_GET['search']) && $_GET['search']) {
                $where .= " AND (r.pet_name LIKE ? OR r.owner_name LIKE ? OR r.owner_phone LIKE ?)";
                $searchTerm = '%' . $_GET['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations r $where");
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            
            // Get reservations
            $stmt = $conn->prepare("
                SELECT r.*, u.first_name as user_first_name, u.last_name as user_last_name
                FROM reservations r
                LEFT JOIN users u ON r.user_id = u.id
                $where
                ORDER BY r.reservation_date DESC, r.reservation_time DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            response([
                'reservations' => $stmt->fetchAll(),
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]);
        }
        
        break;
    
    case 'POST':
        if ($reservationId && $action === 'reminder') {
            // Send reminder SMS
            if ($user['role'] !== 'admin') {
                response(['error' => 'Admin access required'], 403);
            }
            
            $result = SMS::sendReservationReminder($reservationId);
            
            if ($result) {
                response(['message' => 'Reminder sent successfully', 'sms_result' => $result]);
            } else {
                response(['error' => 'Failed to send reminder'], 500);
            }
            
        } else {
            // Create new reservation
            $data = json_decode(file_get_contents('php://input'), true);
            
            $missing = validateRequired($data, [
                'pet_name', 'pet_type', 'owner_name', 'owner_phone', 
                'reservation_date', 'reservation_time', 'service_type'
            ]);
            
            if (!empty($missing)) {
                response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
            }
            
            // Check if date/time is available
            $stmt = $conn->prepare("
                SELECT id FROM reservations 
                WHERE reservation_date = ? AND reservation_time = ? AND status NOT IN ('cancelled', 'completed')
            ");
            $stmt->execute([$data['reservation_date'], $data['reservation_time']]);
            
            if ($stmt->fetch()) {
                response(['error' => 'This time slot is already booked'], 409);
            }
            
            // Insert reservation
            $stmt = $conn->prepare("
                INSERT INTO reservations (
                    user_id, pet_name, pet_type, pet_age, owner_name, owner_phone, 
                    owner_email, reservation_date, reservation_time, service_type, 
                    vaccine_type, notes, price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user['id'],
                $data['pet_name'],
                $data['pet_type'],
                $data['pet_age'] ?? null,
                $data['owner_name'],
                $data['owner_phone'],
                $data['owner_email'] ?? null,
                $data['reservation_date'],
                $data['reservation_time'],
                $data['service_type'],
                $data['vaccine_type'] ?? null,
                $data['notes'] ?? null,
                $data['price'] ?? 0
            ]);
            
            $reservationId = $conn->lastInsertId();
            
            // Send confirmation SMS
            $message = "سلام {$data['owner_name']} عزیز\n";
            $message .= "نوبت شما با موفقیت ثبت شد:\n";
            $message .= "حیوان: {$data['pet_name']}\n";
            $message .= "تاریخ: {$data['reservation_date']}\n";
            $message .= "ساعت: {$data['reservation_time']}\n";
            $message .= "نوع خدمت: {$data['service_type']}\n";
            $message .= "شماره رزرو: {$reservationId}";
            
            SMS::send($data['owner_phone'], $message, $user['id'], $reservationId, 'reservation');
            
            response(['message' => 'Reservation created successfully', 'id' => $reservationId], 201);
        }
        
        break;
    
    case 'PUT':
        if (!$reservationId) {
            response(['error' => 'Reservation ID required'], 400);
        }
        
        // Check if reservation exists
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            response(['error' => 'Reservation not found'], 404);
        }
        
        // Check permissions
        if ($user['role'] !== 'admin' && $reservation['user_id'] !== $user['id']) {
            response(['error' => 'Access denied'], 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Build update query
        $updateFields = [];
        $updateParams = [];
        
        $allowedFields = [
            'pet_name', 'pet_type', 'pet_age', 'owner_name', 'owner_phone', 
            'owner_email', 'reservation_date', 'reservation_time', 'service_type', 
            'vaccine_type', 'notes', 'price', 'status'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateParams[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            response(['error' => 'No fields to update'], 400);
        }
        
        // Check time slot availability if date/time changed
        if (isset($data['reservation_date']) || isset($data['reservation_time'])) {
            $checkDate = $data['reservation_date'] ?? $reservation['reservation_date'];
            $checkTime = $data['reservation_time'] ?? $reservation['reservation_time'];
            
            $stmt = $conn->prepare("
                SELECT id FROM reservations 
                WHERE id != ? AND reservation_date = ? AND reservation_time = ? AND status NOT IN ('cancelled', 'completed')
            ");
            $stmt->execute([$reservationId, $checkDate, $checkTime]);
            
            if ($stmt->fetch()) {
                response(['error' => 'This time slot is already booked'], 409);
            }
        }
        
        // Update reservation
        $updateParams[] = $reservationId;
        $stmt = $conn->prepare("UPDATE reservations SET " . implode(', ', $updateFields) . " WHERE id = ?");
        $stmt->execute($updateParams);
        
        // Send notification SMS if status changed
        if (isset($data['status']) && $data['status'] !== $reservation['status']) {
            $statusMessages = [
                'confirmed' => 'نوبت شما تأیید شد',
                'cancelled' => 'نوبت شما لغو شد',
                'completed' => 'ویزیت شما تکمیل شد'
            ];
            
            if (isset($statusMessages[$data['status']])) {
                $message = "سلام {$reservation['owner_name']} عزیز\n";
                $message .= $statusMessages[$data['status']] . "\n";
                $message .= "شماره رزرو: {$reservationId}\n";
                $message .= "تاریخ: {$reservation['reservation_date']}\n";
                $message .= "ساعت: {$reservation['reservation_time']}";
                
                SMS::send($reservation['owner_phone'], $message, $user['id'], $reservationId, 'notification');
            }
        }
        
        response(['message' => 'Reservation updated successfully']);
        
        break;
    
    case 'DELETE':
        if (!$reservationId) {
            response(['error' => 'Reservation ID required'], 400);
        }
        
        // Check if reservation exists
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            response(['error' => 'Reservation not found'], 404);
        }
        
        // Check permissions
        if ($user['role'] !== 'admin' && $reservation['user_id'] !== $user['id']) {
            response(['error' => 'Access denied'], 403);
        }
        
        // Delete reservation
        $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->execute([$reservationId]);
        
        response(['message' => 'Reservation deleted successfully']);
        
        break;
    
    default:
        response(['error' => 'Method not allowed'], 405);
}

// Special endpoints
if ($path === '/statistics' && $method === 'GET') {
    if ($user['role'] !== 'admin') {
        response(['error' => 'Admin access required'], 403);
    }
    
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    
    // Get statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(price) as total_revenue,
            AVG(price) as avg_price
        FROM reservations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    
    $stmt->execute([$days]);
    $stats = $stmt->fetch();
    
    // Get daily statistics
    $stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count,
            SUM(price) as revenue
        FROM reservations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    
    $stmt->execute([$days]);
    $daily = $stmt->fetchAll();
    
    // Get service type statistics
    $stmt = $conn->prepare("
        SELECT 
            service_type,
            COUNT(*) as count,
            SUM(price) as revenue
        FROM reservations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY service_type
        ORDER BY count DESC
    ");
    
    $stmt->execute([$days]);
    $services = $stmt->fetchAll();
    
    response([
        'total_reservations' => $stats['total_reservations'] ?? 0,
        'pending_count' => $stats['pending_count'] ?? 0,
        'confirmed_count' => $stats['confirmed_count'] ?? 0,
        'completed_count' => $stats['completed_count'] ?? 0,
        'cancelled_count' => $stats['cancelled_count'] ?? 0,
        'total_revenue' => $stats['total_revenue'] ?? 0,
        'avg_price' => $stats['avg_price'] ?? 0,
        'daily_stats' => $daily,
        'service_stats' => $services
    ]);
}
?>