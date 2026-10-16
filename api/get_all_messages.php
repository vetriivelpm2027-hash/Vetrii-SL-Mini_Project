<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get all messages (admin view)
$query = "SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.message, m.is_read, m.created_at, 
                 u1.username as sender_name, u1.role as sender_role, u2.username as receiver_name 
          FROM messages m 
          LEFT JOIN users u1 ON m.sender_id = u1.id 
          LEFT JOIN users u2 ON m.receiver_id = u2.id 
          ORDER BY m.created_at DESC";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}

$messages = [];
while ($msg = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $msg['id'],
        'from' => htmlspecialchars($msg['sender_name'] ?: 'Unknown'),
        'to' => htmlspecialchars($msg['receiver_name'] ?: 'Unknown'),
        'subject' => htmlspecialchars($msg['subject']),
        'message' => htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : ''),
        'full_message' => htmlspecialchars($msg['message']),
        'date' => date('Y-m-d H:i', strtotime($msg['created_at'])),
        'is_read' => $msg['is_read'],
        'sender_role' => $msg['sender_role'],
        'created_at' => $msg['created_at']
    ];
}

header('Content-Type: application/json');
echo json_encode(['messages' => $messages, 'count' => count($messages)]);
?>
