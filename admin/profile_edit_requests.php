<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

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
                                              approved_by_admin = ?, admin_approval_date = NOW() WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("ii", $user_id, $request_id);
                    if ($update_stmt->execute()) {
                        // Send approval message to student
                        if ($student_user_id) {
                            $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                            if ($msg_stmt) {
                                $subject = "✅ Profile Edit Permission APPROVED";
                                $msg_text = "Your profile edit permission has been fully approved by both HOD and Admin. You can now edit your profile!";
                                $msg_stmt->bind_param("iiss", $user_id, $student_user_id, $subject, $msg_text);
                                $msg_stmt->execute();
                                $msg_stmt->close();
                            }
                        }
                        
                        $success = "✅ Request approved! Student has been notified and can now edit their profile.";
                    } else {
                        $error = "Failed to process request.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "Database error.";
                }
            } else {
                $update_stmt = $conn->prepare("UPDATE profile_edit_requests SET status = 'rejected', 
                                              rejection_reason = ?, admin_approval_date = NOW() WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $rejection_reason, $request_id);
                    if ($update_stmt->execute()) {
                        // Send rejection message to student
                        if ($student_user_id) {
                            $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
                            if ($msg_stmt) {
                                $subject = "❌ Profile Edit Permission REJECTED by Admin";
                                $msg_text = "Your profile edit permission request has been rejected by the Admin. Reason: " . htmlspecialchars($rejection_reason);
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

// Get all pending requests with HOD approval
$query = "SELECT per.*, s.registration_number, s.first_name, s.last_name, d.full_name as dept_name,
          u_hod.username as hod_name, u_student.username as student_username
          FROM profile_edit_requests per
          JOIN students s ON per.student_id = s.id
          LEFT JOIN departments d ON s.department_id = d.id
          LEFT JOIN users u_hod ON per.approved_by_hod = u_hod.id
          LEFT JOIN users u_student ON per.user_id = u_student.id
          WHERE per.status = 'approved' AND per.approved_by_hod IS NOT NULL
          ORDER BY per.hod_approval_date DESC";

$requests = [];
$debug_admin = array();
$debug_admin[] = "Admin User ID: " . $user_id;

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $debug_admin[] = "HOD-approved requests found (awaiting admin approval): " . count($requests);
} else {
    $debug_admin[] = "Query prepare failed: " . $conn->error;
}

// Get rejected requests for admin overview
$rejected_query = "SELECT COUNT(*) as rejected_count FROM profile_edit_requests WHERE status = 'rejected'";
$rejected_result = $conn->query($rejected_query);
$rejected_stats = $rejected_result ? $rejected_result->fetch_assoc() : ['rejected_count' => 0];

// Get all requests count for debug
$all_requests_query = "SELECT COUNT(*) as total FROM profile_edit_requests";
$all_requests_result = $conn->query($all_requests_query);
$all_requests_stats = $all_requests_result ? $all_requests_result->fetch_assoc() : ['total' => 0];
$debug_admin[] = "Total requests in system: " . $all_requests_stats['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Edit Requests - Admin Dashboard</title>
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
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #6b7280;
            font-size: 14px;
        }

        .stats {
            display: flex;
            gap: 15px;
        }

        .stat-box {
            background: linear-gradient(135deg, var(--primary), #ff7f50);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
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
            background: linear-gradient(135deg, var(--secondary), #a0522d);
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

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            font-size: 13px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .info-label {
            color: #6b7280;
            font-weight: 500;
        }

        .info-value {
            color: var(--dark);
            font-weight: 600;
        }

        .approval-status {
            background: rgba(16, 185, 129, 0.1);
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .request-date {
            font-size: 12px;
            color: #9ca3af;
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
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .request-card {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: row;
                width: auto;
            }

            .stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>
                    <i class="fas fa-clipboard-check"></i> Profile Edit Requests
                </h1>
                <p>Final approval for student profile edit requests</p>
            </div>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($requests); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-box" style="background: rgba(239, 68, 68, 0.2); color: var(--danger);">
                    <div class="stat-number"><?php echo $rejected_stats['rejected_count']; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
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
        <?php if ((isset($_GET['debug']) && $_GET['debug'] == '1') || (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] == '1')): ?>
            <div style="background:#e8f4f8;color:#0c4a6e;padding:15px;border:1px solid #0284c7;border-radius:8px;margin-bottom:20px;font-size:13px;font-family:monospace;">
                <b style="font-weight:bold;display:block;margin-bottom:10px;">🔧 DEBUG INFO (ADMIN):</b>
                <?php foreach ($debug_admin as $info): ?>
                    <div style="padding:5px;border-bottom:1px solid #0284c7;"><?php echo htmlspecialchars($info); ?></div>
                <?php endforeach; ?>
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

                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Department</span>
                                    <span class="info-value"><?php echo htmlspecialchars($req['dept_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">HOD Approval</span>
                                    <span class="info-value"><?php echo htmlspecialchars($req['hod_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Request ID</span>
                                    <span class="info-value">#<?php echo $req['id']; ?></span>
                                </div>
                            </div>

                            <div class="approval-status">
                                <i class="fas fa-check"></i> ✓ HOD Approved - <?php echo date('d M Y', strtotime($req['hod_approval_date'])); ?>
                            </div>

                            <div class="request-date">
                                📅 Initial Request: <?php echo date('d M Y, h:i A', strtotime($req['requested_at'])); ?>
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
