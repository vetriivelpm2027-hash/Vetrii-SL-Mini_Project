<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

requireLogin();

// Check if user is HOD using prepared statement
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user || $user['role'] != 'hod') {
    header("Location: ../unauthorized.php");
    exit();
}

// Get statistics with error handling using prepared statements
$students_count = 0;
$students_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students");
if ($students_stmt) {
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    $students_count = $students_result->fetch_assoc()['count'] ?? 0;
    $students_stmt->close();
}

$teachers_count = 0;
$teachers_stmt = $conn->prepare("SELECT COUNT(*) as count FROM teachers");
if ($teachers_stmt) {
    $teachers_stmt->execute();
    $teachers_result = $teachers_stmt->get_result();
    $teachers_count = $teachers_result->fetch_assoc()['count'] ?? 0;
    $teachers_stmt->close();
}

$messages = 0;
$messages_stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
if ($messages_stmt) {
    $messages_stmt->bind_param("i", $_SESSION['user_id']);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();
    $messages = $messages_result->fetch_assoc()['count'] ?? 0;
    $messages_stmt->close();
}

// Get pending profile edit requests count
$pending_profile_edits = 0;
$total_profile_edits = 0;
$approved_profile_edits = 0;
$rejected_profile_edits = 0;

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

// Get all profile edit request stats for HOD's department
$profile_edit_stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' AND approved_by_admin IS NOT NULL THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM profile_edit_requests 
WHERE student_id IN (
    SELECT id FROM students 
    WHERE department_id IN (
        SELECT id FROM departments 
        WHERE hod_id = ?
    )
)");
if ($profile_edit_stmt) {
    $profile_edit_stmt->bind_param("i", $_SESSION['user_id']);
    $profile_edit_stmt->execute();
    $profile_edit_result = $profile_edit_stmt->get_result();
    $profile_stats = $profile_edit_result->fetch_assoc();
    
    $total_profile_edits = $profile_stats['total'] ?? 0;
    $pending_profile_edits = $profile_stats['pending'] ?? 0;
    $approved_profile_edits = $profile_stats['approved'] ?? 0;
    $rejected_profile_edits = $profile_stats['rejected'] ?? 0;
    $profile_edit_stmt->close();
}

$classes_today = 0;
$classes_stmt = $conn->prepare("SELECT COUNT(*) as count FROM timetable WHERE day = DAYNAME(CURDATE())");
if ($classes_stmt) {
    $classes_stmt->execute();
    $classes_result = $classes_stmt->get_result();
    $classes_today = $classes_result->fetch_assoc()['count'] ?? 0;
    $classes_stmt->close();
}

