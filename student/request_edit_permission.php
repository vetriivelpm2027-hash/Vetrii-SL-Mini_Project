<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Initialize debug info
$debug_info = array();
$debug_info[] = "🔍 Request Debug Started";
$debug_info[] = "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'];
$debug_info[] = "User ID from SESSION: " . $user_id;
$debug_info[] = "POST keys received: " . implode(", ", array_keys($_POST));
$debug_info[] = "request_permission in POST: " . (isset($_POST['request_permission']) ? 'YES' : 'NO');

// Get student info
$stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name, CONCAT(u.id) as hod_id
                       FROM students s
                       LEFT JOIN departments d ON s.department_id = d.id
                       LEFT JOIN users u ON d.hod_id = u.id
                       WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    // student record missing – show error message
    echo '<div style="padding:20px; background:#ffebee; border:2px solid #d32f2f;">'
        . '⚠️ Student profile not found. Cannot request edit permission. Please contact support.'</div>';
    exit;
}

$student_id = $student['id'];
$success = '';
$error = '';

// Add to debug info
$debug_info[] = "Student ID retrieved: " . $student_id;
$debug_info[] = "Student department_id: " . ($student['department_id'] ?? 'NULL');
$debug_info[] = "Student name: " . ($student['first_name'] . ' ' . $student['last_name']);
$debug_info[] = "---";

// Ensure profile_edit_requests table exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS profile_edit_requests (
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
)";
$conn->query($create_table_sql);

