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

// Create sent_emails table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS sent_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    recipient_email VARCHAR(255),
    subject VARCHAR(255),
    message LONGTEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id)
)");

// Get sent emails by current user
$query = "SELECT * FROM sent_emails 
          WHERE sender_id = $user_id 
          ORDER BY sent_at DESC 
          LIMIT 50";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}

$emails = [];
while ($email = $result->fetch_assoc()) {
    $emails[] = [
        'id' => $email['id'],
        'to' => htmlspecialchars($email['recipient_email']),
        'subject' => htmlspecialchars($email['subject']),
        'message' => htmlspecialchars(substr($email['message'], 0, 50)) . (strlen($email['message']) > 50 ? '...' : ''),
        'full_message' => htmlspecialchars($email['message']),
        'date' => date('Y-m-d H:i', strtotime($email['sent_at'])),
        'sent_at' => $email['sent_at']
    ];
}

header('Content-Type: application/json');
echo json_encode(['emails' => $emails, 'count' => count($emails)]);
?>
