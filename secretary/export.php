<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check role
$user = $conn->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
if (!in_array($user['role'], ['secretary'])) {
    header("Location: ../unauthorized.php");
    exit();
}

// Handle export
$export_type = $_GET['type'] ?? '';

if ($export_type == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Registration No', 'First Name', 'Last Name', 'Father Name', 'Mother Name', 'Aadhar', 'Mobile', 'Email', 'Department', 'DOB', 'Joined']);
    
    $students = $conn->query("SELECT s.*, d.dept_name FROM students s LEFT JOIN departments d ON s.department_id = d.id");
    while ($row = $students->fetch_assoc()) {
        fputcsv($output, [
            $row['registration_number'],
            $row['first_name'],
            $row['last_name'],
            $row['father_name'] ?? '',
            $row['mother_name'] ?? '',
            $row['aadhar_number'] ?? '',
            $row['mobile'] ?? '',
            $row['email'] ?? '',
            $row['dept_name'] ?? '',
            $row['date_of_birth'] ?? '',
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - RKS Student System</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">💼</div>
            <div class="sidebar-brand">SECRETARY PORTAL</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="students.php">👨‍🎓 Students</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="export.php" class="active">📥 Export Data</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1>Export Data</h1>
            <div class="topbar-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Export Options</h3>
            </div>
            <div class="card-body">
                <div class="grid">
                    <div class="stat-card">
                        <h3>CSV Format</h3>
                        <p>Export student data as CSV (Excel compatible)</p>
                        <a href="?type=csv" class="btn btn-primary btn-sm" style="margin-top: 10px;">Download CSV</a>
                    </div>
                    <div class="stat-card">
                        <h3>Print</h3>
                        <p>Print all student records</p>
                        <a href="#" onclick="window.print(); return false;" class="btn btn-primary btn-sm" style="margin-top: 10px;">Print Report</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
