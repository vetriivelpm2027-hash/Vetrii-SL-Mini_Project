<?php
/**
 * Create Profile Edit Permission System
 * Run this once to add the necessary table to the database
 */
require_once 'config/database.php';

// Create profile_edit_requests table
$sql = "CREATE TABLE IF NOT EXISTS profile_edit_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by_hod INT,
    hod_approval_date TIMESTAMP NULL,
    approved_by_admin INT,
    admin_approval_date TIMESTAMP NULL,
    rejection_reason VARCHAR(500),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by_hod) REFERENCES users(id),
    FOREIGN KEY (approved_by_admin) REFERENCES users(id),
    UNIQUE KEY unique_request (student_id)
);";

if ($conn->query($sql) === TRUE) {
    echo "✅ Profile Edit Permission System table created successfully!";
} else {
    echo "❌ Error creating table: " . $conn->error;
}

$conn->close();
?>
