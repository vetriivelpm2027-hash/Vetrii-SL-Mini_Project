<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// DEBUG INFO
$debug = [
    'page' => 'student/dashboard.php',
    'timestamp' => date('Y-m-d H:i:s'),
    'session_data' => $_SESSION ?? [],
    'checks' => []
];

// Check if user is logged in
requireLogin();
$debug['checks']['requireLogin'] = 'PASSED ✓';

// Initialize debug array for session
if (!isset($_SESSION['user_id'])) {
    $debug['checks']['user_logged_in'] = 'FAILED ✗ - No user_id in session';
} else {
    $debug['checks']['user_logged_in'] = 'PASSED ✓ - User ID: ' . $_SESSION['user_id'];
}

// Verify student role
if ($_SESSION['role'] != 'student') {
    $debug['checks']['role_verification'] = 'FAILED ✗ - Role is: ' . ($_SESSION['role'] ?? 'NOT SET');
    $debug['error'] = 'Access Denied - User role is not student';
    // Show debug output
    echo '
    <html>
    <head>
        <title>Access Denied - Student Dashboard</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                margin: 20px;
            }
            .debug-box {
                background: #ffebee;
                border: 3px solid #f44336;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                max-width: 800px;
            }
            .debug-box h2 {
                color: #d32f2f;
                margin-top: 0;
            }
            pre {
                background: white;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="debug-box">
            <h2>❌ Access Denied - Role Check Failed</h2>
            <p>You do not have permission to access the student dashboard.</p>
            <h3>🔧 Debug Information:</h3>
            <pre>' . htmlspecialchars(json_encode($debug, JSON_PRETTY_PRINT)) . '</pre>
            <p><a href="../index.php">← Return to Home</a></p>
        </div>
    </body>
    </html>
    ';
    exit();
}
$debug['checks']['role_verification'] = 'PASSED ✓ - Role: ' . $_SESSION['role'];

$user_id = $_SESSION['user_id'];
$debug['user_id'] = $user_id;

// Get student information using prepared statement
$student_stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name, u.username, u.email
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.user_id = ?");

if (!$student_stmt) {
    $debug['checks']['student_query_prepare'] = 'FAILED ✗ - ' . $conn->error;
    $debug['error'] = 'Failed to prepare student query';
    echo '
    <html>
    <head>
        <title>Database Error - Student Dashboard</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                margin: 20px;
            }
            .debug-box {
                background: #ffebee;
                border: 3px solid #f44336;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                max-width: 800px;
            }
            .debug-box h2 {
                color: #d32f2f;
                margin-top: 0;
            }
            pre {
                background: white;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="debug-box">
            <h2>❌ Database Error</h2>
            <p>Failed to prepare database query.</p>
            <h3>🔧 Debug Information:</h3>
            <pre>' . htmlspecialchars(json_encode($debug, JSON_PRETTY_PRINT)) . '</pre>
            <p><a href="../index.php">← Return to Home</a></p>
        </div>
    </body>
    </html>
    ';
    exit();
}
$debug['checks']['student_query_prepare'] = 'PASSED ✓';

$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result ? $student_result->fetch_assoc() : null;
$student_stmt->close();

if (!$student) {
    $debug['checks']['student_data_found'] = 'FAILED ✗ - No student record found for user ID: ' . $user_id;
    $debug['error'] = 'Student record not found in database';
    echo '
    <html>
    <head>
        <title>Student Record Not Found - Student Dashboard</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                margin: 20px;
            }
            .debug-box {
                background: #fff3e0;
                border: 3px solid #ff9800;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                max-width: 800px;
            }
            .debug-box h2 {
                color: #e65100;
                margin-top: 0;
            }
            pre {
                background: white;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="debug-box">
            <h2>⚠️ Student Record Not Found</h2>
            <p>Your user account exists, but no student record is associated with it.</p>
            <p>Please contact the administrator to create your student profile.</p>
            <h3>🔧 Debug Information:</h3>
            <pre>' . htmlspecialchars(json_encode($debug, JSON_PRETTY_PRINT)) . '</pre>
            <p><a href="../index.php">← Return to Home</a></p>
        </div>
    </body>
    </html>
    ';
    exit();
}
$debug['checks']['student_data_found'] = 'PASSED ✓ - Student ID: ' . $student['id'] . ', Name: ' . $student['first_name'] . ' ' . $student['last_name'];

