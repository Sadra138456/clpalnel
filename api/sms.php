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

// Routes
switch ($path) {
    case '/send':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $missing = validateRequired($data, ['phone', 'message']);
        if (!empty($missing)) {
            response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        $result = SMS::send(
            $data['phone'], 
            $data['message'], 
            $user['id'], 
            $data['reservation_id'] ?? null,
            $data['message_type'] ?? 'custom'
        );
        
        response([
            'message' => 'SMS sent successfully',
            'result' => $result
        ]);
        
        break;
    
    case '/send-bulk':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $missing = validateRequired($data, ['phones', 'message']);
        if (!empty($missing)) {
            response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        if (!is_array($data['phones'])) {
            response(['error' => 'Phones must be an array'], 400);
        }
        
        $results = SMS::sendBulk(
            $data['phones'], 
            $data['message'], 
            $user['id'],
            $data['message_type'] ?? 'custom'
        );
        
        response([
            'message' => 'Bulk SMS sent',
            'results' => $results
        ]);
        
        break;
    
    case '/archive':
        if ($method !== 'GET') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        
        $filters = [];
        if (isset($_GET['phone']) && $_GET['phone']) {
            $filters['phone'] = $_GET['phone'];
        }
        if (isset($_GET['status']) && $_GET['status']) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['message_type']) && $_GET['message_type']) {
            $filters['message_type'] = $_GET['message_type'];
        }
        if (isset($_GET['date_from']) && $_GET['date_from']) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (isset($_GET['date_to']) && $_GET['date_to']) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        $userId = $user['role'] === 'admin' ? null : $user['id'];
        
        $result = SMS::getArchive($userId, $page, $limit, $filters);
        
        response($result);
        
        break;
    
    case '/statistics':
        if ($method !== 'GET') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $userId = $user['role'] === 'admin' ? null : $user['id'];
        
        $result = SMS::getStatistics($userId, $days);
        
        response($result);
        
        break;
    
    case '/templates':
        if ($method === 'GET') {
            // Get SMS templates
            $templates = [
                'reservation_confirmation' => [
                    'name' => 'تأیید نوبت',
                    'template' => 'سلام {owner_name} عزیز\nنوبت شما با موفقیت ثبت شد:\nحیوان: {pet_name}\nتاریخ: {date}\nساعت: {time}\nنوع خدمت: {service_type}\nشماره رزرو: {reservation_id}',
                    'variables' => ['owner_name', 'pet_name', 'date', 'time', 'service_type', 'reservation_id']
                ],
                'reservation_reminder' => [
                    'name' => 'یادآوری نوبت',
                    'template' => 'سلام {owner_name} عزیز\nیادآوری نوبت دامپزشکی شما:\nحیوان: {pet_name}\nتاریخ: {date}\nساعت: {time}\nنوع خدمت: {service_type}\nلطفاً در زمان مقرر حضور داشته باشید.',
                    'variables' => ['owner_name', 'pet_name', 'date', 'time', 'service_type']
                ],
                'status_confirmed' => [
                    'name' => 'تأیید نوبت',
                    'template' => 'سلام {owner_name} عزیز\nنوبت شما تأیید شد\nشماره رزرو: {reservation_id}\nتاریخ: {date}\nساعت: {time}',
                    'variables' => ['owner_name', 'reservation_id', 'date', 'time']
                ],
                'status_cancelled' => [
                    'name' => 'لغو نوبت',
                    'template' => 'سلام {owner_name} عزیز\nنوبت شما لغو شد\nشماره رزرو: {reservation_id}\nتاریخ: {date}\nساعت: {time}',
                    'variables' => ['owner_name', 'reservation_id', 'date', 'time']
                ],
                'status_completed' => [
                    'name' => 'تکمیل ویزیت',
                    'template' => 'سلام {owner_name} عزیز\nویزیت شما تکمیل شد\nشماره رزرو: {reservation_id}\nتاریخ: {date}\nساعت: {time}',
                    'variables' => ['owner_name', 'reservation_id', 'date', 'time']
                ],
                'custom_message' => [
                    'name' => 'پیام دلخواه',
                    'template' => 'سلام {owner_name} عزیز\n{message}',
                    'variables' => ['owner_name', 'message']
                ]
            ];
            
            response(['templates' => $templates]);
            
        } else {
            response(['error' => 'Method not allowed'], 405);
        }
        
        break;
    
    case '/send-template':
        if ($method !== 'POST') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $missing = validateRequired($data, ['phone', 'template', 'variables']);
        if (!empty($missing)) {
            response(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        // Get template
        $templates = [
            'reservation_confirmation' => 'سلام {owner_name} عزیز\nنوبت شما با موفقیت ثبت شد:\nحیوان: {pet_name}\nتاریخ: {date}\nساعت: {time}\nنوع خدمت: {service_type}\nشماره رزرو: {reservation_id}',
            'reservation_reminder' => 'سلام {owner_name} عزیز\nیادآوری نوبت دامپزشکی شما:\nحیوان: {pet_name}\nتاریخ: {date}\nساعت: {time}\nنوع خدمت: {service_type}\nلطفاً در زمان مقرر حضور داشته باشید.',
            'status_confirmed' => 'سلام {owner_name} عزیز\nنوبت شما تأیید شد\nشماره رزرو: {reservation_id}\nتاریخ: {date}\nساعت: {time}',
            'status_cancelled' => 'سلام {owner_name} عزیز\nنوبت شما لغو شد\nشماره رزرو: {reservation_id}\nتاریخ: {date}\nساعت: {time}',
            'status_completed' => 'سلام {owner_name} عزیز\nویزیت شما تکمیل شد\nشماره رزرو: {reservation_id}\nتاریخ: {date}\nساعت: {time}',
            'custom_message' => 'سلام {owner_name} عزیز\n{message}'
        ];
        
        if (!isset($templates[$data['template']])) {
            response(['error' => 'Template not found'], 404);
        }
        
        $message = $templates[$data['template']];
        
        // Replace variables
        foreach ($data['variables'] as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        $result = SMS::send(
            $data['phone'], 
            $message, 
            $user['id'], 
            $data['reservation_id'] ?? null,
            $data['message_type'] ?? 'custom'
        );
        
        response([
            'message' => 'Template SMS sent successfully',
            'result' => $result
        ]);
        
        break;
    
    case '/customers':
        if ($method !== 'GET') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        // Get unique customers from reservations
        $stmt = $conn->prepare("
            SELECT DISTINCT owner_name, owner_phone, owner_email, COUNT(*) as reservation_count
            FROM reservations r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE (? = 'admin' OR r.user_id = ?)
            GROUP BY owner_phone, owner_name, owner_email
            ORDER BY reservation_count DESC
        ");
        
        $stmt->execute([$user['role'], $user['id']]);
        $customers = $stmt->fetchAll();
        
        response(['customers' => $customers]);
        
        break;
    
    case '/balance':
        if ($method !== 'GET') {
            response(['error' => 'Method not allowed'], 405);
        }
        
        // This would normally check Kavenegar balance
        // For now, return mock data
        response([
            'balance' => 50000,
            'currency' => 'تومان',
            'last_updated' => date('Y-m-d H:i:s')
        ]);
        
        break;
    
    default:
        response(['error' => 'Endpoint not found'], 404);
}
?>