<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is HOD
$user = $conn->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
if ($user['role'] != 'hod') {
    header("Location: ../unauthorized.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - RKS Student System</title>
    <link rel="stylesheet" href="../public/css/style.css">
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
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="reports.php" class="active">📈 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1>Department Reports</h1>
            <div class="topbar-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📈 Department Reports</h3>
            </div>
            <div class="card-body">
                <p>Reports and analytics for the department</p>
            </div>
        </div>
    </main>
</div>
</body>
</html>