// Check current permission status
$permission = null;
$perm_stmt = $conn->prepare("SELECT * FROM profile_edit_requests WHERE student_id = ?");
if ($perm_stmt) {
    $perm_stmt->bind_param("i", $student_id);
    $perm_stmt->execute();
    $permission = $perm_stmt->get_result()->fetch_assoc();
    $perm_stmt->close();
}

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_permission'])) {
    $debug_info[] = "✓ POST request detected with request_permission";
    $debug_info[] = "Checking existing permission status...";
    
    // Check if already has pending request
    if ($permission && $permission['status'] == 'pending') {
        $error = "You already have a pending request. Please wait for approval.";
        $debug_info[] = "❌ Already has pending request";
    } elseif ($permission && $permission['status'] == 'approved') {
        // If already approved, redirect to edit profile
        $debug_info[] = "✅ Already approved - redirecting to edit_profile.php";
        header("Location: edit_profile.php");
        exit;
    } else {
        $debug_info[] = "✓ No existing permission - proceeding with new request";
        $debug_info[] = "---NEW REQUEST SUBMISSION---";

        
        $insert_stmt = $conn->prepare("INSERT INTO profile_edit_requests (student_id, user_id, status) VALUES (?, ?, 'pending')");
        if ($insert_stmt) {
            $debug_info[] = "✅ Step 1: Prepare statement successful";
            $insert_stmt->bind_param("ii", $student_id, $user_id);
            $debug_info[] = "✅ Step 1a: Parameters bound (student_id=$student_id, user_id=$user_id)";
            
            if ($insert_stmt->execute()) {
                $new_request_id = $conn->insert_id;
                $debug_info[] = "✅ Step 1b: Execute successful - New Request ID: " . $new_request_id;

                
                // Get HOD and Admin IDs to send them messages
                $hod_id = null;
                $admin_id = null;
                
                // Get HOD ID
                $hod_stmt = $conn->prepare("SELECT u.id FROM users u 
                                           JOIN departments d ON u.id = d.hod_id 
                                           WHERE d.id = ?");
                if ($hod_stmt) {
                    $hod_stmt->bind_param("i", $student['department_id']);
                    $exec_result = $hod_stmt->execute();
                    $debug_info[] = "✅ Step 2: HOD query execute result: " . ($exec_result ? 'SUCCESS' : 'FAILED');
                    $hod_result = $hod_stmt->get_result();
                    if ($hod_result && $hod_result->num_rows > 0) {
                        $hod_row = $hod_result->fetch_assoc();
                        $hod_id = $hod_row['id'];
                        $debug_info[] = "✅ Step 2a: Found HOD ID = " . $hod_id . " (Rows: " . $hod_result->num_rows . ")";
                    } else {
                        $debug_info[] = "❌ Step 2a: HOD not found. Rows returned: " . ($hod_result ? $hod_result->num_rows : 'NULL');
                        $debug_info[] = "    Department ID for search: " . $student['department_id'];
                    }
                    $hod_stmt->close();
                } else {
                    $debug_info[] = "❌ Step 2: Failed to prepare HOD query - " . $conn->error;
                }

                
                // Get Admin ID
                $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
                if ($admin_stmt) {
                    $exec_result = $admin_stmt->execute();
                    $debug_info[] = "✅ Step 3: Admin query execute result: " . ($exec_result ? 'SUCCESS' : 'FAILED');
                    $admin_result = $admin_stmt->get_result();
                    if ($admin_result && $admin_result->num_rows > 0) {
                        $admin_row = $admin_result->fetch_assoc();
                        $admin_id = $admin_row['id'];
                        $debug_info[] = "✅ Step 3a: Found Admin ID = " . $admin_id . " (Rows: " . $admin_result->num_rows . ")";
                    } else {
                        $debug_info[] = "❌ Step 3a: Admin not found. Rows returned: " . ($admin_result ? $admin_result->num_rows : 'NULL');
                    }
                    $admin_stmt->close();
                } else {
                    $debug_info[] = "❌ Step 3: Failed to prepare Admin query - " . $conn->error;
                }
                
                // Send message to HOD
                if ($hod_id) {
                    $debug_info[] = "✅ Step 4: Attempting to send message to HOD (ID: $hod_id)";
                    $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                    if ($msg_stmt) {
                        $subject = "New Profile Edit Permission Request";
                        $msg_text = "Student " . $student['first_name'] . " " . $student['last_name'] . " (Reg: " . $student['registration_number'] . ") has requested permission to edit their profile. Please review and approve/reject the request.";
                        $msg_stmt->bind_param("iiss", $user_id, $hod_id, $subject, $msg_text);
                        if ($msg_stmt->execute()) {
                            $debug_info[] = "✅ Step 4a: Message sent to HOD (Message ID: " . $conn->insert_id . ")";
                        } else {
                            $debug_info[] = "❌ Step 4a: Failed to send message to HOD - " . $msg_stmt->error;
                        }
                        $msg_stmt->close();
                    } else {
                        $debug_info[] = "❌ Step 4: Failed to prepare HOD message - " . $conn->error;
                    }
                } else {
                    $debug_info[] = "⚠️  Step 4: Skipped HOD message (no HOD ID found)";
                }

                
                // Send message to Admin
                if ($admin_id) {
                    $debug_info[] = "✅ Step 5: Attempting to send message to Admin (ID: $admin_id)";
                    $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                    if ($msg_stmt) {
                        $subject = "Profile Edit Permission Request - HOD Approval Pending";
                        $msg_text = "Student " . $student['first_name'] . " " . $student['last_name'] . " (Reg: " . $student['registration_number'] . ") has requested profile edit permission. The HOD of " . ($student['dept_name'] ?? 'their department') . " will review this request.";
                        $msg_stmt->bind_param("iiss", $user_id, $admin_id, $subject, $msg_text);
                        if ($msg_stmt->execute()) {
                            $debug_info[] = "✅ Step 5a: Message sent to Admin (Message ID: " . $conn->insert_id . ")";
                        } else {
                            $debug_info[] = "❌ Step 5a: Failed to send message to Admin - " . $msg_stmt->error;
                        }
                        $msg_stmt->close();
                    } else {
                        $debug_info[] = "❌ Step 5: Failed to prepare Admin message - " . $conn->error;
                    }
                } else {
                    $debug_info[] = "⚠️  Step 5: Skipped Admin message (no Admin ID found)";
                }

                
                $success = "✅ Permission request submitted! Messages sent to your HOD and Admin. Please wait for approval.";
                $debug_info[] = "✅ COMPLETE: Request submitted successfully";
                $debug_info[] = "HOD message sent: " . ($hod_id ? 'YES' : 'NO');
                $debug_info[] = "Admin message sent: " . ($admin_id ? 'YES' : 'NO');
                
                // Refresh permission data
                $perm_stmt = $conn->prepare("SELECT * FROM profile_edit_requests WHERE student_id = ?");
                if ($perm_stmt) {
                    $perm_stmt->bind_param("i", $student_id);
                    $perm_stmt->execute();
                    $permission = $perm_stmt->get_result()->fetch_assoc();
                    $perm_stmt->close();
                    $debug_info[] = "✅ Permission record refreshed from DB";
                }
            } else {
                $error = "Failed to submit request. Please try again. Error: " . $insert_stmt->error;
                $debug_info[] = "❌ Step 1b: Insert failed - " . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $error = "Database error. Permission system not initialized.";
            $debug_info[] = "❌ Step 1: Prepare failed - " . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Edit Permission - Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #FF9933;
            --secondary: #800000;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f3f4f6;
            --dark: #1f2937;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: var(--dark);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #6b7280;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .status-box {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }

        .status-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .status-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .info-item {
            background: var(--light);
            padding: 12px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #ff7f50);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 153, 51, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .timeline {
            margin: 30px 0;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: 14px;
            top: 35px;
            width: 2px;
            height: calc(100% + 20px);
            background: #e5e7eb;
        }

        .timeline-item:last-child::after {
            display: none;
        }

        .timeline-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
        }

        .timeline-dot.completed {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .timeline-dot.pending {
            background: var(--warning);
            border-color: var(--warning);
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
        }

        .timeline-content {
            flex: 1;
            padding-top: 5px;
        }

        .timeline-step {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }

        .timeline-date {
            font-size: 12px;
            color: #6b7280;
        }

        .note {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 12px;
            margin: 20px 0;
            font-size: 13px;
            color: #92400e;
            display: flex;
            gap: 10px;
        }

        .note i {
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Profile Edit Permission</h1>
            <p>Request and track your profile editing permission</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- DEBUG OUTPUT -->
        <?php if (!empty($debug_info)): ?>
            <div style="background:#e8f4f8;color:#0c4a6e;padding:15px;border:1px solid #0284c7;border-radius:8px;margin-bottom:20px;font-size:13px;font-family:monospace;">
                <b style="font-weight:bold;display:block;margin-bottom:10px;">🔧 DEBUG INFO:</b>
                <?php foreach ($debug_info as $info): ?>
                    <div style="padding:5px;border-bottom:1px solid #0284c7;"><?php echo htmlspecialchars($info); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="status-box">
            <div class="status-label">Current Status</div>
            <div class="status-value">
                <?php if ($permission): ?>
                    <?php if ($permission['status'] == 'pending'): ?>
                        <span class="status-badge status-pending">
                            <i class="fas fa-hourglass-half"></i> Pending Approval
                        </span>
                    <?php elseif ($permission['status'] == 'approved'): ?>
                        <span class="status-badge status-approved">
                            <i class="fas fa-check"></i> Approved
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-rejected">
                            <i class="fas fa-times"></i> Rejected
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #6b7280;">
                        <i class="fas fa-clock"></i> No Request Yet
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Info -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Registration No.</div>
                <div class="info-value"><?php echo htmlspecialchars($student['registration_number']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Department</div>
                <div class="info-value"><?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Semester</div>
                <div class="info-value"><?php echo intval($student['semester'] ?? 1); ?></div>
            </div>
        </div>

        <!-- Timeline -->
        <?php if ($permission): ?>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-step">✅ Request Submitted</div>
                        <div class="timeline-date"><?php echo date('d M Y, h:i A', strtotime($permission['requested_at'])); ?></div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot <?php echo ($permission['status'] == 'approved' || $permission['status'] == 'rejected') ? 'completed' : 'pending'; ?>">
                        <i class="fas fa-<?php echo ($permission['status'] == 'approved' || $permission['status'] == 'rejected') ? 'check' : 'hourglass-half'; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-step">📋 Awaiting HOD Approval</div>
                        <div class="timeline-date">
                            <?php 
                            if ($permission['hod_approval_date']) {
                                echo date('d M Y, h:i A', strtotime($permission['hod_approval_date']));
                            } else {
                                echo "Pending...";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot <?php echo ($permission['status'] == 'approved') ? 'completed' : ($permission['admin_approval_date'] ? 'completed' : 'pending'); ?>">
                        <i class="fas fa-<?php echo ($permission['status'] == 'approved' || $permission['admin_approval_date']) ? 'check' : 'hourglass-half'; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-step">👤 Awaiting Admin Approval</div>
                        <div class="timeline-date">
                            <?php 
                            if ($permission['admin_approval_date']) {
                                echo date('d M Y, h:i A', strtotime($permission['admin_approval_date']));
                            } else {
                                echo "Pending...";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($permission['status'] == 'rejected'): ?>
                <div class="note">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Rejection Reason:</strong><br>
                        <?php echo htmlspecialchars($permission['rejection_reason'] ?? 'No reason provided'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($permission['status'] == 'approved'): ?>
                <div class="button-group">
                    <a href="edit_profile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Your Profile
                    </a>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            <?php else: ?>
                <div class="button-group">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="note">
                <i class="fas fa-info-circle"></i>
                <p>You need to request permission from HOD and Admin before you can edit your profile. This ensures data integrity and proper approval workflow.</p>
            </div>

            <form method="POST">
                <button type="submit" name="request_permission" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                    <i class="fas fa-paper-plane"></i> Request Permission to Edit Profile
                </button>
            </form>

            <div class="button-group" style="margin-top: 15px;">
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
