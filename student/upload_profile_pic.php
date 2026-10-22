<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_FILES['profile_pic'])) {
    echo json_encode(['success' => false, 'message' => 'No file provided']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student ID
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$student_id = $student['id'];
$file = $_FILES['profile_pic'];

// Validate file
$target_dir = "../uploads/profile_pic/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($file_ext, $allowed_ext)) {
    echo json_encode(['success' => false, 'message' => 'Invalid image format']);
    exit;
}

if ($file['size'] > (5 * 1024 * 1024)) {
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit;
}

// Get old file to delete
$old_file_stmt = $conn->prepare("SELECT profile_pic FROM students WHERE id = ?");
$old_file_stmt->bind_param("i", $student_id);
$old_file_stmt->execute();
$old_file_result = $old_file_stmt->get_result();
$old_data = $old_file_result->fetch_assoc();
$old_file_stmt->close();

// Delete old file if exists
if (!empty($old_data['profile_pic']) && file_exists($target_dir . $old_data['profile_pic'])) {
    unlink($target_dir . $old_data['profile_pic']);
}

// Save new file
$file_name = $student_id . "_" . time() . "." . $file_ext;
$target_file = $target_dir . $file_name;

if (!move_uploaded_file($file['tmp_name'], $target_file)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Update database
$update_stmt = $conn->prepare("UPDATE students SET profile_pic = ? WHERE id = ?");
$update_stmt->bind_param("si", $file_name, $student_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Profile picture updated', 'filename' => $file_name]);
} else {
    // Delete the file if database update fails
    unlink($target_file);
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$update_stmt->close();
?>
