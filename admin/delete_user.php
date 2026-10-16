<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin();

$user_id = intval($_GET['id']);

// Check if there's at least 1 admin remaining (prevent deleting last admin)
$users_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_count = $users_query->fetch_assoc()['count'];

// Get user role to check if deleting admin
$user_query = $conn->query("SELECT role FROM users WHERE id = $user_id");
$user_role = $user_query->fetch_assoc()['role'] ?? '';

// Prevent deletion if this is the only admin
if ($user_role === 'admin' && $admin_count <= 1) {
    header("Location: users.php?error=Cannot delete the last admin user");
    exit();
}

// Delete student record if exists and all related data
$student_query = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
if ($student_query && $student_query->num_rows > 0) {
    $student = $student_query->fetch_assoc();
    $student_id = $student['id'];
    
    // Delete student sports (has CASCADE, but explicit for safety)
    $conn->query("DELETE FROM student_sports WHERE student_id = $student_id");
    
    // Delete student activities (has CASCADE, but explicit for safety)
    $conn->query("DELETE FROM student_activities WHERE student_id = $student_id");
    
    // Delete student record
    $conn->query("DELETE FROM students WHERE id = $student_id");
}

// Delete messages where user is sender or receiver
$conn->query("DELETE FROM messages WHERE sender_id = $user_id OR receiver_id = $user_id");

// Delete sent emails
$conn->query("DELETE FROM sent_emails WHERE sender_id = $user_id");

// Delete attendance records if teacher
$conn->query("DELETE FROM attendance WHERE teacher_id = $user_id");

// Finally delete the user
$conn->query("DELETE FROM users WHERE id = $user_id");

header("Location: users.php?success=User deleted successfully");
exit();
?>