// Get recent activities using prepared statements
$recent_students = [];
$recent_stmt = $conn->prepare("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
if ($recent_stmt) {
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    if ($recent_result) {
        while ($row = $recent_result->fetch_assoc()) {
            $recent_students[] = $row;
        }
    }
    $recent_stmt->close();
}

$pending_requests = [];
$requests_stmt = $conn->prepare("SELECT * FROM requests WHERE status = 'pending' AND department = ? LIMIT 5");
if ($requests_stmt) {
    $requests_stmt->bind_param("s", $user['department']);
    $requests_stmt->execute();
    $requests_result = $requests_stmt->get_result();
    if ($requests_result) {
        while ($row = $requests_result->fetch_assoc()) {
            $pending_requests[] = $row;
        }
    }
    $requests_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard - RKMS Home Student Information System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #FF8C42;
            --primary-dark: #FF6B35;
            --primary-light: #FFB27A;
            --secondary: #2C3E50;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #334155;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
            box-shadow: 0 10px 20px rgba(255, 140, 66, 0.3);
            transform: rotate(-5deg);
            transition: all 0.3s ease;
        }

        .sidebar-logo:hover {
            transform: rotate(0deg);
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            background: linear-gradient(135deg, var(--primary), #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 15px;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 12px;
        }

        .sidebar-menu a i {
            width: 24px;
            font-size: 18px;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 140, 66, 0.1);
            color: var(--primary);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 5px 15px rgba(255, 140, 66, 0.4);
        }

        .sidebar-menu a.logout {
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0;
            color: #ef4444;
        }

        .sidebar-menu a.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px 30px;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 20px;
            padding: 15px 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 140, 66, 0.1);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .page-title .badge {
            background: var(--primary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .notification-badge i {
            font-size: 22px;
            color: var(--gray);
            transition: color 0.3s ease;
        }

        .notification-badge:hover i {
            color: var(--primary);
        }

        .notification-badge .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 12px;
            transition: background 0.3s ease;
        }

        .user-profile:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .user-info {
            display: none;
        }

        @media (min-width: 768px) {
            .user-info {
                display: block;
            }
            
            .user-info .name {
                font-weight: 600;
                color: var(--dark);
                font-size: 14px;
            }
            
            .user-info .role {
                font-size: 12px;
                color: var(--gray);
            }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 140, 66, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 140, 66, 0.15);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.1), rgba(255, 107, 53, 0.1));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .stat-icon i {
            font-size: 30px;
            color: var(--primary);
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: var(--success);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 140, 66, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }

        .card-header .action-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-body {
            padding: 25px;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.1), rgba(255, 107, 53, 0.1));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 12px;
            color: var(--gray);
        }

        /* Request List */
        .request-list {
            list-style: none;
        }

        .request-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .request-item:last-child {
            border-bottom: none;
        }

        .request-info h4 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 15px;
        }

        .request-info p {
            font-size: 13px;
            color: var(--gray);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-badge.approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-badge.rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }

        .action-btn {
            background: white;
            border: 1px solid rgba(255, 140, 66, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            transform: translateY(-5px);
        }

        .action-btn:hover i,
        .action-btn:hover span {
            color: white;
        }

        .action-btn i {
            font-size: 30px;
            color: var(--primary);
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .action-btn span {
            display: block;
            font-weight: 600;
            color: var(--dark);
            transition: color 0.3s ease;
        }

        /* Chart Placeholder */
        .chart-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.02), rgba(255, 107, 53, 0.02));
            border-radius: 15px;
            border: 1px dashed rgba(255, 140, 66, 0.3);
            color: var(--gray);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-content h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-content p {
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .welcome-stats {
            display: flex;
            gap: 30px;
        }

        .welcome-stat {
            text-align: center;
        }

        .welcome-stat .number {
            font-size: 24px;
            font-weight: 800;
            display: block;
        }

        .welcome-stat .label {
            font-size: 12px;
            opacity: 0.8;
        }

        /* Loading Animation */
        .loading {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages 
                    <?php if($messages > 0): ?>
                        <span class="badge" style="margin-left: auto; background: var(--primary); color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px;"><?php echo $messages; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Timetable</a></li>
                <li><a href="attendance.php"><i class="fas fa-check-circle"></i> Attendance</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="profile_edit_requests.php"><i class="fas fa-edit"></i> Profile Requests
                    <?php if($pending_profile_edits > 0): ?>
                        <span class="badge" style="margin-left: auto; background: #ef4444; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;"><?php echo $pending_profile_edits; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>HOD Dashboard</h1>
                    <span class="badge"><?php echo htmlspecialchars($user['department'] ?? 'Department'); ?></span>
                </div>
                
                <div class="top-bar-right">
                    <div class="notification-badge">
                        <i class="fas fa-bell"></i>
                        <?php if($messages > 0): ?>
                            <span class="badge"><?php echo $messages; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <div class="role">Head of Department</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
                    <p>Here's what's happening with your department today</p>
                    <div class="welcome-stats">
                        <div class="welcome-stat">
                            <span class="number"><?php echo $classes_today; ?></span>
                            <span class="label">Classes Today</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?php echo count($pending_requests); ?></span>
                            <span class="label">Pending Requests</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?php echo $teachers_count; ?></span>
                            <span class="label">Teachers</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value"><?php echo $students_count; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>12% from last month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-label">Total Teachers</div>
                    <div class="stat-value"><?php echo $teachers_count; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>5% from last month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-label">Classes Today</div>
                    <div class="stat-value"><?php echo $classes_today; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-clock"></i>
                        <span>Next in 30 mins</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-label">Unread Messages</div>
                    <div class="stat-value"><?php echo $messages; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-down"></i>
                        <span>3 urgent</span>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Request Stats -->
            <div style="margin-top: 30px;">
                <h3 style="font-size: 16px; color: var(--dark); margin-bottom: 20px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                    <i class="fas fa-clipboard-check" style="color: var(--primary); margin-right: 8px;"></i>Profile Edit Permission System
                </h3>
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 69, 19, 0.1)); border-left: 4px solid #636EF1;">
                        <div class="stat-icon">
                            <i class="fas fa-list" style="color: #636EF1;"></i>
                        </div>
                        <div class="stat-label">Total Requests</div>
                        <div class="stat-value"><?php echo $total_profile_edits; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-chart-line" style="color: #636EF1;"></i>
                            <span>All time</span>
                        </div>
                    </div>

                    <div class="stat-card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(255, 193, 7, 0.1)); border-left: 4px solid #FBBF24;">
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half" style="color: #FBBF24;"></i>
                        </div>
                        <div class="stat-label">Waiting Your Approval</div>
                        <div class="stat-value" style="color: #F59E0B;"><?php echo $pending_profile_edits; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-exclamation-circle" style="color: #F59E0B;"></i>
                            <span>Action required</span>
                        </div>
                    </div>

                    <div class="stat-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(34, 197, 94, 0.1)); border-left: 4px solid #10B981;">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle" style="color: #10B981;"></i>
                        </div>
                        <div class="stat-label">Approved by You</div>
                        <div class="stat-value"><?php echo $approved_profile_edits; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <span>Finalized</span>
                        </div>
                    </div>

                    <div class="stat-card" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1)); border-left: 4px solid #EF4444;">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle" style="color: #EF4444;"></i>
                        </div>
                        <div class="stat-label">Rejected</div>
                        <div class="stat-value"><?php echo $rejected_profile_edits; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-down"></i>
                            <span>Denied</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history" style="color: var(--primary); margin-right: 10px;"></i> Recent Activities</h3>
                        <a href="activities.php" class="action-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="card-body">
                        <ul class="activity-list">
                            <?php if($recent_students && $recent_students->num_rows > 0): ?>
                                <?php while($student = $recent_students->fetch_assoc()): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title">New student enrolled: <?php echo htmlspecialchars($student['name']); ?></div>
                                            <div class="activity-time"><?php echo timeAgo($student['created_at']); ?></div>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">No recent activities</div>
                                        <div class="activity-time">Check back later</div>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock" style="color: var(--warning); margin-right: 10px;"></i> Pending Requests</h3>
                        <a href="requests.php" class="action-link">Review <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="card-body">
                        <ul class="request-list">
       
                        <?php if($pending_requests && count($pending_requests) > 0): ?>
                                <?php foreach($pending_requests as $request): ?>
                                    <li class="request-item">
                                        <div class="request-info">
                                            <h4><?php echo htmlspecialchars($request['title']); ?></h4>
                                            <p><?php echo htmlspecialchars($request['student_name']); ?></p>
                                        </div>
                                        <span class="status-badge">Pending</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="request-item">
                                    <div class="request-info">
                                        <h4>No pending requests</h4>
                                        <p>All caught up!</p>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Attendance Overview -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line" style="color: var(--primary); margin-right: 10px;"></i> Attendance Overview</h3>
                    <a href="attendance.php" class="action-link">Detailed Report <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <i class="fas fa-chart-bar" style="font-size: 48px; margin-right: 10px;"></i>
                        <span>Attendance chart will be displayed here</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="students.php?action=add" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Student</span>
                </a>
                <a href="teachers.php?action=add" class="action-btn">
                    <i class="fas fa-user-tie"></i>
                    <span>Add Teacher</span>
                </a>
                <a href="timetable.php" class="action-btn">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Schedule Class</span>
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-file-pdf"></i>
                    <span>Generate Report</span>
                </a>
                <a href="messages.php?action=new" class="action-btn">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Message</span>
                </a>
                <a href="attendance.php" class="action-btn">
                    <i class="fas fa-camera"></i>
                    <span>Mark Attendance</span>
                </a>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to stats
            const stats = document.querySelectorAll('.stat-value');
            stats.forEach(stat => {
                stat.classList.add('loading');
                setTimeout(() => {
                    stat.classList.remove('loading');
                }, 1000);
            });

            // Notification click handler
            document.querySelector('.notification-badge').addEventListener('click', function() {
                window.location.href = 'messages.php';
            });

            // Auto refresh data every 5 minutes
            setInterval(function() {
                location.reload();
            }, 300000);
        });

        // Tooltip function
        function showTooltip(element, message) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = message;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--dark);
                color: white;
                padding: 5px 10px;
                border-radius: 5px;
                font-size: 12px;
                z-index: 1000;
            `;
            
            const rect = element.getBoundingClientRect();
            tooltip.style.top = rect.top - 30 + 'px';
            tooltip.style.left = rect.left + 'px';
            
            document.body.appendChild(tooltip);
            
            setTimeout(() => {
                tooltip.remove();
            }, 2000);
        }
    </script>
</body>
</html>