<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check role
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!in_array($user['role'], ['student'])) {
    header("Location: ../unauthorized.php");
    exit();
}

// Get unread count
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread'];
$unread_stmt->close();

// Get student info
$student_stmt = $conn->prepare("SELECT first_name, last_name, profile_pic FROM students WHERE user_id = ?");
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();
$student_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RKMS HOME Student Portal</title>
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
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .icon-btn {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 18px;
        }

        .icon-btn:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        /* Messages Card */
        .messages-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            animation: fadeInUp 0.5s ease;
        }

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

        .messages-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to right, rgba(255, 140, 66, 0.02), transparent);
        }

        .messages-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .messages-header h2 i {
            color: var(--primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 8px 15px 8px 40px;
            border: 2px solid var(--primary-soft);
            border-radius: 30px;
            font-size: 13px;
            width: 200px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            width: 250px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 14px;
        }

        .filter-dropdown {
            position: relative;
        }

        .filter-btn {
            padding: 8px 15px;
            background: var(--primary-soft);
            border: none;
            border-radius: 30px;
            color: var(--primary);
            font-weight: 500;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: var(--gradient);
            color: white;
        }

        .filter-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            padding: 10px 0;
            min-width: 150px;
            display: none;
            z-index: 10;
        }

        .filter-menu.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .filter-menu a {
            display: block;
            padding: 8px 20px;
            color: var(--dark);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .filter-menu a:hover {
            background: var(--primary-soft);
            color: var(--primary);
        }

        /* Messages Table */
        .messages-table {
            width: 100%;
            border-collapse: collapse;
        }

        .messages-table th {
            text-align: left;
            padding: 15px 20px;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .messages-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--primary-soft);
            color: var(--dark);
            font-size: 14px;
        }

        .messages-table tbody tr {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .messages-table tbody tr:hover {
            background: var(--primary-soft);
            transform: scale(1.01);
            box-shadow: var(--shadow-md);
        }

        .messages-table tbody tr.unread {
            background: rgba(255, 140, 66, 0.05);
            font-weight: 500;
        }

        .messages-table tbody tr.unread td:first-child {
            border-left: 4px solid var(--primary);
        }

        .messages-table tbody tr.new-message {
            animation: highlight 2s ease;
        }

        @keyframes highlight {
            0%, 100% { background: transparent; }
            50% { background: rgba(255, 140, 66, 0.2); }
        }

        /* Sender Info */
        .sender-info {
            display: flex;
            align-items: center;
            gap: 10px;
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
            color: var(--dark);
        }

        .sender-role {
            font-size: 11px;
            color: var(--gray);
        }

        /* Message Subject */
        .message-subject {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }

        .message-preview {
            font-size: 12px;
            color: var(--gray);
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge.unread {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .status-badge.read {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Date */
        .message-date {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gray);
            font-size: 12px;
        }

        .message-date i {
            font-size: 12px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: var(--primary-soft);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--dark);
            font-size: 18px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 20px;
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

        .skeleton-row td {
            padding: 20px;
        }

        .skeleton-line {
            height: 20px;
            width: 100%;
            background: #e0e0e0;
            border-radius: 4px;
        }

        /* Message Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--gradient);
            color: white;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
        }

        .message-detail {
            margin-bottom: 20px;
        }

        .message-detail-label {
            color: var(--gray);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .message-detail-value {
            color: var(--dark);
            font-size: 16px;
            font-weight: 500;
        }

        .message-content {
            background: var(--primary-soft);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            line-height: 1.6;
            color: var(--dark);
        }

        .reply-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--primary-soft);
        }

        .reply-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .reply-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .messages-table {
                display: block;
                overflow-x: auto;
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
                gap: 15px;
            }
            
            .page-title {
                width: 100%;
                justify-content: space-between;
            }
            
            .header-actions {
                flex-wrap: wrap;
            }
            
            .search-box input {
                width: 150px;
            }
            
            .search-box input:focus {
                width: 200px;
            }
            
            .messages-table td {
                min-width: 120px;
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
        .loading-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: var(--shadow-xl);
            z-index: 2000;
            align-items: center;
            gap: 15px;
        }

        .loading-indicator.active {
            display: flex;
            animation: pulse 2s infinite;
        }

        .loading-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid var(--primary-soft);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
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

    <!-- Loading Indicator -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="loading-spinner"></div>
        <span style="color: var(--primary); font-weight: 600;">Loading messages...</span>
    </div>

    <!-- Message Modal -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-envelope-open-text"></i> Message Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Messages</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a></li>
                <li><a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages 
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="message_teacher.php"><i class="fas fa-paper-plane"></i> Message Teacher</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shield-alt" style="color: var(--primary);"></i>
                    <span style="color: var(--gray); font-size: 12px;">Secure Messaging</span>
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
                    <h1><i class="fas fa-envelope"></i> My Messages</h1>
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge">
                            <i class="fas fa-circle"></i> <?php echo $unread_count; ?> unread
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="top-bar-right">
                    <div class="action-buttons">
                        <button class="icon-btn" onclick="refreshMessages()" data-tooltip="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="icon-btn" onclick="markAllAsRead()" data-tooltip="Mark all as read">
                            <i class="fas fa-check-double"></i>
                        </button>
                        <button class="icon-btn" onclick="composeMessage()" data-tooltip="New message">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    
                    <div class="user-profile" onclick="window.location.href='profile.php'">
                        <div class="user-avatar">
                            <?php 
                            $initials = strtoupper(substr($student['first_name'] ?? 'S', 0, 1) . substr($student['last_name'] ?? 'T', 0, 1));
                            echo $initials ?: 'ST';
                            ?>
                        </div>
                        <span><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Messages Card -->
            <div class="messages-card">
                <div class="messages-header">
                    <h2>
                        <i class="fas fa-inbox"></i> Inbox
                        <span id="messageCount" style="color: var(--gray); font-size: 14px; font-weight: 400;"></span>
                    </h2>
                    
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search messages..." onkeyup="searchMessages()">
                        </div>
                        
                        <div class="filter-dropdown">
                            <button class="filter-btn" onclick="toggleFilter()">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <div class="filter-menu" id="filterMenu">
                                <a href="#" onclick="filterMessages('all')">All Messages</a>
                                <a href="#" onclick="filterMessages('unread')">Unread</a>
                                <a href="#" onclick="filterMessages('read')">Read</a>
                                <a href="#" onclick="filterMessages('today')">Today</a>
                                <a href="#" onclick="filterMessages('week')">This Week</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="messages-table" id="messagesTable">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="messagesTbody">
                            <tr class="skeleton-row">
                                <td colspan="5">
                                    <div class="skeleton-line"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // State management
        let lastMessageCount = <?php echo $unread_count; ?>;
        let messageIds = new Set();
        let allMessages = [];
        let currentFilter = 'all';
        let searchTerm = '';

        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Toggle filter menu
        function toggleFilter() {
            document.getElementById('filterMenu').classList.toggle('show');
        }

        // Close filter menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-dropdown')) {
                document.getElementById('filterMenu').classList.remove('show');
            }
        });

        // Load messages
        async function loadMessages() {
            showLoading(true);
            
            try {
                const response = await fetch('../api/get_messages.php');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }

                allMessages = data.messages;
                
                // Check for new messages
                const hasNewMessages = data.count > lastMessageCount;
                
                if (hasNewMessages) {
                    // Play notification sound (optional)
                    // new Audio('notification.mp3').play();
                    
                    // Update browser tab title
                    document.title = `(${data.count}) Messages - RKMS HOME`;
                }
                
                lastMessageCount = data.count;
                displayMessages();
                
            } catch (error) {
                console.error('Failed to load messages:', error);
                document.getElementById('messagesTbody').innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--danger);">
                            <i class="fas fa-exclamation-circle" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                            Failed to load messages. Please try again.
                        </td>
                    </tr>
                `;
            } finally {
                showLoading(false);
            }
        }

        // Display messages with filters
        function displayMessages() {
            const tbody = document.getElementById('messagesTbody');
            const countDisplay = document.getElementById('messageCount');
            
            // Apply filters
            let filteredMessages = allMessages;
            
            if (currentFilter === 'unread') {
                filteredMessages = filteredMessages.filter(msg => !msg.is_read);
            } else if (currentFilter === 'read') {
                filteredMessages = filteredMessages.filter(msg => msg.is_read);
            } else if (currentFilter === 'today') {
                const today = new Date().toDateString();
                filteredMessages = filteredMessages.filter(msg => {
                    const msgDate = new Date(msg.date).toDateString();
                    return msgDate === today;
                });
            } else if (currentFilter === 'week') {
                const weekAgo = new Date();
                weekAgo.setDate(weekAgo.getDate() - 7);
                filteredMessages = filteredMessages.filter(msg => {
                    return new Date(msg.date) >= weekAgo;
                });
            }
            
            // Apply search
            if (searchTerm) {
                const term = searchTerm.toLowerCase();
                filteredMessages = filteredMessages.filter(msg => 
                    msg.from.toLowerCase().includes(term) ||
                    msg.subject.toLowerCase().includes(term) ||
                    msg.message.toLowerCase().includes(term)
                );
            }
            
            if (filteredMessages.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-envelope-open"></i>
                                <h3>No messages found</h3>
                                <p>${allMessages.length === 0 ? 'You haven\'t received any messages yet.' : 'Try adjusting your filters.'}</p>
                                ${allMessages.length === 0 ? `
                                    <button class="icon-btn" onclick="composeMessage()" style="padding: 10px 20px;">
                                        <i class="fas fa-paper-plane"></i> Send your first message
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
                countDisplay.textContent = '';
                return;
            }
            
            tbody.innerHTML = '';
            
            filteredMessages.forEach(msg => {
                const isNew = !messageIds.has(msg.id);
                if (isNew) messageIds.add(msg.id);
                
                const row = document.createElement('tr');
                row.className = `${!msg.is_read ? 'unread' : ''} ${isNew ? 'new-message' : ''}`;
                row.onclick = () => openMessage(msg.id);
                
                // Format date
                const msgDate = new Date(msg.date);
                const now = new Date();
                let dateDisplay;
                
                if (msgDate.toDateString() === now.toDateString()) {
                    dateDisplay = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                } else {
                    dateDisplay = msgDate.toLocaleDateString([], { month: 'short', day: 'numeric' });
                }
                
                row.innerHTML = `
                    <td>
                        <div class="sender-info">
                            <div class="sender-avatar">
                                ${msg.from.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="sender-name">${msg.from}</div>
                                <div class="sender-role">${msg.role || 'Sender'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="message-subject">${msg.subject}</div>
                    </td>
                    <td>
                        <div class="message-preview">${msg.message}</div>
                    </td>
                    <td>
                        <div class="message-date">
                            <i class="far fa-clock"></i>
                            ${dateDisplay}
                        </div>
                    </td>
                    <td>
                        <span class="status-badge ${msg.is_read ? 'read' : 'unread'}">
                            <i class="fas fa-${msg.is_read ? 'check-circle' : 'circle'}"></i>
                            ${msg.is_read ? 'Read' : 'Unread'}
                        </span>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            countDisplay.textContent = `(${filteredMessages.length} message${filteredMessages.length !== 1 ? 's' : ''})`;
        }

        // Search messages
        function searchMessages() {
            searchTerm = document.getElementById('searchInput').value;
            displayMessages();
        }

        // Filter messages
        function filterMessages(filter) {
            currentFilter = filter;
            document.getElementById('filterMenu').classList.remove('show');
            displayMessages();
            
            // Update filter button text
            const filterBtn = document.querySelector('.filter-btn');
            const filterNames = {
                'all': 'All Messages',
                'unread': 'Unread',
                'read': 'Read',
                'today': 'Today',
                'week': 'This Week'
            };
            filterBtn.innerHTML = `<i class="fas fa-filter"></i> ${filterNames[filter] || 'Filter'}`;
        }

        // Open message
        async function openMessage(messageId) {
            showLoading(true);
            
            try {
                const response = await fetch(`../api/get_message.php?id=${messageId}`);
                const data = await response.json();
                
                if (data.error) {
                    alert('Error loading message: ' + data.error);
                    return;
                }
                
                // Mark as read
                await fetch(`../api/mark_read.php?id=${messageId}`, { method: 'POST' });
                
                // Update modal content
                const modalBody = document.getElementById('modalBody');
                const msgDate = new Date(data.date);
                
                modalBody.innerHTML = `
                    <div class="message-detail">
                        <div class="message-detail-label">From</div>
                        <div class="message-detail-value">${data.from}</div>
                    </div>
                    <div class="message-detail">
                        <div class="message-detail-label">Subject</div>
                        <div class="message-detail-value">${data.subject}</div>
                    </div>
                    <div class="message-detail">
                        <div class="message-detail-label">Date</div>
                        <div class="message-detail-value">${msgDate.toLocaleString()}</div>
                    </div>
                    <div class="message-content">
                        ${data.full_message.replace(/\n/g, '<br>')}
                    </div>
                    <div class="reply-section">
                        <button class="reply-btn" onclick="replyToMessage(${data.sender_id})">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                    </div>
                `;
                
                document.getElementById('messageModal').classList.add('active');
                
                // Refresh messages list
                loadMessages();
                
            } catch (error) {
                console.error('Failed to load message:', error);
                alert('Failed to load message details');
            } finally {
                showLoading(false);
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('messageModal').classList.remove('active');
        }

        // Refresh messages
        function refreshMessages() {
            loadMessages();
        }

        // Mark all as read
        async function markAllAsRead() {
            if (!confirm('Mark all messages as read?')) return;
            
            showLoading(true);
            
            try {
                await fetch('../api/mark_all_read.php', { method: 'POST' });
                loadMessages();
            } catch (error) {
                console.error('Failed to mark all as read:', error);
                alert('Failed to mark messages as read');
            } finally {
                showLoading(false);
            }
        }

        // Compose message
        function composeMessage() {
            window.location.href = 'message_teacher.php';
        }

        // Reply to message
        function replyToMessage(senderId) {
            window.location.href = `message_teacher.php?reply_to=${senderId}`;
        }

        // Show/hide loading indicator
        function showLoading(show) {
            document.getElementById('loadingIndicator').classList.toggle('active', show);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + R to refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshMessages();
            }
            // Ctrl + F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            // Escape to close modal
            if (e.key === 'Escape') {
                closeModal();
            }
            // Ctrl + N to compose new message
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                composeMessage();
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(loadMessages, 30000);

        // Load messages on page load
        document.addEventListener('DOMContentLoaded', loadMessages);

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Add ripple effect to buttons
        document.querySelectorAll('.icon-btn, .filter-btn, .reply-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
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

        // Show notification for new messages
        function showNotification(title, body) {
            if (!("Notification" in window)) return;
            
            if (Notification.permission === "granted") {
                new Notification(title, { body });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        new Notification(title, { body });
                    }
                });
            }
        }

        // Request notification permission
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        // Check for new messages periodically and show notification
        setInterval(async () => {
            try {
                const response = await fetch('../api/get_unread_count.php');
                const data = await response.json();
                
                if (data.count > lastMessageCount) {
                    showNotification('New Message', `You have ${data.count} unread message${data.count !== 1 ? 's' : ''}`);
                }
            } catch (error) {
                console.error('Failed to check for new messages:', error);
            }
        }, 60000); // Check every minute
    </script>
</body>
</html>