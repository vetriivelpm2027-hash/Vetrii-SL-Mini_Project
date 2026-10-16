<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin();

$message_id = intval($_GET['id']);

// Get message details to check if sender is admin
$msg_query = $conn->query("SELECT m.sender_id, u.role FROM messages m 
                           LEFT JOIN users u ON m.sender_id = u.id 
                           WHERE m.id = $message_id");

if ($msg_query && $msg_query->num_rows > 0) {
    $message = $msg_query->fetch_assoc();
    
    // Prevent deletion of messages from admin users
    if ($message['role'] === 'admin') {
        header("Location: messages.php?error=Cannot delete messages from admin users");
        exit();
    }
    
    // Delete the message
    $conn->query("DELETE FROM messages WHERE id = $message_id");
    header("Location: messages.php?success=Message deleted successfully");
} else {
    header("Location: messages.php?error=Message not found");
}
exit();
?>
