<?php
/**
 * Fix Missing Student Records
 * Creates student records for users with role 'student' that don't have them
 */

require_once 'config/database.php';

echo "Checking for users with role 'student' missing student records...\n\n";

// Get all users with role 'student'
$user_query = "SELECT u.id, u.username, u.email, u.department FROM users u WHERE u.role = 'student'";
$user_result = $conn->query($user_query);

if (!$user_result) {
    die("Error querying users: " . $conn->error);
}

$fixed = 0;
$skipped = 0;

while ($user = $user_result->fetch_assoc()) {
    // Check if student record exists
    $student_check = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
    $student_check->bind_param("i", $user['id']);
    $student_check->execute();
    $student_result = $student_check->get_result();

    if ($student_result->num_rows == 0) {
        // No student record, create one
        echo "Creating student record for user: {$user['username']}\n";

        // Get department id from department name/code
        $dept_id = null;
        if (!empty($user['department'])) {
            $dept_query = $conn->prepare("SELECT id FROM departments WHERE dept_code = ? OR dept_name = ? LIMIT 1");
            $dept_query->bind_param("ss", $user['department'], $user['department']);
            $dept_query->execute();
            $dept_result = $dept_query->get_result();
            if ($dept_result->num_rows > 0) {
                $dept_id = $dept_result->fetch_assoc()['id'];
            }
            $dept_query->close();
        }

        // Generate registration number
        $reg_no = "RKS" . date('Y') . str_pad($user['id'], 5, "0", STR_PAD_LEFT);

        // Insert student record
        $insert_stmt = $conn->prepare("INSERT INTO students (user_id, registration_number, first_name, last_name, email, department_id, semester) VALUES (?, ?, ?, ?, ?, ?, 1)");
        if ($insert_stmt) {
            // Use username as first name for now, can be updated later
            $first_name = $user['username'];
            $last_name = '';
            $email = $user['email'] ?: '';

            $insert_stmt->bind_param("issssi", $user['id'], $reg_no, $first_name, $last_name, $email, $dept_id);
            if ($insert_stmt->execute()) {
                echo "✓ Created student record with registration: {$reg_no}\n";
                $fixed++;
            } else {
                echo "✗ Failed to create student record: " . $insert_stmt->error . "\n";
            }
            $insert_stmt->close();
        } else {
            echo "✗ Prepare failed for student insert\n";
        }
    } else {
        echo "Student record already exists for: {$user['username']}\n";
        $skipped++;
    }

    $student_check->close();
}

echo "\n========================================\n";
echo "Fix Complete!\n";
echo "Created: {$fixed}\n";
echo "Skipped: {$skipped}\n";
echo "========================================\n";

$user_result->close();
$conn->close();
?></content>
<parameter name="filePath">d:\xampp\htdocs\final\fix_student_records.php