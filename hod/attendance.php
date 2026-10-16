<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is HOD
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if ($user['role'] != 'hod') {
    header("Location: ../unauthorized.php");
    exit();
}

// Get HOD's department
$dept_stmt = $conn->prepare("SELECT * FROM departments WHERE hod_id = ?");
$dept_stmt->bind_param("i", $user_id);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$department = $dept_result->fetch_assoc();
$dept_stmt->close();

if (!$department) {
    echo '<div style="padding:20px; background:#ffebee; border:2px solid #d32f2f;">⚠️ You are not assigned as HOD of any department.</div>';
    exit;
}

$error = '';
$success = '';

// Get date filters
$today = date('Y-m-d');
$view_date = $_GET['date'] ?? $today;
$view_month = $_GET['month'] ?? date('Y-m');

// Get students in this department
$students_stmt = $conn->prepare("SELECT s.*, u.username 
                                 FROM students s 
                                 JOIN users u ON s.user_id = u.id 
                                 WHERE s.department_id = ? 
                                 ORDER BY s.semester, s.registration_number");
$students_stmt->bind_param("i", $department['id']);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students_stmt->close();

// Get attendance records for view date
$attendance_stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE attendance_date = ?");
$attendance_stmt->bind_param("s", $view_date);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance_data = [];
while ($att = $attendance_result->fetch_assoc()) {
    $attendance_data[$att['student_id']] = $att['status'];
}
$attendance_result->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Overview - HOD Portal</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .attendance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #1976d2; }
        .stat-number { font-size: 24px; font-weight: bold; color: #1976d2; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .attendance-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .attendance-table th, .attendance-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .attendance-table th { background: #f5f5f5; font-weight: bold; }
        .status-present { color: green; }
        .status-absent { color: red; }
        .status-leave { color: orange; }
        .status-none { color: #999; }
        .filters { background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .filter-input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; background: #1976d2; color: white; }
        .btn:hover { background: #1565c0; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🏆</div>
            <div class="sidebar-brand">HOD PORTAL</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="students.php">👨‍🎓 Students</a></li>
            <li><a href="teachers.php">👨‍🏫 Teachers</a></li>
            <li><a href="attendance.php" class="active">📋 Attendance</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1>Attendance Overview - <?php echo htmlspecialchars($department['full_name']); ?></h1>
            <div class="topbar-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Daily Attendance Report</h3>
            </div>
            <div class="card-body">
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <label for="date">Select Date:</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($view_date); ?>" class="filter-input" />
                        <button type="submit" class="btn">Show Attendance</button>
                        <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn">Today</a>
                    </form>
                </div>

                <div class="attendance-summary">
                    <?php
                    $total = 0;
                    $present = 0;
                    $absent = 0;
                    $leave = 0;

                    // Count attendance from results
                    $count_stmt = $conn->prepare("SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) as leave_count
                    FROM attendance 
                    WHERE attendance_date = ? AND student_id IN (SELECT id FROM students WHERE department_id = ?)");
                    $count_stmt->bind_param("si", $view_date, $department['id']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result()->fetch_assoc();
                    $count_stmt->close();

                    $total = $count_result['total'] ?? 0;
                    $present = $count_result['present_count'] ?? 0;
                    $absent = $count_result['absent_count'] ?? 0;
                    $leave = $count_result['leave_count'] ?? 0;
                    ?>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $total; ?></div>
                        <div class="stat-label">Marked</div>
                    </div>
                    <div class="stat-box" style="border-left-color: green;">
                        <div class="stat-number status-present"><?php echo $present; ?></div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-box" style="border-left-color: red;">
                        <div class="stat-number status-absent"><?php echo $absent; ?></div>
                        <div class="stat-label">Absent</div>
                    </div>
                    <div class="stat-box" style="border-left-color: orange;">
                        <div class="stat-number status-leave"><?php echo $leave; ?></div>
                        <div class="stat-label">Leave</div>
                    </div>
                </div>

                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $students_result->data_seek(0);
                        while ($student = $students_result->fetch_assoc()):
                            $status = $attendance_data[$student['id']] ?? 'Not Marked';
                            $status_class = match($status) {
                                'Present' => 'status-present',
                                'Absent' => 'status-absent',
                                'Leave' => 'status-leave',
                                default => 'status-none'
                            };
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></td>
                            <td><?php echo htmlspecialchars($student['semester'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">📊 Monthly Attendance Summary - <?php echo date('F Y', strtotime($view_month . '-01')); ?></h3>
            </div>
            <div class="card-body">
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <label for="month">Select Month:</label>
                        <input type="month" id="month" name="month" value="<?php echo htmlspecialchars($view_month); ?>" class="filter-input" />
                        <button type="submit" class="btn">Show Report</button>
                    </form>
                </div>

                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Present Days</th>
                            <th>Absent Days</th>
                            <th>Leave Days</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $month_start = $view_month . '-01';
                        $month_end = date('Y-m-t', strtotime($month_start));
                        
                        $students_result->data_seek(0);
                        while ($student = $students_result->fetch_assoc()):
                            $att_stmt = $conn->prepare("SELECT 
                                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                                SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) as leaves,
                                COUNT(*) as total
                            FROM attendance 
                            WHERE student_id = ? AND attendance_date BETWEEN ? AND ?");
                            $att_stmt->bind_param("iss", $student['id'], $month_start, $month_end);
                            $att_stmt->execute();
                            $att_data = $att_stmt->get_result()->fetch_assoc();
                            $att_stmt->close();

                            $present = $att_data['present'] ?? 0;
                            $absent = $att_data['absent'] ?? 0;
                            $leaves = $att_data['leaves'] ?? 0;
                            $total = $att_data['total'] ?? 1;
                            $percentage = ($total > 0) ? round(($present / $total) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td class="status-present"><?php echo $present; ?></td>
                            <td class="status-absent"><?php echo $absent; ?></td>
                            <td class="status-leave"><?php echo $leaves; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>