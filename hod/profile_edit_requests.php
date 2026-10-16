<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Ensure user is HOD
if ($_SESSION['role'] !== 'hod') {
    header("Location: ../unauthorized.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

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

// Get HOD's department
$hod_stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'hod'");
$hod_stmt->bind_param("i", $user_id);
$hod_stmt->execute();
$hod_data = $hod_stmt->get_result()->fetch_assoc();
$hod_stmt->close();

// DEBUG OUTPUT - Check if debug mode is on from GET or POST
if ((isset($_GET['debug']) && $_GET['debug'] == '1') || (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] == '1')) {
    echo '<div style="background:#e8f4f8;color:#0c4a6e;padding:15px;border:1px solid #0284c7;border-radius:8px;margin-bottom:20px;font-size:13px;font-family:monospace;">';
    echo '<b style="font-weight:bold;display:block;margin-bottom:10px;">🔧 DEBUG INFO (HOD):</b>';
    echo 'HOD User ID: ' . htmlspecialchars($user_id) . '<br>';
    echo 'HOD Department ID: ' . htmlspecialchars($hod_data['department_id'] ?? 'N/A') . '<br>';
    echo 'Database Error: ' . ($conn->error ?: 'None') . '<br>';
    echo 'Query: SELECT per.*, s.registration_number, s.first_name, s.last_name, s.department_id, d.full_name as dept_name FROM profile_edit_requests per JOIN students s ON per.student_id = s.id LEFT JOIN departments d ON s.department_id = d.id WHERE per.status = "pending" AND s.department_id IN (SELECT id FROM departments WHERE hod_id = ' . $user_id . ')<br>';
    echo 'Pending Requests Fetched: ' . count($requests) . '<br>';
    if (!empty($requests)) {
        foreach ($requests as $req) {
            echo '<br><b>Request ID: ' . $req['id'] . '</b><br>';
            echo 'Student: ' . $req['first_name'] . ' ' . $req['last_name'] . ' (Reg: ' . $req['registration_number'] . ')<br>';
            echo 'Department ID: ' . $req['department_id'] . ' (' . $req['dept_name'] . ')<br>';
            echo 'Status: ' . $req['status'] . '<br>';
            echo 'Requested At: ' . $req['requested_at'] . '<br>';
        }
    } else {
        echo '<br><span style="color:#dc2626;">⚠️ No pending requests found!</span>';
    }
    echo '</div>';
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action']; // 'approve' or 'reject'
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    if ($action == 'reject' && empty($rejection_reason)) {
        $error = "Please provide a reason for rejection.";
    } else {
        // Get request details to get student info
        $req_stmt = $conn->prepare("SELECT per.*, s.first_name, s.last_name, s.registration_number, s.user_id as student_user_id FROM profile_edit_requests per JOIN students s ON per.student_id = s.id WHERE per.id = ?");
        if ($req_stmt) {
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $req_data = $req_stmt->get_result()->fetch_assoc();
            $req_stmt->close();
            
            $student_user_id = $req_data['student_user_id'] ?? null;
            
            if ($action == 'approve') {
                $update_stmt = $conn->prepare("UPDATE profile_edit_requests SET status = 'approved', 
                                              approved_by_hod = ?, hod_approval_date = NOW() WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("ii", $user_id, $request_id);
                    if ($update_stmt->execute()) {
                        // Send approval message to student
                        if ($student_user_id) {
                            $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                            if ($msg_stmt) {
                                $subject = "✅ HOD Approved - Profile Edit Permission";
                                $msg_text = "Your request to edit your profile has been approved by your HOD. It is now pending Admin approval.";
                                $msg_stmt->bind_param("iiss", $user_id, $student_user_id, $subject, $msg_text);
                                $msg_stmt->execute();
                                $msg_stmt->close();
                            }
                        }
                        
                        // Send notification to Admin
                        $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
                        if ($admin_stmt) {
                            $admin_stmt->execute();
                            $admin_result = $admin_stmt->get_result()->fetch_assoc();
                            $admin_id = $admin_result['id'] ?? null;
                            $admin_stmt->close();
                            
                            if ($admin_id) {
                                $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                                if ($msg_stmt) {
                                    $subject = "HOD Approved - Profile Edit Permission Request";
                                    $msg_text = "Student " . $req_data['first_name'] . " " . $req_data['last_name'] . " (Reg: " . $req_data['registration_number'] . ") has been approved by HOD. Please review and give final approval.";
                                    $msg_stmt->bind_param("iiss", $user_id, $admin_id, $subject, $msg_text);
                                    $msg_stmt->execute();
                                    $msg_stmt->close();
                                }
                            }
                        }
                        
                        $success = "✅ Request approved! Student and Admin have been notified.";
                    } else {
                        $error = "Failed to process request.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "Database error.";
                }
            } else {
                $update_stmt = $conn->prepare("UPDATE profile_edit_requests SET status = 'rejected', 
                                              rejection_reason = ?, hod_approval_date = NOW() WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $rejection_reason, $request_id);
                    if ($update_stmt->execute()) {
                        // Send rejection message to student
                        if ($student_user_id) {
                            $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                            if ($msg_stmt) {
                                $subject = "❌ Profile Edit Permission Rejected by HOD";
                                $msg_text = "Your request to edit your profile has been rejected by your HOD. Reason: " . htmlspecialchars($rejection_reason);
                                $msg_stmt->bind_param("iiss", $user_id, $student_user_id, $subject, $msg_text);
                                $msg_stmt->execute();
                                $msg_stmt->close();
                            }
                        }
                        
                        $success = "❌ Request rejected! Student has been notified.";
                    } else {
                        $error = "Failed to process request.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "Database error.";
                }
            }
        }
    }
}

// Get pending requests for HOD's department
$query = "SELECT per.*, s.registration_number, s.first_name, s.last_name, s.department_id, d.full_name as dept_name
          FROM profile_edit_requests per
          JOIN students s ON per.student_id = s.id
          LEFT JOIN departments d ON s.department_id = d.id
          WHERE per.status = 'pending' AND s.department_id IN (
              SELECT id FROM departments WHERE hod_id = ?
          )
          ORDER BY per.requested_at DESC";

$requests = [];
$debug_hod = array();
$debug_hod[] = "HOD User ID: " . $user_id;

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $debug_hod[] = "Total pending requests found: " . count($requests);
} else {
    $debug_hod[] = "Query prepare failed: " . $conn->error;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Edit Requests - HOD Dashboard</title>
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
            --cream: #FFF8E7;
        }

        body {
            background: var(--cream);
            padding: 20px;
            color: var(--dark);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
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

        .requests-grid {
            display: grid;
            gap: 20px;
        }

        .request-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: start;
        }

        .request-info {
            display: grid;
            gap: 15px;
        }

        .student-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #ff7f50);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .student-details h3 {
            font-size: 16px;
            margin-bottom: 3px;
        }

        .student-details p {
            font-size: 12px;
            color: #6b7280;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 13px;
        }

        .info-item {
            display: flex;
            gap: 8px;
        }

        .info-item span:first-child {
            color: #6b7280;
            font-weight: 500;
        }

        .request-date {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 10px;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 200px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
        }

        .btn-approve {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-reject {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-content h3 {
            margin-bottom: 15px;
        }

        .modal-content textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
            font-family: 'Inter', sans-serif;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-buttons .btn-submit {
            background: var(--danger);
            color: white;
        }

        .modal-buttons .btn-cancel {
            background: var(--light);
            color: var(--dark);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #6b7280;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .empty-state p {
            color: #9ca3af;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .request-card {
                grid-template-columns: 1fr;
            }

            .info-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: row;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-check-circle"></i> Profile Edit Requests
            </h1>
            <p>Approve or reject student profile edit requests</p>
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

        <?php if (count($requests) > 0): ?>
            <div class="requests-grid">
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="request-info">
                            <div class="student-header">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($req['first_name'], 0, 1)); ?>
                                </div>
                                <div class="student-details">
                                    <h3><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($req['registration_number']); ?></p>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-item">
                                    <span>Department:</span>
                                    <strong><?php echo htmlspecialchars($req['dept_name'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>Request ID:</span>
                                    <strong>#<?php echo $req['id']; ?></strong>
                                </div>
                            </div>

                            <div class="request-date">
                                📅 Requested: <?php echo date('d M Y, h:i A', strtotime($req['requested_at'])); ?>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-reject" onclick="openRejectModal(<?php echo $req['id']; ?>)">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Pending Requests</h3>
                <p>All profile edit requests have been processed.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3>Reject Request</h3>
            <p>Please provide a reason for rejection:</p>
            <form method="POST">
                <input type="hidden" id="rejectRequestId" name="request_id">
                <textarea name="rejection_reason" placeholder="Enter reason for rejection..." required></textarea>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" name="action" value="reject" class="btn-submit">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(requestId) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>
