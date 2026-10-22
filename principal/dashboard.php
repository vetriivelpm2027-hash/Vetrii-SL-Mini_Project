<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is principal
$user = $conn->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
if ($user['role'] != 'principal') {
    header("Location: ../unauthorized.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard - RKS Student System</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">👑</div>
            <div class="sidebar-brand">PRINCIPAL PORTAL</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="statistics.php">📈 Statistics</a></li>
            <li><a href="departments.php">🏢 Departments</a></li>
            <li><a href="reports.php">📑 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1>Principal Dashboard</h1>
            <div class="topbar-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="stat-number"><?php echo $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-number"><?php echo $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Departments</h3>
                <div class="stat-number"><?php echo $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count']; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👑 Principal Overview</h3>
            </div>
            <div class="card-body">
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! You have access to all institution statistics and reports.</p>
            </div>
        </div>
    </main>
</div>
</body>
</html>
