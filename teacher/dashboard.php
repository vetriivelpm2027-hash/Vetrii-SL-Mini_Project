<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is teacher using prepared statement
$user_stmt = $conn->prepare("SELECT u.*, t.department_id, t.qualification, t.experience_years, d.full_name as dept_name 
                            FROM users u 
                            LEFT JOIN teachers t ON u.id = t.user_id
                            LEFT JOIN departments d ON t.department_id = d.id
                            WHERE u.id = ?");
if (!$user_stmt) {
    die("Prepare failed (user query): " . $conn->error);
}
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user || $user['role'] != 'class_teacher') {
    header("Location: ../unauthorized.php");
    exit();
}

// Get messages with student info
$messages_stmt = $conn->prepare("SELECT m.*, u.username as sender_name, s.first_name, s.last_name, s.profile_pic 
                                FROM messages m 
                                LEFT JOIN users u ON m.sender_id = u.id 
                                LEFT JOIN students s ON u.id = s.user_id
                                WHERE m.receiver_id = ? 
                                ORDER BY m.created_at DESC 
                                LIMIT 10");
if (!$messages_stmt) {
    error_log("Prepare failed (messages query): " . $conn->error);
    $messages = null;
} else {
    $messages_stmt->bind_param("i", $_SESSION['user_id']);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();
    $messages = $messages_result;
    $messages_stmt->close();
}

// Get unread count
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $_SESSION['user_id']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread'];
$unread_stmt->close();

// Get students count for this teacher's department
$dept_id = $user['department_id'] ?? 0;
$students_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE department_id = ?");
$students_stmt->bind_param("i", $dept_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students_data = $students_result->fetch_assoc();
$students_count = $students_data['count'] ?? 0;
$students_stmt->close();

// Get today's attendance stats
$today = date('Y-m-d');
$attendance_stmt = $conn->prepare("SELECT 
                                   COUNT(*) as total,
                                   SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                                   SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                                   SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                                   FROM attendance 
                                   WHERE attendance_date = ?");
$attendance_stmt->bind_param("s", $today);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance = $attendance_result->fetch_assoc();
$attendance_stmt->close();

// Get recent students
$recent_stmt = $conn->prepare("SELECT s.*, u.username 
                              FROM students s 
                              JOIN users u ON s.user_id = u.id 
                              WHERE s.department_id = ? 
                              ORDER BY s.created_at DESC 
                              LIMIT 5");
$recent_stmt->bind_param("i", $dept_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_students = $recent_result;
$recent_stmt->close();

// Get pending replies
$pending_stmt = $conn->prepare("SELECT COUNT(*) as pending FROM messages WHERE receiver_id = ? AND is_read = 0");
$pending_stmt->bind_param("i", $_SESSION['user_id']);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_data = $pending_result->fetch_assoc();
$pending_count = $pending_data['pending'];
$pending_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - RKMS HOME</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary: #FF8C42;
            --primary-dark: #FF6B35;
            --primary-light: #FFB27A;
            --primary-soft: #FFE5D9;
            --secondary: #2C3E50;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --shadow-sm: 0 2px 4px rgba(255, 140, 66, 0.1);
            --shadow-md: 0 4px 6px rgba(255, 140, 66, 0.1);
            --shadow-lg: 0 10px 15px rgba(255, 140, 66, 0.1);
            --shadow-xl: 0 20px 25px rgba(255, 140, 66, 0.15);
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
            overflow-x: hidden;
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

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 140, 66, 0.03);
            animation: float 20s infinite;
        }

        .bg-circle:nth-child(1) {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
        }

        .bg-circle:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        .bg-circle:nth-child(3) {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 10s;
        }

        .bg-circle:nth-child(4) {
            width: 200px;
            height: 200px;
            top: 20%;
            right: 15%;
            animation-delay: 15s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(50px, 50px) rotate(120deg); }
            66% { transform: translate(-50px, -50px) rotate(240deg); }
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background: white;
            box-shadow: var(--shadow-xl);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            border-right: 3px solid var(--primary);
            transition: all 0.3s ease;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .sidebar-header {
            padding: 30px 20px;
            background: var(--gradient);
            text-align: center;
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
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
            color: var(--primary);
            transform: rotate(-5deg);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            box-shadow: var(--shadow-lg);
        }

        .sidebar-logo:hover {
            transform: rotate(0deg) scale(1.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .sidebar-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 13px;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
        }

        .sidebar-menu li {
            margin: 5px 15px;
            animation: slideInRight 0.5s ease forwards;
            opacity: 0;
        }

        .sidebar-menu li:nth-child(1) { animation-delay: 0.1s; }
        .sidebar-menu li:nth-child(2) { animation-delay: 0.2s; }
        .sidebar-menu li:nth-child(3) { animation-delay: 0.3s; }
        .sidebar-menu li:nth-child(4) { animation-delay: 0.4s; }
        .sidebar-menu li:nth-child(5) { animation-delay: 0.5s; }
        .sidebar-menu li:nth-child(6) { animation-delay: 0.6s; }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--gray);
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
            background: rgba(255, 140, 66, 0.1);
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
            color: var(--primary);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu a:hover {
            background: var(--primary-soft);
            color: var(--primary);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: var(--gradient);
            color: white;
            box-shadow: var(--shadow-lg);
        }

        .sidebar-menu a.active i {
            color: white;
        }

        .sidebar-menu .badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--primary-soft);
            margin-top: 20px;
        }

        .teacher-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .teacher-info i {
            color: var(--primary);
            font-size: 20px;
        }

        .teacher-info-details h4 {
            color: var(--dark);
            font-size: 14px;
        }

        .teacher-info-details p {
            color: var(--gray);
            font-size: 11px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 300px;
            padding: 30px;
            transition: all 0.3s ease;
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
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            animation: slideInDown 0.5s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary);
            cursor: pointer;
            display: none;
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
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-badge {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            background: var(--primary-soft);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #fff5e6, #fff);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
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
            background: radial-gradient(circle, rgba(255, 140, 66, 0.05) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .welcome-icon {
            width: 100px;
            height: 100px;
            background: var(--gradient);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
            box-shadow: var(--shadow-xl);
            transform: rotate(-5deg);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .welcome-icon:hover {
            transform: rotate(0deg) scale(1.05);
        }

        .welcome-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .welcome-content h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .welcome-content p {
            color: var(--gray);
            font-size: 16px;
        }

        .welcome-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .welcome-stat {
            text-align: center;
        }

        .welcome-stat .number {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            display: block;
        }

        .welcome-stat .label {
            font-size: 12px;
            color: var(--gray);
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
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

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

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-soft);
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
        }

        .trend-up {
            color: var(--success);
        }

        .trend-down {
            color: var(--danger);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-xl);
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to right, rgba(255, 140, 66, 0.02), transparent);
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h3 i {
            color: var(--primary);
        }

        .card-header .badge {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .card-header .action-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .card-header .action-link:hover {
            transform: translateX(5px);
        }

        .card-body {
            padding: 25px;
        }

        /* Messages List */
        .messages-list {
            list-style: none;
        }

        .message-item {
            padding: 15px;
            background: var(--primary-soft);
            border-radius: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .message-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }

        .message-item.unread {
            border-left-color: var(--primary);
            background: rgba(255, 140, 66, 0.05);
        }

        .message-sender {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .sender-avatar {
            width: 35px;
            height: 35px;
            background: var(--gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .sender-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
        }

        .message-subject {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 15px;
        }

        .message-preview {
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .message-time {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gray);
            font-size: 11px;
        }

        .message-time i {
            font-size: 11px;
        }

        /* Students Grid */
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .student-card {
            background: var(--primary-soft);
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .student-avatar {
            width: 45px;
            height: 45px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .student-info {
            flex: 1;
        }

        .student-info h4 {
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .student-info p {
            color: var(--gray);
            font-size: 11px;
        }

        /* Attendance Progress */
        .attendance-progress {
            margin-top: 20px;
        }

        .progress-item {
            margin-bottom: 15px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .progress-header span {
            color: var(--gray);
            font-size: 13px;
        }

        .progress-bar {
            height: 10px;
            background: var(--primary-soft);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient);
            border-radius: 10px;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--primary-soft);
            border-radius: 15px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .action-btn:hover {
            background: var(--gradient);
            border-color: transparent;
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .action-btn:hover i,
        .action-btn:hover span {
            color: white;
        }

        .action-btn i {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .action-btn span {
            display: block;
            font-weight: 600;
            color: var(--dark);
            font-size: 13px;
            transition: color 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--primary-soft);
            margin-bottom: 15px;
        }

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive */
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
                padding: 20px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .top-bar {
                flex-wrap: wrap;
            }
            
            .page-title {
                width: 100%;
                justify-content: space-between;
            }
            
            .welcome-section {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-stats {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .students-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }

        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 1000;
        }

        /* Loading Indicator */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 20px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--primary-soft);
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #f0fff4;
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert i {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <p style="color: var(--primary); font-weight: 600;">Loading dashboard...</p>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Teacher Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages 
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="reply_message.php"><i class="fas fa-reply"></i> Reply Message</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="teacher-info">
                    <i class="fas fa-graduation-cap"></i>
                    <div class="teacher-info-details">
                        <h4><?php echo htmlspecialchars($user['dept_name'] ?? 'Department'); ?></h4>
                        <p><?php echo htmlspecialchars($user['qualification'] ?? 'Class Teacher'); ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="page-title">
                    <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
                    <span class="date-badge">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('l, d M Y'); ?>
                    </span>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-icon">
                    <i class="fas fa-smile"></i>
                </div>
                <div class="welcome-content">
                    <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?>! 👋</h2>
                    <p>Here's what's happening in your class today</p>
                    <div class="welcome-stats">
                        <div class="welcome-stat">
                            <span class="number"><?php echo $students_count; ?></span>
                            <span class="label">Total Students</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?php echo $unread_count; ?></span>
                            <span class="label">Unread Messages</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?php echo $attendance['present'] ?? 0; ?></span>
                            <span class="label">Present Today</span>
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
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> +2 this month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-label">Messages</div>
                    <div class="stat-value"><?php echo $messages->num_rows; ?></div>
                    <div class="stat-trend <?php echo $unread_count > 0 ? 'trend-down' : ''; ?>">
                        <i class="fas fa-<?php echo $unread_count > 0 ? 'exclamation-circle' : 'check-circle'; ?>"></i> 
                        <?php echo $unread_count; ?> unread
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-label">Today's Attendance</div>
                    <div class="stat-value"><?php echo $attendance['present'] ?? 0; ?>/<?php echo $students_count; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-clock"></i> 
                        <?php echo $attendance['late'] ?? 0; ?> late
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-label">Pending Replies</div>
                    <div class="stat-value"><?php echo $pending_count; ?></div>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-exclamation-triangle"></i> Action needed
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Messages -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope-open-text"></i> Recent Messages</h3>
                        <a href="messages.php" class="action-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($messages->num_rows > 0): ?>
                            <div class="messages-list">
                                <?php while ($msg = $messages->fetch_assoc()): ?>
                                    <div class="message-item <?php echo !($msg['is_read'] ?? 0) ? 'unread' : ''; ?>" 
                                         onclick="window.location.href='reply_message.php?id=<?php echo $msg['id']; ?>'">
                                        <div class="message-sender">
                                            <div class="sender-avatar">
                                                <?php 
                                                $initials = strtoupper(substr($msg['first_name'] ?? $msg['sender_name'] ?? 'S', 0, 1) . substr($msg['last_name'] ?? '', 0, 1));
                                                echo $initials ?: 'ST';
                                                ?>
                                            </div>
                                            <div class="sender-name">
                                                <?php echo htmlspecialchars($msg['sender_name'] ?? 'Student'); ?>
                                            </div>
                                        </div>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($msg['message'], 0, 60)) . '...'; ?>
                                        </div>
                                        <div class="message-time">
                                            <i class="far fa-clock"></i>
                                            <?php echo timeAgo($msg['created_at']); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-envelope-open"></i>
                                <p>No messages yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Students -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-graduate"></i> Recent Students</h3>
                        <a href="students.php" class="action-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_students->num_rows > 0): ?>
                            <div class="students-grid">
                                <?php while ($student = $recent_students->fetch_assoc()): ?>
                                    <div class="student-card">
                                        <div class="student-avatar">
                                            <?php 
                                            $initials = strtoupper(substr($student['first_name'] ?? 'S', 0, 1) . substr($student['last_name'] ?? 'T', 0, 1));
                                            echo $initials ?: 'ST';
                                            ?>
                                        </div>
                                        <div class="student-info">
                                            <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($student['registration_number']); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>No students found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Attendance Progress -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Today's Attendance Overview</h3>
                    <span class="badge"><?php echo date('d M Y'); ?></span>
                </div>
                <div class="card-body">
                    <div class="attendance-progress">
                        <div class="progress-item">
                            <div class="progress-header">
                                <span>Present</span>
                                <span><?php echo $attendance['present'] ?? 0; ?>/<?php echo $students_count; ?></span>
                            </div>
                            <div class="progress-bar">
                                <?php $present_percent = $students_count > 0 ? round((($attendance['present'] ?? 0) / $students_count) * 100) : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $present_percent; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span>Absent</span>
                                <span><?php echo $attendance['absent'] ?? 0; ?>/<?php echo $students_count; ?></span>
                            </div>
                            <div class="progress-bar">
                                <?php $absent_percent = $students_count > 0 ? round((($attendance['absent'] ?? 0) / $students_count) * 100) : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $absent_percent; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span>Late</span>
                                <span><?php echo $attendance['late'] ?? 0; ?>/<?php echo $students_count; ?></span>
                            </div>
                            <div class="progress-bar">
                                <?php $late_percent = $students_count > 0 ? round((($attendance['late'] ?? 0) / $students_count) * 100) : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $late_percent; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="attendance.php" class="action-btn">
                            <i class="fas fa-calendar-check"></i>
                            <span>Mark Attendance</span>
                        </a>
                        <a href="students.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Student</span>
                        </a>
                        <a href="reply_message.php" class="action-btn">
                            <i class="fas fa-reply"></i>
                            <span>Reply Messages</span>
                        </a>
                        <a href="messages.php" class="action-btn">
                            <i class="fas fa-envelope"></i>
                            <span>View Inbox</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Show loading overlay on page load
        document.getElementById('loadingOverlay').classList.add('active');
        setTimeout(() => {
            document.getElementById('loadingOverlay').classList.remove('active');
        }, 1000);

        // Auto-refresh data every 30 seconds
        setInterval(() => {
            // Could implement AJAX refresh here
            console.log('Refreshing dashboard data...');
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + S for students
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                window.location.href = 'students.php';
            }
            // Alt + M for messages
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                window.location.href = 'messages.php';
            }
            // Alt + A for attendance
            if (e.altKey && e.key === 'a') {
                e.preventDefault();
                window.location.href = 'attendance.php';
            }
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Ripple effect for buttons
        document.querySelectorAll('.action-btn, .stat-card, .message-item').forEach(element => {
            element.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.width = '20px';
                ripple.style.height = '20px';
                ripple.style.background = 'rgba(255, 140, 66, 0.2)';
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

        // Show welcome toast
        function showToast(message) {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.background = 'var(--gradient)';
            toast.style.color = 'white';
            toast.style.padding = '12px 25px';
            toast.style.borderRadius = '10px';
            toast.style.boxShadow = 'var(--shadow-lg)';
            toast.style.zIndex = '9999';
            toast.style.animation = 'slideInRight 0.3s ease';
            toast.innerHTML = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Add slideOut animation
        const slideOutStyle = document.createElement('style');
        slideOutStyle.textContent = `
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(slideOutStyle);

        // Time ago function (PHP fallback for JS)
        function timeAgo(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + ' years ago';
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + ' months ago';
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + ' days ago';
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + ' hours ago';
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + ' minutes ago';
            
            return Math.floor(seconds) + ' seconds ago';
        }
    </script>
</body>
</html>