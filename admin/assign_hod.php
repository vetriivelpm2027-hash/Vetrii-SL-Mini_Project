<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$error = '';
$success = '';

// handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = isset($_POST['dept_id']) ? (int)$_POST['dept_id'] : 0;
    $hod_id = isset($_POST['hod_id']) ? (int)$_POST['hod_id'] : 0;
    if ($dept_id > 0 && $hod_id > 0) {
        // First, check if department already has an HOD
        $check_stmt = $conn->prepare("SELECT hod_id FROM departments WHERE id = ?");
        $check_stmt->bind_param('i', $dept_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $current_hod = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // Update the HOD
        $stmt = $conn->prepare("UPDATE departments SET hod_id = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $hod_id, $dept_id);
            if ($stmt->execute()) {
                $success = 'HOD assigned successfully.';
                
                // Optional: Log the action (activity_log table is singular)
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, 'assign_hod', ?)");
                $details = "Assigned user ID $hod_id as HOD for department ID $dept_id";
                $admin_id = $_SESSION['user_id'];
                if ($log_stmt) {
                    $log_stmt->bind_param('is', $admin_id, $details);
                    $log_stmt->execute();
                    $log_stmt->close();
                } else {
                    // optional: track logging failure in $error if needed
                    // $error .= ' (log prep error: '.$conn->error.')';
                }
            } else {
                $error = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Failed to prepare statement: ' . $conn->error;
        }
    } else {
        $error = 'Please select both department and user.';
    }
}

// fetch departments with current HOD info
$departments = [];
$teachers = [];
if (isset($conn) && $conn) {
    // Get departments with current HOD information
    $res = $conn->query("
        SELECT d.id, d.dept_name, d.hod_id, u.username as hod_name 
        FROM departments d 
        LEFT JOIN users u ON d.hod_id = u.id 
        ORDER BY d.dept_name
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $departments[] = $r;
        }
    }
    
    // Get available teachers (class teachers)
    $res = $conn->query("
        SELECT id, username, full_name, email, department
        FROM users 
        WHERE role='class_teacher' 
        ORDER BY username
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $teachers[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assign HOD - RKMS Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fff4e6 0%, #ffe4cc 100%);
            padding: 2rem 1rem;
            color: #2b2b2b;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #b45309;
            font-size: 2.2rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(180, 83, 9, 0.1);
        }
        
        .header p {
            color: #92400e;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(180, 83, 9, 0.15);
            overflow: hidden;
            border: 1px solid #fed7aa;
        }
        
        .card-header {
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
            color: white;
            padding: 1.2rem 1.5rem;
        }
        
        .card-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-header h2 i {
            font-size: 1.6rem;
        }
        
        .card-body {
            padding: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #92400e;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        select, input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #fed7aa;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fffaf0;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: #b45309;
            box-shadow: 0 0 0 3px rgba(180, 83, 9, 0.2);
        }
        
        select:hover, input:hover {
            border-color: #92400e;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(180, 83, 9, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(180, 83, 9, 0.4);
            background: linear-gradient(135deg, #92400e 0%, #b45309 100%);
        }
        
        .btn-secondary {
            background: #6b4a2b;
            margin-left: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: #5a3d23;
        }
        
        .alert {
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .alert-success {
            background: #f0f9eb;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            border-left: 5px solid #2e7d32;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            border-left: 5px solid #c62828;
        }
        
        .department-list {
            list-style: none;
        }
        
        .department-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #fed7aa;
            transition: background 0.2s ease;
        }
        
        .department-item:hover {
            background: #fff4e6;
        }
        
        .department-item:last-child {
            border-bottom: none;
        }
        
        .dept-info {
            flex: 1;
        }
        
        .dept-name {
            font-weight: 600;
            color: #92400e;
            font-size: 1.1rem;
        }
        
        .hod-name {
            color: #6b4a2b;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .hod-name i {
            color: #b45309;
            font-size: 0.9rem;
        }
        
        .badge {
            background: #f0f0f0;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-assigned {
            background: #e6ffed;
            color: #2e7d32;
        }
        
        .badge-unassigned {
            background: #ffebee;
            color: #c62828;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .icon {
            font-style: normal;
            margin-right: 0.3rem;
        }
        
        .teacher-info {
            display: flex;
            flex-direction: column;
        }
        
        .teacher-name {
            font-weight: 600;
            color: #92400e;
        }
        
        .teacher-email {
            font-size: 0.8rem;
            color: #6b4a2b;
        }
        
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
    <!-- Font Awesome for icons (optional, but nice) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-tie" style="margin-right: 10px;"></i>Assign Head of Department</h1>
            <p>Manage department leadership assignments</p>
        </div>
        
        <div class="main-grid">
            <!-- Assignment Form -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-pen-fancy"></i> New Assignment</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" id="assignForm">
                        <div class="form-group">
                            <label for="dept_id">
                                <i class="fas fa-building"></i> Select Department
                            </label>
                            <select name="dept_id" id="dept_id" required>
                                <option value="">-- Choose a department --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>">
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                        <?php if ($d['hod_id']): ?> 
                                            (Current: <?php echo htmlspecialchars($d['hod_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="hod_id">
                                <i class="fas fa-chalkboard-teacher"></i> Select Class Teacher
                            </label>
                            <select name="hod_id" id="hod_id" required>
                                <option value="">-- Choose a teacher --</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?php echo $t['id']; ?>">
                                        <?php echo htmlspecialchars($t['username']); ?>
                                        <?php if (!empty($t['department'])): ?>
                                            (<?php echo htmlspecialchars($t['department']); ?>)
                                        <?php endif; ?>
                                        <?php if (!empty($t['full_name'])): ?>
                                            - <?php echo htmlspecialchars($t['full_name']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn" type="submit">
                                <i class="fas fa-check-circle"></i> Assign HOD
                            </button>
                            <a href="departments.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Departments
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Current Assignments -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list-alt"></i> Current Assignments</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($departments)): ?>
                        <p style="text-align: center; color: #6b4a2b;">No departments found.</p>
                    <?php else: ?>
                        <ul class="department-list">
                            <?php foreach ($departments as $dept): ?>
                                <li class="department-item">
                                    <div class="dept-info">
                                        <div class="dept-name">
                                            <i class="fas fa-university" style="color: #b45309; margin-right: 8px;"></i>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </div>
                                        <div class="hod-name">
                                            <?php if ($dept['hod_id'] && $dept['hod_name']): ?>
                                                <i class="fas fa-user-check"></i>
                                                HOD: <?php echo htmlspecialchars($dept['hod_name']); ?>
                                            <?php else: ?>
                                                <i class="fas fa-user-times"></i>
                                                No HOD assigned
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo $dept['hod_id'] ? 'badge-assigned' : 'badge-unassigned'; ?>">
                                        <?php echo $dept['hod_id'] ? 'Assigned' : 'Vacant'; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Optional JavaScript for enhanced UX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('assignForm');
            const deptSelect = document.getElementById('dept_id');
            const hodSelect = document.getElementById('hod_id');
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                if (!deptSelect.value || !hodSelect.value) {
                    e.preventDefault();
                    alert('Please select both department and teacher');
                }
            });
            
            // Add visual feedback for select changes
            deptSelect.addEventListener('change', function() {
                this.style.borderColor = '#b45309';
            });
            
            hodSelect.addEventListener('change', function() {
                this.style.borderColor = '#b45309';
            });
        });
    </script>
</body>
</html>