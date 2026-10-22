<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is teacher using prepared statement
$user_stmt = $conn->prepare("SELECT u.*, t.department_id, t.qualification, d.full_name as dept_name 
                            FROM users u 
                            LEFT JOIN teachers t ON u.id = t.user_id
                            LEFT JOIN departments d ON t.department_id = d.id
                            WHERE u.id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user || $user['role'] != 'class_teacher') {
    header("Location: ../unauthorized.php");
    exit();
}

$success = '';
$error = '';

// Get student ID from URL if replying to specific student
$reply_to_id = isset($_GET['to']) ? intval($_GET['to']) : 0;
$reply_to_name = isset($_GET['name']) ? $_GET['name'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    
    if (empty($subject) || empty($message_text)) {
        $error = "❌ Subject and message are required!";
    } else {
        // Use prepared statement
        $insert_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiss", $_SESSION['user_id'], $receiver_id, $subject, $message_text);
        
        if ($insert_stmt->execute()) {
            $success = "✅ Message sent successfully to student!";
        } else {
            $error = "❌ Error sending message: " . $conn->error;
        }
        $insert_stmt->close();
    }
}

// Get all students for dropdown using prepared statement
$students_stmt = $conn->prepare("SELECT u.id, u.username, s.first_name, s.last_name, s.registration_number, s.profile_pic 
                                FROM users u 
                                JOIN students s ON u.id = s.user_id 
                                WHERE s.department_id = ? OR ? IS NULL
                                ORDER BY s.semester, s.first_name");
$dept_id = $user['department_id'] ?? null;
$students_stmt->bind_param("ii", $dept_id, $dept_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result;
$students_stmt->close();

// Get recent conversations
$recent_stmt = $conn->prepare("SELECT DISTINCT u.id, u.username, s.first_name, s.last_name, s.registration_number,
                               MAX(m.created_at) as last_message
                               FROM messages m
                               JOIN users u ON m.sender_id = u.id
                               LEFT JOIN students s ON u.id = s.user_id
                               WHERE m.receiver_id = ? OR m.sender_id = ?
                               GROUP BY u.id
                               ORDER BY last_message DESC
                               LIMIT 5");
$recent_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_conversations = $recent_result;
$recent_stmt->close();

// Get unread count
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $_SESSION['user_id']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread'];
$unread_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply to Students - RKMS HOME Teacher Portal</title>
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

        .unread-badge {
            background: var(--danger);
            color: white;
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

        /* Alerts */
        .alert-container {
            margin-bottom: 25px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            margin-bottom: 15px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #f0fff4;
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: #fff5f5;
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        .alert i {
            font-size: 20px;
        }

        .alert .close-btn {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }

        .alert .close-btn:hover {
            opacity: 1;
        }

        /* Recent Conversations */
        .recent-conversations {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 16px;
            font-weight: 600;
        }

        .section-title i {
            color: var(--primary);
        }

        .conversation-chips {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .conversation-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            background: var(--primary-soft);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            flex: 1;
            min-width: 200px;
        }

        .conversation-chip:hover {
            background: var(--gradient);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .conversation-chip:hover .chip-info h4,
        .conversation-chip:hover .chip-info p {
            color: white;
        }

        .chip-avatar {
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

        .chip-info {
            flex: 1;
        }

        .chip-info h4 {
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .chip-info p {
            color: var(--gray);
            font-size: 11px;
        }

        /* Message Card */
        .message-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
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

        .student-count {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23FF8C42' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 16px;
            padding-right: 50px;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        /* Character Counter */
        .char-counter {
            text-align: right;
            margin-top: 8px;
            color: var(--gray);
            font-size: 12px;
        }

        .char-counter.warning {
            color: var(--warning);
        }

        .char-counter.danger {
            color: var(--danger);
        }

        /* Quick Templates */
        .templates-section {
            margin-bottom: 20px;
        }

        .templates-label {
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 10px;
        }

        .templates-grid {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .template-btn {
            padding: 8px 15px;
            background: var(--primary-soft);
            border: 1px solid var(--primary);
            border-radius: 30px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .template-btn:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Buttons */
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 15px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
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
            background: var(--gradient);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-secondary {
            background: var(--primary-soft);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-3px);
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #fff5e6, #fff);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-box i {
            font-size: 24px;
            color: var(--primary);
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-content strong {
            color: var(--primary);
            font-size: 15px;
        }

        .info-box-content p {
            color: var(--gray);
            font-size: 13px;
            margin-top: 5px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: var(--primary-soft);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--dark);
            font-size: 16px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            font-size: 13px;
        }

        /* Loading Overlay */
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

        /* Responsive */
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
            
            .conversation-chips {
                flex-direction: column;
            }
            
            .conversation-chip {
                width: 100%;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .templates-grid {
                justify-content: center;
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

        /* Animations */
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
        <p style="color: var(--primary); font-weight: 600;">Sending message...</p>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Teacher Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages 
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="reply_message.php" class="active"><i class="fas fa-reply"></i> Reply Message</a></li>
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
                    <h1><i class="fas fa-reply"></i> Reply to Students</h1>
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge">
                            <i class="fas fa-circle"></i> <?php echo $unread_count; ?> Unread
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
            </div>

            <!-- Alert Container -->
            <div class="alert-container" id="alertContainer">
                <?php if ($success): ?>
                    <div class="alert alert-success" id="successAlert">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                        <i class="fas fa-times close-btn" onclick="this.parentElement.remove()"></i>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" id="errorAlert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                        <i class="fas fa-times close-btn" onclick="this.parentElement.remove()"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Conversations -->
            <?php if ($recent_conversations->num_rows > 0): ?>
            <div class="recent-conversations">
                <div class="section-title">
                    <i class="fas fa-history"></i>
                    <span>Recent Conversations</span>
                </div>
                <div class="conversation-chips">
                    <?php while ($conv = $recent_conversations->fetch_assoc()): ?>
                        <?php 
                        $full_name = htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']);
                        $initials = strtoupper(substr($conv['first_name'] ?? 'S', 0, 1) . substr($conv['last_name'] ?? 'T', 0, 1));
                        ?>
                        <div class="conversation-chip" onclick="selectStudent(<?php echo $conv['id']; ?>, '<?php echo $full_name; ?>')">
                            <div class="chip-avatar">
                                <?php echo $initials ?: 'ST'; ?>
                            </div>
                            <div class="chip-info">
                                <h4><?php echo $full_name; ?></h4>
                                <p><?php echo htmlspecialchars($conv['registration_number'] ?? 'Student'); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Message Card -->
            <div class="message-card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> Compose Message</h3>
                    <span class="student-count">
                        <i class="fas fa-users"></i> <?php echo $students->num_rows; ?> Students
                    </span>
                </div>
                <div class="card-body">
                    <!-- Info Box -->
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <div class="info-box-content">
                            <strong>Send a message to your students</strong>
                            <p>Messages will be delivered to the student's inbox. They can reply directly to you.</p>
                        </div>
                    </div>

                    <!-- Quick Templates -->
                    <div class="templates-section">
                        <div class="templates-label">Quick Templates:</div>
                        <div class="templates-grid">
                            <button class="template-btn" onclick="useTemplate('homework')">
                                <i class="fas fa-book"></i> Homework Reminder
                            </button>
                            <button class="template-btn" onclick="useTemplate('meeting')">
                                <i class="fas fa-users"></i> Parent Meeting
                            </button>
                            <button class="template-btn" onclick="useTemplate('announcement')">
                                <i class="fas fa-bullhorn"></i> Class Announcement
                            </button>
                            <button class="template-btn" onclick="useTemplate('reminder')">
                                <i class="fas fa-bell"></i> General Reminder
                            </button>
                        </div>
                    </div>

                    <!-- Message Form -->
                    <form method="POST" id="messageForm" onsubmit="return submitForm(event)">
                        <div class="form-group">
                            <label for="receiver_id">
                                <i class="fas fa-user-graduate"></i> Select Student
                            </label>
                            <select id="receiver_id" name="receiver_id" class="form-control" required>
                                <option value="">✉️ Choose a student...</option>
                                <?php $students->data_seek(0); ?>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                        <?php echo ($reply_to_id == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                        (<?php echo htmlspecialchars($student['registration_number']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-heading"></i> Subject
                            </label>
                            <input type="text" id="subject" name="subject" class="form-control" 
                                   required placeholder="Enter message subject" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="message">
                                <i class="fas fa-comment"></i> Message
                            </label>
                            <textarea id="message" name="message" class="form-control" 
                                      required placeholder="Type your message here..." 
                                      onkeyup="updateCharCount()"></textarea>
                            <div class="char-counter" id="charCounter">0 characters</div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                <i class="fas fa-eraser"></i> Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Section -->
            <div style="margin-top: 20px; text-align: center; color: var(--gray); font-size: 12px;">
                <i class="fas fa-shield-alt" style="color: var(--primary);"></i> 
                All messages are encrypted and stored securely
            </div>
        </main>
    </div>

    <script>
        // Set reply to student from URL parameter
        <?php if ($reply_to_id > 0 && !empty($reply_to_name)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('Replying to <?php echo htmlspecialchars($reply_to_name); ?>', 'info');
        });
        <?php endif; ?>

        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Select student from recent conversations
        function selectStudent(studentId, studentName) {
            document.getElementById('receiver_id').value = studentId;
            document.getElementById('subject').focus();
            showToast(`Selected: ${studentName}`, 'info');
        }

        // Use template
        function useTemplate(template) {
            const subjectField = document.getElementById('subject');
            const messageField = document.getElementById('message');
            
            const templates = {
                homework: {
                    subject: 'Homework Reminder',
                    message: 'Dear Student,\n\nThis is a reminder about pending homework assignments. Please ensure all work is submitted by the deadline.\n\nBest regards,\n' + '<?php echo $user['username']; ?>'
                },
                meeting: {
                    subject: 'Parent-Teacher Meeting',
                    message: 'Dear Student,\n\nI would like to schedule a parent-teacher meeting to discuss your academic progress. Please let me know your available times.\n\nBest regards,\n' + '<?php echo $user['username']; ?>'
                },
                announcement: {
                    subject: 'Class Announcement',
                    message: 'Dear Students,\n\nThis is an important class announcement. Please read carefully and let me know if you have any questions.\n\nBest regards,\n' + '<?php echo $user['username']; ?>'
                },
                reminder: {
                    subject: 'General Reminder',
                    message: 'Dear Student,\n\nThis is a gentle reminder about upcoming events and deadlines. Please stay updated with class announcements.\n\nBest regards,\n' + '<?php echo $user['username']; ?>'
                }
            };
            
            if (templates[template]) {
                subjectField.value = templates[template].subject;
                messageField.value = templates[template].message;
                updateCharCount();
            }
        }

        // Update character count
        function updateCharCount() {
            const message = document.getElementById('message');
            const counter = document.getElementById('charCounter');
            const length = message.value.length;
            
            counter.textContent = `${length} characters`;
            
            if (length > 900) {
                counter.className = 'char-counter warning';
            } else if (length > 950) {
                counter.className = 'char-counter danger';
            } else {
                counter.className = 'char-counter';
            }
        }

        // Clear form
        function clearForm() {
            if (confirm('Clear all form fields?')) {
                document.getElementById('messageForm').reset();
                document.getElementById('charCounter').textContent = '0 characters';
                document.getElementById('charCounter').className = 'char-counter';
            }
        }

        // Submit form
        async function submitForm(event) {
            event.preventDefault();
            
            const receiverId = document.getElementById('receiver_id').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            if (!receiverId) {
                showToast('Please select a student', 'error');
                return false;
            }
            
            if (!subject.trim()) {
                showToast('Please enter a subject', 'error');
                return false;
            }
            
            if (!message.trim()) {
                showToast('Please enter a message', 'error');
                return false;
            }
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            // Simulate form submission (actual form will submit normally)
            setTimeout(() => {
                document.getElementById('messageForm').submit();
            }, 500);
            
            return false;
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.background = type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--primary)';
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

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to submit
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('messageForm').requestSubmit();
            }
            // Ctrl + L to clear
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                clearForm();
            }
            // Escape to clear selection
            if (e.key === 'Escape') {
                document.getElementById('receiver_id').value = '';
            }
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Initialize character counter
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount();
        });

        // Ripple effect for buttons
        document.querySelectorAll('.btn, .conversation-chip, .template-btn').forEach(element => {
            element.addEventListener('click', function(e) {
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
    </script>
</body>
</html>