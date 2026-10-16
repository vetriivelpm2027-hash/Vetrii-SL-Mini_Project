<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin();

$user_id = intval($_GET['id']);

// Delete student
$conn->query("DELETE FROM students WHERE id = $user_id");

header("Location: students.php");
exit();
?>