// Get selected sports using prepared statement
$student_id = $student['id'];
$sports_stmt = $conn->prepare("SELECT s.* FROM sports s 
                 JOIN student_sports ss ON s.id = ss.sport_id 
                 WHERE ss.student_id = ?");
$sports = [];
if ($sports_stmt) {
    $sports_stmt->bind_param("i", $student_id);
    $sports_stmt->execute();
    $sports_result = $sports_stmt->get_result();
    if ($sports_result) {
        while ($row = $sports_result->fetch_assoc()) {
            $sports[] = $row['sport_name'];
        }
    }
    $sports_stmt->close();
}

// Get selected activities using prepared statement
$activities_stmt = $conn->prepare("SELECT a.* FROM activities a 
                     JOIN student_activities sa ON a.id = sa.activity_id 
                     WHERE sa.student_id = ?");
$activities = [];
if ($activities_stmt) {
    $activities_stmt->bind_param("i", $student_id);
    $activities_stmt->execute();
    $activities_result = $activities_stmt->get_result();
    if ($activities_result) {
        while ($row = $activities_result->fetch_assoc()) {
            $activities[] = $row['activity_name'];
        }
    }
    $activities_stmt->close();
}

// Count messages using prepared statement
$msg_count = 0;
$msg_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_id = ?");
if ($msg_stmt) {
    $msg_stmt->bind_param("i", $user_id);
    $msg_stmt->execute();
    $msg_result = $msg_stmt->get_result();
    if ($msg_result) {
        $msg_data = $msg_result->fetch_assoc();
        $msg_count = $msg_data['cnt'];
    }
    $msg_stmt->close();
}

// Get recent messages using prepared statement
$recent_messages = [];
$recent_msg_stmt = $conn->prepare("SELECT m.*, u.username as sender_name 
                     FROM messages m 
                     LEFT JOIN users u ON m.sender_id = u.id 
                     WHERE m.receiver_id = ? 
                     ORDER BY m.created_at DESC LIMIT 5");
if ($recent_msg_stmt) {
    $recent_msg_stmt->bind_param("i", $user_id);
    $recent_msg_stmt->execute();
    $recent_msg_result = $recent_msg_stmt->get_result();
    if ($recent_msg_result) {
        while ($row = $recent_msg_result->fetch_assoc()) {
            $recent_messages[] = $row;
        }
    }
    $recent_msg_stmt->close();
}

// Get upcoming events using prepared statement
$upcoming_events = [];
$events_stmt = $conn->prepare("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3");
if ($events_stmt) {
    $events_stmt->execute();
    $events_result = $events_stmt->get_result();
    if ($events_result) {
        while ($row = $events_result->fetch_assoc()) {
            $upcoming_events[] = $row;
        }
    }
    $events_stmt->close();
}

// Get attendance summary using prepared statement
$attendance = ['total_days' => 0, 'present_days' => 0];
$attendance_stmt = $conn->prepare("SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
                     FROM attendance 
                     WHERE student_id = ?");
if ($attendance_stmt) {
    $attendance_stmt->bind_param("i", $student_id);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    if ($attendance_result) {
        $attendance = $attendance_result->fetch_assoc();
    }
    $attendance_stmt->close();
}
$attendance_percentage = $attendance['total_days'] > 0 ? round(($attendance['present_days'] / $attendance['total_days']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKSMSHOME - Student Dashboard</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE5D9 100%);
            min-height: 100vh;
        }

        /* Orange Theme Variables */
        :root {
            --primary-orange: #FF4500;
            --secondary-orange: #FF8C00;
            --light-orange: #FFE5D9;
            --dark-orange: #FF3300;
            --orange-gradient: linear-gradient(135deg, #FF8C00 0%, #FF4500 100%);
            --orange-shadow: 0 10px 30px rgba(255, 69, 0, 0.2);
            --orange-hover: #FF5722;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-shape {
            position: absolute;
            background: rgba(255, 140, 0, 0.05);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        .bg-shape:nth-child(1) {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
        }

        .bg-shape:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        .bg-shape:nth-child(3) {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 10s;
        }

        .bg-shape:nth-child(4) {
            width: 200px;
            height: 200px;
            top: 20%;
            right: 15%;
            background: rgba(255, 99, 71, 0.05);
            animation: floatReverse 15s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(100px, 100px) rotate(90deg); }
            50% { transform: translate(0, 200px) rotate(180deg); }
            75% { transform: translate(-100px, 100px) rotate(270deg); }
        }

        @keyframes floatReverse {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-100px, -100px) rotate(-90deg); }
            50% { transform: translate(0, -200px) rotate(-180deg); }
            75% { transform: translate(100px, -100px) rotate(-270deg); }
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8F3 100%);
            box-shadow: 5px 0 30px rgba(255, 69, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            border-right: 3px solid var(--primary-orange);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            background: var(--orange-gradient);
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes pulse {
            0% { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
            50% { box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); }
            100% { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
        }

        .sidebar-brand {
            color: white;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .sidebar-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 12px;
            margin-top: 5px;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
        }

        .sidebar-menu li {
            margin: 5px 15px;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .sidebar-menu li:nth-child(1) { animation-delay: 0.1s; }
        .sidebar-menu li:nth-child(2) { animation-delay: 0.2s; }
        .sidebar-menu li:nth-child(3) { animation-delay: 0.3s; }
        .sidebar-menu li:nth-child(4) { animation-delay: 0.4s; }
        .sidebar-menu li:nth-child(5) { animation-delay: 0.5s; }
        .sidebar-menu li:nth-child(6) { animation-delay: 0.6s; }
        .sidebar-menu li:nth-child(7) { animation-delay: 0.7s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 69, 0, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .sidebar-menu a:hover::before {
            width: 300px;
            height: 300px;
        }

        .sidebar-menu a i {
            width: 24px;
            font-size: 18px;
            color: var(--primary-orange);
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: var(--light-orange);
            color: var(--primary-orange);
            transform: translateX(5px);
            box-shadow: var(--orange-shadow);
        }

        .sidebar-menu a.active {
            background: var(--orange-gradient);
            color: white;
            box-shadow: var(--orange-shadow);
        }

        .sidebar-menu a.active i {
            color: white;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 69, 0, 0.1);
            margin-top: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            position: relative;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Topbar */
        .topbar {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: var(--orange-shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 69, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .topbar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--orange-gradient);
            animation: borderFlow 3s linear infinite;
            background-size: 200% 100%;
        }

        @keyframes borderFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .topbar h1 {
            font-size: 28px;
            background: var(--orange-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-badge {
            background: var(--light-orange);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
            color: var(--primary-orange);
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 8px 20px;
            background: var(--light-orange);
            border-radius: 50px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .user-profile::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .user-profile:hover::before {
            left: 100%;
        }

        .user-profile:hover {
            border-color: var(--primary-orange);
            transform: translateY(-2px);
            box-shadow: var(--orange-shadow);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--orange-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            animation: glow 2s infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 69, 0, 0.5); }
            50% { box-shadow: 0 0 20px rgba(255, 69, 0, 0.8); }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--orange-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 69, 0, 0.1);
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(255, 69, 0, 0.3);
            border-color: var(--primary-orange);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 69, 0, 0.05) 0%, transparent 70%);
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: var(--light-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-icon i {
            font-size: 24px;
            color: var(--primary-orange);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            background: var(--orange-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #999;
            font-size: 12px;
        }

        /* Section Title */
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-orange);
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-orange);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 24px;
        }

        /* Profile Section */
        .profile-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--orange-shadow);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 69, 0, 0.1);
        }

        .profile-header {
            display: flex;
            gap: 30px;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--light-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            overflow: hidden;
            border: 4px solid var(--primary-orange);
            box-shadow: var(--orange-shadow);
            position: relative;
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-pic .edit-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 69, 0, 0.8);
            color: white;
            text-align: center;
            padding: 5px;
            font-size: 12px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .profile-pic:hover .edit-overlay {
            transform: translateY(0);
        }

        .profile-info h2 {
            font-size: 28px;
            color: var(--primary-orange);
            margin-bottom: 10px;
        }

        .profile-info .badge {
            display: inline-block;
            padding: 5px 15px;
            background: var(--light-orange);
            color: var(--primary-orange);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-info p {
            color: #666;
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info p i {
            width: 20px;
            color: var(--primary-orange);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: var(--light-orange);
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid var(--primary-orange);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateX(5px);
            box-shadow: var(--orange-shadow);
        }

        .info-item strong {
            display: block;
            color: var(--primary-orange);
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-item span {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        /* Tags */
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }

        .tag {
            background: var(--orange-gradient);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--orange-shadow);
            transition: all 0.3s ease;
        }

        .tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(255, 69, 0, 0.4);
        }

        .tag i {
            font-size: 14px;
        }

        /* Video Status */
        .video-status {
            background: var(--light-orange);
            border-left: 4px solid var(--primary-orange);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .video-status.uploaded {
            background: #f0fff4;
            border-left-color: #22c55e;
        }

        .video-status i {
            font-size: 24px;
            color: var(--primary-orange);
        }

        .video-status.uploaded i {
            color: #22c55e;
        }

        /* Messages Section */
        .messages-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--orange-shadow);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 69, 0, 0.1);
        }

        .message-item {
            padding: 20px;
            background: var(--light-orange);
            border-radius: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-orange);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .message-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--orange-gradient);
        }

        .message-item:hover {
            transform: translateX(5px);
            box-shadow: var(--orange-shadow);
        }

        .message-from {
            font-weight: 700;
            color: var(--primary-orange);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-subject {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .message-preview {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .message-time {
            color: #999;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Events Section */
        .events-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--orange-shadow);
            border: 1px solid rgba(255, 69, 0, 0.1);
        }

        .event-item {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: var(--light-orange);
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .event-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--orange-shadow);
        }

        .event-date {
            text-align: center;
            min-width: 60px;
        }

        .event-date .day {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-orange);
            line-height: 1;
        }

        .event-date .month {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .event-details h4 {
            color: var(--primary-orange);
            margin-bottom: 5px;
        }

        .event-details p {
            color: #666;
            font-size: 13px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: var(--orange-gradient);
            color: white;
            box-shadow: var(--orange-shadow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(255, 69, 0, 0.4);
        }

        .btn-secondary {
            background: var(--light-orange);
            color: var(--primary-orange);
            border: 2px solid var(--primary-orange);
        }

        .btn-secondary:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: var(--orange-shadow);
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 2px solid #dc2626;
        }

        .btn-danger:hover {
            background: #dc2626;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: rgba(255, 69, 0, 0.3);
            margin-bottom: 15px;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 20px;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--light-orange);
            border-top: 5px solid var(--primary-orange);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
    </div>

    <!-- Loading Animation -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p style="color: var(--primary-orange); font-weight: 600;">Loading RKSMSHOME Dashboard...</p>
    </div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap" style="color: #FF4500;"></i>
                </div>
                <div class="sidebar-brand">RKSMSHOME</div>
                <div class="sidebar-subtitle">Student Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> View Profile</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a></li>
                <li><a href="message_teacher.php"><i class="fas fa-paper-plane"></i> Message Teacher</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Inbox 
                    <?php if ($msg_count > 0): ?>
                        <span style="background: #FF4500; color: white; padding: 2px 8px; border-radius: 20px; font-size: 11px; margin-left: 5px;"><?php echo $msg_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--light-orange); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clock" style="color: var(--primary-orange);"></i>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #666;">System Time</div>
                        <div style="font-weight: 600; color: var(--primary-orange);"><?php echo date('h:i A'); ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <h1>
                    <i class="fas fa-tachometer-alt"></i> Student Dashboard
                    <span class="welcome-badge">
                        <i class="fas fa-smile"></i> Welcome back!
                    </span>
                </h1>
                <div class="topbar-right">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Debug Info Box -->
            <div style="background: #e8f5e9; border: 2px solid #4caf50; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                    <span style="font-size: 24px;">✅</span>
                    <strong style="color: #2e7d32; font-size: 16px;">Dashboard Loaded Successfully</strong>
                    <a href="?debug=1" style="margin-left: auto; padding: 8px 16px; background: #4caf50; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">View Debug Info</a>
                </div>
                <div style="font-size: 13px; color: #555; margin-left: 39px;">
                    <div><strong>User ID:</strong> <?php echo htmlspecialchars($debug['user_id']); ?></div>
                    <div><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></div>
                    <div><strong>Student ID:</strong> <?php echo htmlspecialchars($student['id']); ?></div>
                    <div><strong>Timestamp:</strong> <?php echo htmlspecialchars($debug['timestamp']); ?></div>
                </div>
            </div>

            <!-- Debug Details (if requested) -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div style="background: #f5f5f5; border: 2px solid #666; border-radius: 12px; padding: 20px; margin-bottom: 30px; overflow-x: auto;">
                <div style="font-weight: bold; margin-bottom: 10px; color: #333; display: flex; align-items: center; gap: 10px;">
                    <span>🔧 Full Debug Information</span>
                    <a href="?" style="margin-left: auto; text-decoration: none; color: #666; font-size: 13px;">Hide Debug</a>
                </div>
                <pre style="margin: 0; font-size: 12px; color: #333; background: white; padding: 15px; border-radius: 6px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($debug, JSON_PRETTY_PRINT)); ?></pre>
            </div>
            <?php endif; ?>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <h3>Registration No</h3>
                    <div class="stat-number"><?php echo htmlspecialchars($student['registration_number']); ?></div>
                    <div class="stat-label">Student ID</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3>Current Semester</h3>
                    <div class="stat-number"><?php echo intval($student['semester'] ?? 1); ?></div>
                    <div class="stat-label">Academic Term</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <h3>Sports</h3>
                    <div class="stat-number"><?php echo count($sports); ?></div>
                    <div class="stat-label">Selected Sports</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Activities</h3>
                    <div class="stat-number"><?php echo count($activities); ?></div>
                    <div class="stat-label">Clubs & Activities</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Messages</h3>
                    <div class="stat-number"><?php echo $msg_count; ?></div>
                    <div class="stat-label">Unread Messages</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3>Video</h3>
                    <div class="stat-number"><?php echo !empty($student['video_file']) ? '✓' : '✗'; ?></div>
                    <div class="stat-label">Profile Video</div>
                </div>
            </div>

            <!-- Profile Section -->
            <div class="profile-section">
                <h2 class="section-title">
                    <i class="fas fa-user-circle"></i> Student Profile
                </h2>
                
                <div class="profile-header">
                    <div class="profile-pic">
                        <?php if (!empty($student['profile_pic']) && file_exists('../uploads/profile_pic/' . $student['profile_pic'])): ?>
                            <img src="../uploads/profile_pic/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-graduate" style="font-size: 60px; color: var(--primary-orange);"></i>
                        <?php endif; ?>
                        <div class="edit-overlay">
                            <i class="fas fa-camera"></i> Update
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <div class="badge">
                            <i class="fas fa-graduation-cap"></i> 
                            <?php echo htmlspecialchars($student['dept_name']); ?>
                        </div>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['mobile'] ?? 'Not provided'); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($student['address'] ?? 'Address not provided'); ?></p>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <strong><i class="fas fa-calendar"></i> Date of Birth</strong>
                        <span><?php echo !empty($student['date_of_birth']) ? date('d M Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong><i class="fas fa-user-tie"></i> Father's Name</strong>
                        <span><?php echo htmlspecialchars($student['father_name'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><i class="fas fa-user-tie"></i> Mother's Name</strong>
                        <span><?php echo htmlspecialchars($student['mother_name'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><i class="fas fa-id-card"></i> Aadhar Number</strong>
                        <span><?php echo htmlspecialchars($student['aadhar_number'] ?? 'Not provided'); ?></span>
                    </div>
                </div>

                <!-- Sports & Activities -->
                <?php if (count($sports) > 0 || count($activities) > 0): ?>
                <div style="margin-top: 20px;">
                    <?php if (count($sports) > 0): ?>
                        <div style="margin-bottom: 15px;">
                            <h3 style="color: var(--primary-orange); margin-bottom: 10px; font-size: 16px;">
                                <i class="fas fa-futbol"></i> Sports Enrolled
                            </h3>
                            <div class="tags">
                                <?php foreach ($sports as $sport): ?>
                                    <span class="tag">
                                        <i class="fas fa-futbol"></i>
                                        <?php echo htmlspecialchars($sport); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (count($activities) > 0): ?>
                        <div>
                            <h3 style="color: var(--primary-orange); margin-bottom: 10px; font-size: 16px;">
                                <i class="fas fa-tasks"></i> Activities & Clubs
                            </h3>
                            <div class="tags">
                                <?php foreach ($activities as $activity): ?>
                                    <span class="tag">
                                        <i class="fas fa-star"></i>
                                        <?php echo htmlspecialchars($activity); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Video Status -->
                <?php if (!empty($student['video_file'])): ?>
                    <div class="video-status uploaded">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong style="color: #22c55e;">Promotional Video Uploaded</strong>
                            <p style="color: #666; margin-top: 5px; font-size: 13px;">Your profile video has been successfully uploaded</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="video-status">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong style="color: var(--primary-orange);">Promotional Video Pending</strong>
                            <p style="color: #666; margin-top: 5px; font-size: 13px;">Upload a video to enhance your profile</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-eye"></i> View Full Profile
                    </a>
                    <a href="edit_profile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="message_teacher.php" class="btn btn-secondary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </a>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="messages-section">
                <h2 class="section-title">
                    <i class="fas fa-envelope-open-text"></i> Recent Messages
                    <?php if ($msg_count > 0): ?>
                        <span style="background: var(--primary-orange); color: white; padding: 5px 15px; border-radius: 50px; font-size: 12px; margin-left: 10px;">
                            <?php echo $msg_count; ?> new
                        </span>
                    <?php endif; ?>
                </h2>
                
                <?php if (count($recent_messages) > 0): ?>
                    <?php foreach ($recent_messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-from">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($msg['sender_name'] ?? 'System'); ?>
                        </div>
                        <div class="message-subject">
                            <?php echo htmlspecialchars($msg['subject']); ?>
                        </div>
                        <div class="message-preview">
                            <?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : ''); ?>
                        </div>
                        <div class="message-time">
                            <i class="far fa-clock"></i>
                            <?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 20px; text-align: center;">
                        <a href="messages.php" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> View All Messages
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <p>No messages yet. Teachers will send you messages here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Events -->
            <?php if ($upcoming_events && $upcoming_events->num_rows > 0): ?>
            <div class="events-section">
                <h2 class="section-title">
                    <i class="fas fa-calendar-alt"></i> Upcoming Events
                </h2>
                
                <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                <div class="event-item">
                    <div class="event-date">
                        <div class="day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                        <div class="month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                    </div>
                    <div class="event-details">
                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue'] ?? 'TBD'); ?></p>
                        <p><?php echo htmlspecialchars($event['description']); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Show loading animation on page load
        document.getElementById('loading').classList.add('active');
        setTimeout(() => {
            document.getElementById('loading').classList.remove('active');
        }, 1000);

        // Mobile menu toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // Add active class to current menu item
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });

        // Ripple effect on buttons
        document.querySelectorAll('.btn, .sidebar-menu a').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.width = '20px';
                ripple.style.height = '20px';
                ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                ripple.style.borderRadius = '50%';
                ripple.style.pointerEvents = 'none';
                ripple.style.left = e.offsetX + 'px';
                ripple.style.top = e.offsetY + 'px';
                ripple.style.transform = 'translate(-50%, -50%) scale(0)';
                ripple.style.animation = 'ripple 0.6s ease-out';
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: translate(-50%, -50%) scale(20);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Auto-refresh messages every 30 seconds
        setInterval(() => {
            // Could implement AJAX refresh here
            console.log('Refreshing messages...');
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + D for dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = 'dashboard.php';
            }
            // Alt + P for profile
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'profile.php';
            }
            // Alt + E for edit profile
            if (e.altKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = 'edit_profile.php';
            }
            // Alt + M for messages
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                window.location.href = 'messages.php';
            }
        });

        // Mouse move parallax effect for background
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.bg-shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 20;
                const x = (mouseX * speed) - (speed / 2);
                const y = (mouseY * speed) - (speed / 2);
                shape.style.transform = `translate(${x}px, ${y}px)`;
            });
        });

        // Notification sound for new messages
        <?php if ($msg_count > 0): ?>
        // Optional: Play notification sound
        // const audio = new Audio('notification.mp3');
        // audio.play();
        <?php endif; ?>

        // Tooltips for icons
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.position = 'absolute';
                tooltip.style.background = '#FF4500';
                tooltip.style.color = 'white';
                tooltip.style.padding = '5px 10px';
                tooltip.style.borderRadius = '5px';
                tooltip.style.fontSize = '12px';
                tooltip.style.zIndex = '1000';
                tooltip.style.pointerEvents = 'none';
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.top - 30) + 'px';
                
                document.body.appendChild(tooltip);
                
                this.addEventListener('mouseleave', function() {
                    tooltip.remove();
                });
            });
        });

        // Profile picture hover effect
        document.querySelector('.profile-pic')?.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        document.querySelector('.profile-pic')?.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    </script>
</body>
</html>