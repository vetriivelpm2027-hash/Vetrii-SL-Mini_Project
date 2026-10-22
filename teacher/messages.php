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

// Get unread count
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $_SESSION['user_id']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread'];
$unread_stmt->close();

// Get recent senders for quick reply
$recent_senders_stmt = $conn->prepare("SELECT DISTINCT u.id, u.username, s.first_name, s.last_name, s.profile_pic
                                       FROM messages m
                                       JOIN users u ON m.sender_id = u.id
                                       LEFT JOIN students s ON u.id = s.user_id
                                       WHERE m.receiver_id = ?
                                       ORDER BY m.created_at DESC
                                       LIMIT 5");
$recent_senders_stmt->bind_param("i", $_SESSION['user_id']);
$recent_senders_stmt->execute();
$recent_senders = $recent_senders_stmt->get_result();
$recent_senders_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RKMS HOME Teacher Portal</title>
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
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

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

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-soft);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 30px;
            color: var(--primary);
        }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .stat-desc {
            font-size: 12px;
            color: var(--gray);
        }

        /* Quick Reply Section */
        .quick-reply {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border: 1px solid var(--primary-soft);
        }

        .quick-reply-label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .quick-reply-senders {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .sender-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: var(--primary-soft);
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .sender-chip:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .sender-chip:hover span {
            color: white;
        }

        .sender-chip i {
            color: var(--primary);
            font-size: 14px;
        }

        .sender-chip:hover i {
            color: white;
        }

        .sender-chip span {
            color: var(--dark);
            font-weight: 500;
            font-size: 13px;
        }

        .compose-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .compose-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Messages Card */
        .messages-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .messages-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to right, rgba(255, 140, 66, 0.02), transparent);
        }

        .messages-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .messages-header h3 i {
            color: var(--primary);
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 15px 10px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            font-size: 13px;
            width: 250px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .filter-dropdown {
            position: relative;
        }

        .filter-btn {
            padding: 10px 20px;
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
        .table-responsive {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        .messages-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .messages-table thead {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        .messages-table th {
            padding: 15px 20px;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--primary);
            white-space: nowrap;
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
            width: 40px;
            height: 40px;
            background: var(--gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .sender-details {
            flex: 1;
        }

        .sender-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .sender-role {
            font-size: 11px;
            color: var(--gray);
        }

        /* Message Content */
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
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
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
            white-space: nowrap;
        }

        .message-date i {
            font-size: 12px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .action-btn.view {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }

        .action-btn.view:hover {
            background: var(--info);
            color: white;
        }

        .action-btn.reply {
            background: rgba(255, 140, 66, 0.1);
            color: var(--primary);
        }

        .action-btn.reply:hover {
            background: var(--gradient);
            color: white;
        }

        .action-btn.delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .action-btn.delete:hover {
            background: var(--danger);
            color: white;
        }

        /* Pagination */
        .pagination {
            padding: 20px 25px;
            border-top: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            color: var(--gray);
            font-size: 13px;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 2px solid var(--primary-soft);
            background: white;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-btn.active {
            background: var(--gradient);
            border-color: var(--primary);
            color: white;
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
            display: flex;
            gap: 10px;
        }

        .reply-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            resize: vertical;
        }

        .send-reply {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
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
            height: 20px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
            
            .quick-reply {
                flex-direction: column;
                align-items: stretch;
            }
            
            .quick-reply-senders {
                justify-content: center;
            }
            
            .messages-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .search-box input:focus {
                width: 100%;
            }
            
            .filter-dropdown {
                width: 100%;
            }
            
            .filter-btn {
                width: 100%;
                justify-content: center;
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
        <p style="color: var(--primary); font-weight: 600;">Loading messages...</p>
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
                <div class="sidebar-subtitle">Teacher Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages 
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
                    <h1><i class="fas fa-envelope"></i> Student Messages</h1>
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

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Messages</div>
                        <div class="stat-value" id="totalMessages">0</div>
                        <div class="stat-desc">All time</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Unread</div>
                        <div class="stat-value" id="unreadMessages"><?php echo $unread_count; ?></div>
                        <div class="stat-desc">Need attention</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Replied</div>
                        <div class="stat-value" id="repliedMessages">0</div>
                        <div class="stat-desc">This month</div>
                    </div>
                </div>
            </div>

            <!-- Quick Reply Section -->
            <?php if ($recent_senders->num_rows > 0): ?>
            <div class="quick-reply">
                <span class="quick-reply-label">Quick Reply to:</span>
                <div class="quick-reply-senders">
                    <?php while ($sender = $recent_senders->fetch_assoc()): ?>
                        <div class="sender-chip" onclick="quickReply(<?php echo $sender['id']; ?>, '<?php echo htmlspecialchars($sender['first_name'] . ' ' . $sender['last_name']); ?>')">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($sender['first_name'] ?? $sender['username']); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button class="compose-btn" onclick="composeMessage()">
                    <i class="fas fa-pen"></i> Compose
                </button>
            </div>
            <?php endif; ?>

            <!-- Messages Card -->
            <div class="messages-card">
                <div class="messages-header">
                    <h3><i class="fas fa-inbox"></i> Inbox</h3>
                    
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search messages..." onkeyup="filterMessages()">
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
                        
                        <button class="filter-btn" onclick="refreshMessages()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="messagesTbody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <div class="spinner" style="margin: 0 auto;"></div>
                                    <p style="color: var(--gray); margin-top: 15px;">Loading messages...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">
                        Showing 0 entries
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <button class="page-btn" onclick="changePage('prev')"><i class="fas fa-chevron-left"></i></button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn" onclick="changePage(2)">2</button>
                        <button class="page-btn" onclick="changePage(3)">3</button>
                        <button class="page-btn" onclick="changePage('next')"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // State management
        let allMessages = [];
        let messageIds = new Set();
        let currentFilter = 'all';
        let searchTerm = '';
        let currentPage = 1;
        let rowsPerPage = 10;

        // Toggle sidebar
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
                const hasNewMessages = data.messages.some(msg => !messageIds.has(msg.id));
                
                if (hasNewMessages) {
                    // Update message IDs
                    data.messages.forEach(msg => messageIds.add(msg.id));
                    
                    // Show notification
                    showNotification('New Messages', 'You have new messages from students');
                }
                
                // Update stats
                updateStats();
                
                // Display messages
                displayMessages();
                
            } catch (error) {
                console.error('Failed to load messages:', error);
                document.getElementById('messagesTbody').innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-exclamation-circle"></i>
                                <h3>Failed to Load Messages</h3>
                                <p>Please try again later</p>
                                <button class="compose-btn" onclick="loadMessages()">
                                    <i class="fas fa-sync-alt"></i> Retry
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            } finally {
                showLoading(false);
            }
        }

        // Update statistics
        function updateStats() {
            const totalMessages = allMessages.length;
            const unreadMessages = allMessages.filter(msg => !msg.is_read).length;
            const repliedMessages = allMessages.filter(msg => msg.replied).length;
            
            document.getElementById('totalMessages').textContent = totalMessages;
            document.getElementById('unreadMessages').textContent = unreadMessages;
            document.getElementById('repliedMessages').textContent = repliedMessages;
        }

        // Display messages with filters
        function displayMessages() {
            const tbody = document.getElementById('messagesTbody');
            
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
            
            // Update pagination info
            const totalEntries = filteredMessages.length;
            const start = (currentPage - 1) * rowsPerPage + 1;
            const end = Math.min(currentPage * rowsPerPage, totalEntries);
            
            document.getElementById('paginationInfo').textContent = 
                `Showing ${totalEntries > 0 ? start : 0} to ${end} of ${totalEntries} entries`;
            
            // Paginate
            const startIndex = (currentPage - 1) * rowsPerPage;
            const paginatedMessages = filteredMessages.slice(startIndex, startIndex + rowsPerPage);
            
            if (paginatedMessages.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-envelope-open"></i>
                                <h3>No Messages Found</h3>
                                <p>${allMessages.length === 0 ? 'You haven\'t received any messages yet.' : 'Try adjusting your filters.'}</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = '';
            
            paginatedMessages.forEach(msg => {
                const row = document.createElement('tr');
                row.className = `${!msg.is_read ? 'unread' : ''}`;
                row.onclick = () => openMessage(msg.id);
                
                // Format date
                const msgDate = new Date(msg.date);
                const now = new Date();
                let dateDisplay;
                
                if (msgDate.toDateString() === now.toDateString()) {
                    dateDisplay = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                } else {
                    dateDisplay = msgDate.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                }
                
                // Get initials
                const initials = msg.from.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                
                row.innerHTML = `
                    <td>
                        <div class="sender-info">
                            <div class="sender-avatar">
                                ${initials || 'ST'}
                            </div>
                            <div class="sender-details">
                                <div class="sender-name">${msg.from}</div>
                                <div class="sender-role">Student</div>
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
                    <td>
                        <div class="action-buttons" onclick="event.stopPropagation()">
                            <button class="action-btn view" onclick="viewMessage(${msg.id})" data-tooltip="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn reply" onclick="replyToMessage(${msg.sender_id})" data-tooltip="Reply">
                                <i class="fas fa-reply"></i>
                            </button>
                            <button class="action-btn delete" onclick="deleteMessage(${msg.id})" data-tooltip="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Filter messages
        function filterMessages(filter = null) {
            if (filter) {
                currentFilter = filter;
                document.getElementById('filterMenu').classList.remove('show');
                
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
            
            currentPage = 1;
            displayMessages();
        }

        // Search messages
        function filterMessages() {
            searchTerm = document.getElementById('searchInput').value;
            currentPage = 1;
            displayMessages();
        }

        // Change page
        function changePage(page) {
            const totalPages = Math.ceil(allMessages.length / rowsPerPage);
            
            if (page === 'prev') {
                currentPage = Math.max(1, currentPage - 1);
            } else if (page === 'next') {
                currentPage = Math.min(totalPages, currentPage + 1);
            } else {
                currentPage = page;
            }
            
            displayMessages();
            
            // Update active page button
            document.querySelectorAll('.page-btn').forEach((btn, index) => {
                if (index === 1) { // Skip prev button
                    btn.classList.toggle('active', index === currentPage);
                }
            });
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
                        <textarea class="reply-input" id="replyText" placeholder="Type your reply..."></textarea>
                        <button class="send-reply" onclick="sendReply(${data.sender_id})">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                `;
                
                document.getElementById('messageModal').classList.add('active');
                
                // Refresh messages list
                loadMessages();
                
            } catch (error) {
                console.error('Failed to load message:', error);
                showToast('Failed to load message details', 'error');
            } finally {
                showLoading(false);
            }
        }

        // View message (alias for openMessage)
        function viewMessage(id) {
            openMessage(id);
        }

        // Quick reply
        function quickReply(senderId, senderName) {
            window.location.href = `reply_message.php?to=${senderId}&name=${encodeURIComponent(senderName)}`;
        }

        // Reply to message
        function replyToMessage(senderId) {
            window.location.href = `reply_message.php?to=${senderId}`;
        }

        // Send reply from modal
        async function sendReply(senderId) {
            const replyText = document.getElementById('replyText').value;
            
            if (!replyText.trim()) {
                alert('Please enter a reply message');
                return;
            }
            
            showLoading(true);
            
            try {
                const response = await fetch('../api/send_reply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_id: senderId,
                        message: replyText
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Reply sent successfully!', 'success');
                    closeModal();
                    loadMessages();
                } else {
                    alert('Failed to send reply: ' + data.error);
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to send reply');
            } finally {
                showLoading(false);
            }
        }

        // Delete message
        async function deleteMessage(messageId) {
            if (!confirm('Are you sure you want to delete this message?')) {
                return;
            }
            
            showLoading(true);
            
            try {
                const response = await fetch('../api/delete_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: messageId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Message deleted successfully', 'success');
                    loadMessages();
                } else {
                    alert('Failed to delete message');
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete message');
            } finally {
                showLoading(false);
            }
        }

        // Compose new message
        function composeMessage() {
            window.location.href = 'reply_message.php';
        }

        // Refresh messages
        function refreshMessages() {
            loadMessages();
        }

        // Close modal
        function closeModal() {
            document.getElementById('messageModal').classList.remove('active');
        }

        // Show/hide loading
        function showLoading(show) {
            document.getElementById('loadingOverlay').classList.toggle('active', show);
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.background = type === 'success' ? 'var(--success)' : 'var(--danger)';
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

        // Show notification
        function showNotification(title, body) {
            if (!("Notification" in window)) return;
            
            if (Notification.permission === "granted") {
                new Notification(title, { body });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission();
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F for search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            // Ctrl + R for refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshMessages();
            }
            // Ctrl + N for compose
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                composeMessage();
            }
            // Escape to close modal
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Request notification permission
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        // Load messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages();
            
            // Auto-refresh every 30 seconds
            setInterval(loadMessages, 30000);
        });

        // Ripple effect for buttons
        document.querySelectorAll('.action-btn, .compose-btn, .filter-btn, .sender-chip').forEach(element => {
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