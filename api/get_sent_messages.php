<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get sent messages
$query = "SELECT m.id, m.receiver_id, m.subject, m.message, m.created_at, u.username 
          FROM messages m 
          LEFT JOIN users u ON m.receiver_id = u.id 
          WHERE m.sender_id = $user_id 
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
        'to' => htmlspecialchars($msg['username'] ?: 'Unknown'),
        'subject' => htmlspecialchars($msg['subject']),
        'message' => htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : ''),
        'full_message' => htmlspecialchars($msg['message']),
        'date' => date('Y-m-d H:i', strtotime($msg['created_at'])),
        'created_at' => $msg['created_at']
    ];
}

header('Content-Type: application/json');
echo json_encode(['messages' => $messages, 'count' => count($messages)]);
?>
