<?php
/**
 * Registration Flow Test
 * This script tests if student records are being created properly
 */

require_once 'config/database.php';

// Check database connection
if (!$conn) {
    die("Database connection failed!");
}

echo "Database connection: OK\n\n";

// Check if students table exists
$table_check = $conn->query("SHOW TABLES LIKE 'students'");
if ($table_check->num_rows == 0) {
    die("Students table does not exist!\n");
}

echo "Students table: OK\n\n";

// Get recent student registrations
$query = "SELECT u.id, u.username, u.role, u.created_at, s.id as student_id, s.registration_number, s.first_name, s.last_name
          FROM users u
          LEFT JOIN students s ON u.id = s.user_id
          WHERE u.role = 'student'
          ORDER BY u.created_at DESC
          LIMIT 5";

$result = $conn->query($query);

echo "Recent Student Registrations:\n";
echo "========================================\n";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Username: " . $row['username'] . "\n";
        echo "User ID: " . $row['id'] . "\n";
        echo "Student Record: " . ($row['student_id'] ? "YES (ID: " . $row['student_id'] . ")" : "❌ MISSING") . "\n";
        if ($row['student_id']) {
            echo "  Reg No: " . $row['registration_number'] . "\n";
            echo "  Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        }
        echo "Created: " . $row['created_at'] . "\n";
        echo "---\n";
    }
} else {
    echo "No student users found in database.\n";
}

echo "\n========================================\n";
echo "Test Complete\n\n";

echo "If students show '❌ MISSING', then the student record is not being created.\n";
echo "Check the server error logs for details.\n";
?>
