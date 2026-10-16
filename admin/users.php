<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

// handle bulk deletion
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    if (isset($conn) && $conn) {
        // Temporarily disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        // Delete related messages first
        $delete_messages = $conn->query("DELETE FROM messages WHERE sender_id IN (SELECT id FROM users WHERE role != 'admin') OR receiver_id IN (SELECT id FROM users WHERE role != 'admin')");
        
        // Then delete the users
        $del = $conn->query("DELETE FROM users WHERE role != 'admin'");
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        if ($del && $delete_messages !== false) {
            $success = 'All non-admin users and their related data have been deleted.';
        } else {
            $error = 'Failed to delete users: ' . $conn->error;
        }
    } else {
        $error = 'Database connection not available.';
    }
}

$success = $success ?: ($_GET['success'] ?? '');
$error = $error ?: ($_GET['error'] ?? '');

// fetch users for listing
$users = null;
if (isset($conn) && $conn) {
    $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Ramakrishna Mission Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --saffron: #FF9933;
            --saffron-dark: #e68a00;
            --saffron-light: #ffb366;
            --orange: #FF8C42;
            --orange-dark: #e67300;
            --orange-light: #ffaa66;
            --maroon: #800000;
            --maroon-dark: #660000;
            --maroon-light: #b30000;
            --gold: #FFD700;
            --gold-dark: #e6b800;
            --gold-light: #ffe066;
            --cream: #FFF8E7;
            --cream-dark: #f5e6d3;
            --brown: #8B4513;
            --brown-light: #a0522d;
            --success: #10b981;
            --danger: #ef4444;
            --info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }

        body {
            background: linear-gradient(135deg, #FFF8E7 0%, #FFE5CC 50%, #FFF8E7 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: "🕉️";
            position: fixed;
            top: 20px;
            right: 20px;
            font-size: 100px;
            opacity: 0.03;
            z-index: 0;
            pointer-events: none;
            animation: rotate 20s linear infinite;
        }

        body::after {
            content: "রামকৃষ্ণ";
            position: fixed;
            bottom: 20px;
            left: 20px;
            font-size: 60px;
            font-weight: 800;
            opacity: 0.03;
            color: var(--saffron);
            z-index: 0;
            pointer-events: none;
            transform: rotate(-5deg);
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Enhanced Sidebar with Ramakrishna Theme */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #8B4513 0%, #800000 50%, #4A0404 100%);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
            box-shadow: var(--shadow-2xl);
            z-index: 50;
            border-right: 3px solid var(--gold);
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.05"><path d="M50 15 L61 40 L88 44 L68 62 L73 90 L50 75 L27 90 L32 62 L12 44 L39 40 Z" fill="%23FFD700"/></svg>');
            background-size: 100px 100px;
            pointer-events: none;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 2px solid var(--gold);
            margin-bottom: 1.5rem;
            position: relative;
            text-align: center;
        }

        .ramakrishna-image {
            width: 100px;
            height: 100px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--gold);
            box-shadow: var(--shadow-xl);
            animation: glow 3s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px var(--gold); }
            50% { box-shadow: 0 0 40px var(--saffron); }
        }

        .ramakrishna-image svg {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--saffron), var(--orange));
        }

        .sidebar-logo {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--gold);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--cream);
            text-transform: uppercase;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            color: var(--gold-light);
            margin-top: 0.5rem;
            font-style: italic;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.25rem;
            color: var(--cream);
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 0.75rem;
            border: 1px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--gold), var(--saffron));
            color: var(--maroon);
            transform: translateX(5px);
            border-color: var(--gold);
            box-shadow: var(--shadow-lg);
        }

        .sidebar-menu a.active {
            font-weight: 600;
            background: linear-gradient(90deg, var(--gold), var(--orange));
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: rgba(255, 248, 231, 0.6);
            backdrop-filter: blur(10px);
            min-height: 100vh;
            position: relative;
        }

        /* Top Bar with Spiritual Theme */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .topbar::before {
            content: '🚩';
            position: absolute;
            left: -20px;
            top: -20px;
            font-size: 80px;
            opacity: 0.05;
            transform: rotate(-15deg);
        }

        .topbar h1 {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--maroon), var(--brown));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            padding-left: 50px;
        }

        .topbar h1::before {
            content: '🕉️';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            background: none;
            -webkit-text-fill-color: initial;
            color: var(--saffron);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, var(--cream), white);
            padding: 0.5rem 1.5rem 0.5rem 0.5rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gold);
        }

        .user-profile:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            border-color: var(--saffron);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--maroon);
            font-weight: 700;
            font-size: 1.25rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gold);
        }

        /* Alert Messages */
        .alert {
            margin: 1.5rem 0;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.5s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee, #ffe6e6);
            color: var(--maroon);
            border-color: var(--danger);
        }

        .alert-error::before {
            background: var(--danger);
        }

        .alert-success {
            background: linear-gradient(135deg, #efe, #e6ffe6);
            color: var(--success);
            border-color: var(--success);
        }

        .alert-success::before {
            background: var(--success);
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 30px;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2xl);
        }

        .stat-card::before {
            content: '🚩';
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 60px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--brown);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--maroon);
            line-height: 1.2;
        }

        /* Enhanced Card */
        .card {
            background: white;
            border-radius: 30px;
            box-shadow: var(--shadow-xl);
            margin-bottom: 2rem;
            border: 2px solid var(--gold);
            overflow: hidden;
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--saffron), white, var(--green));
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--cream-dark);
            background: linear-gradient(135deg, var(--cream), white);
            position: relative;
        }

        .card-header::before {
            content: '🕉️';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            opacity: 0.2;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title span {
            font-size: 2rem;
        }

        .card-body {
            padding: 2rem;
            background: white;
        }

        /* Enhanced Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 20px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        thead tr {
            background: linear-gradient(135deg, var(--saffron), var(--orange));
        }

        th {
            text-align: left;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--maroon);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        th:first-child {
            border-top-left-radius: 50px;
            border-bottom-left-radius: 50px;
        }

        th:last-child {
            border-top-right-radius: 50px;
            border-bottom-right-radius: 50px;
        }

        tbody tr {
            background: linear-gradient(135deg, white, var(--cream));
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            border: 1px solid transparent;
        }

        tbody tr:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: var(--shadow-xl);
            border-color: var(--gold);
            background: linear-gradient(135deg, white, var(--saffron-light));
        }

        td {
            padding: 1rem 1.5rem;
            color: var(--brown);
            font-size: 0.95rem;
            border: none;
        }

        /* Role Badges */
        .role-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: var(--shadow-sm);
        }

        .role-badge.admin {
            background: linear-gradient(135deg, var(--maroon), #990000);
            color: var(--gold);
        }

        .role-badge.secretary {
            background: linear-gradient(135deg, var(--saffron), #e68a00);
            color: var(--maroon);
        }

        .role-badge.principal {
            background: linear-gradient(135deg, var(--gold), #e6b800);
            color: var(--maroon);
        }

        .role-badge.hod {
            background: linear-gradient(135deg, var(--orange), #ff751a);
            color: white;
        }

        .role-badge.class_teacher {
            background: linear-gradient(135deg, var(--brown), #5a2e0e);
            color: var(--cream);
        }

        /* Enhanced Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 0.5rem;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
            box-shadow: 0 4px 15px rgba(255, 153, 51, 0.3);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 153, 51, 0.4);
            border-color: var(--gold);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--maroon), #660000);
            color: var(--cream);
            box-shadow: 0 4px 15px rgba(128, 0, 0, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(128, 0, 0, 0.4);
            border-color: var(--gold);
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.75rem;
        }

        /* Department Tags */
        .dept-tag {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--cream), #f5e6d3);
            color: var(--brown);
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid var(--gold);
        }

        /* Search Bar */
        .search-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem;
            border-radius: 50px;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gold);
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--cream-dark);
            border-radius: 50px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: var(--cream);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 3px rgba(255, 153, 51, 0.2);
        }

        .filter-select {
            padding: 0.75rem 2rem 0.75rem 1.5rem;
            border: 2px solid var(--cream-dark);
            border-radius: 50px;
            font-size: 0.95rem;
            background: var(--cream);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%238B4513' viewBox='0 0 20 20'%3E%3Cpath stroke='%238B4513' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-item {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: white;
            color: var(--brown);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid var(--cream-dark);
        }

        .page-item.active,
        .page-item:hover {
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
            border-color: var(--gold);
            transform: scale(1.1);
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 1rem 1.5rem;
            border-radius: 50px;
            background: white;
            border: 2px solid var(--gold);
            color: var(--brown);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 150px;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-brand,
            .sidebar-subtitle,
            .ramakrishna-image,
            .sidebar-menu a span:not(.emoji) {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 1rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .sidebar:hover {
                width: 280px;
            }
            
            .sidebar:hover .sidebar-brand,
            .sidebar:hover .sidebar-subtitle,
            .sidebar:hover .ramakrishna-image,
            .sidebar:hover .sidebar-menu a span:not(.emoji) {
                display: inline-block;
            }
            
            .sidebar:hover .sidebar-menu a {
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .topbar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .search-section {
                flex-direction: column;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            th {
                padding: 0.75rem;
                font-size: 0.7rem;
            }
            
            body::before {
                font-size: 50px;
            }
            
            body::after {
                font-size: 30px;
            }
        }

        /* Spiritual Elements */
        .om-symbol {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 60px;
            opacity: 0.1;
            z-index: 0;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .quote-box {
            background: linear-gradient(135deg, rgba(255, 153, 51, 0.1), rgba(255, 215, 0, 0.1));
            border-left: 5px solid var(--saffron);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 20px;
            font-style: italic;
            color: var(--maroon);
            backdrop-filter: blur(5px);
        }

        .quote-box::before {
            content: '"';
            font-size: 3rem;
            color: var(--saffron);
            opacity: 0.3;
            position: absolute;
            top: -10px;
            left: 10px;
        }
    </style>
</head>
<body>
    <div class="om-symbol">🕉️</div>
    
    <div class="dashboard-container">
        <!-- Sidebar with Ramakrishna Image -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="ramakrishna-image">
                    <!-- SVG Representation of Ramakrishna -->
                    <svg viewBox="0 0 100 100">
                        <!-- Background glow -->
                        <circle cx="50" cy="50" r="45" fill="url(#grad)" opacity="0.2"/>
                        
                        <!-- Face outline -->
                        <circle cx="50" cy="45" r="25" fill="#FFE5B4" stroke="#FFD700" stroke-width="2"/>
                        
                        <!-- Turban/Head wrap -->
                        <path d="M30 30 L70 30 L65 20 L35 20 Z" fill="#FF9933"/>
                        
                        <!-- Eyes (closed in meditation) -->
                        <path d="M40 45 Q45 40 50 45" stroke="#8B4513" stroke-width="2" fill="none"/>
                        <path d="M50 45 Q55 40 60 45" stroke="#8B4513" stroke-width="2" fill="none"/>
                        
                        <!-- Nose -->
                        <path d="M50 50 L50 55" stroke="#8B4513" stroke-width="2"/>
                        
                        <!-- Beard -->
                        <path d="M40 60 Q50 70 60 60" stroke="#8B4513" stroke-width="2" fill="none"/>
                        
                        <!-- Robe -->
                        <rect x="40" y="70" width="20" height="20" fill="#FF9933" opacity="0.5"/>
                        
                        <!-- Gradient definition -->
                        <defs>
                            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#FFD700;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#FF9933;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="sidebar-logo">🚩 RKMS</div>
                <div class="sidebar-brand">Ramakrishna Mission</div>
                <div class="sidebar-subtitle">"সেবা ও সংযম"</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span>📊</span> <span>Dashboard</span></a></li>
                <li><a href="students.php"><span>👨‍🎓</span> <span>Students</span></a></li>
                <li><a href="users.php" class="active"><span>👥</span> <span>Users</span></a></li>
                <li><a href="departments.php"><span>🏢</span> <span>Departments</span></a></li>
                <li><a href="messages.php"><span>💬</span> <span>Messages</span></a></li>
                <li><a href="export.php"><span>📥</span> <span>Export Data</span></a></li>
                <li><a href="settings.php"><span>⚙️</span> <span>Settings</span></a></li>
                <li><a href="../logout.php"><span>🚪</span> <span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <h1>Users Management</h1>
                <div style="margin-left:auto;">
                    <form method="post" onsubmit="return confirm('⚠️ Permanently delete all non-admin users?');" style="display:inline;">
                        <button type="submit" name="delete_all" class="btn btn-danger" style="padding:0.5rem 1rem; font-size:0.9rem;">
                            <i class="fas fa-trash-alt"></i> Delete All Users
                        </button>
                    </form>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>

            <!-- Quote Box -->
            <div class="quote-box">
                "They alone live who live for others, the rest are more dead than alive." - Swami Vivekananda
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <?php
                $totalUsers = $adminCount = $teacherCount = $activeToday = 0;
                if (isset($conn) && $conn) {
                    $totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                    $adminCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
                    $teacherCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='class_teacher'")->fetch_assoc()['count'];
                    $r = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(last_login) = CURDATE()");
                    $activeToday = $r ? $r->fetch_assoc()['count'] : 0;
                }
                ?>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👑</div>
                    <div class="stat-label">Admins</div>
                    <div class="stat-number"><?php echo $adminCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-label">Teachers</div>
                    <div class="stat-number"><?php echo $teacherCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✨</div>
                    <div class="stat-label">Active Today</div>
                    <div class="stat-number"><?php echo $activeToday; ?></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="add_user.php" class="action-btn">
                    <span>➕</span> Add New User
                </a>
                <a href="import_users.php" class="action-btn">
                    <span>📤</span> Import Users
                </a>
                <a href="export_users.php" class="action-btn">
                    <span>📥</span> Export Users
                </a>
                <a href="permissions.php" class="action-btn">
                    <span>🔐</span> Permissions
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>❌</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Search Section -->
            <div class="search-section">
                <input type="text" class="search-input" placeholder="🔍 Search users by name, email, or role..." id="searchInput">
                <select class="filter-select" id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="secretary">Secretary</option>
                    <option value="principal">Principal</option>
                    <option value="hod">HOD</option>
                    <option value="class_teacher">Class Teacher</option>
                </select>
            </div>

            <!-- Users Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>👥</span>
                        Registered Users (<?php echo $users ? $users->num_rows : 0; ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset pointer and iterate if available
                                if ($users) {
                                    $users->data_seek(0);
                                    while ($row = $users->fetch_assoc()): ?>
                                <tr onclick="window.location='view_user.php?id=<?php echo $row['id']; ?>'" style="cursor: pointer;">
                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 30px; height: 30px; background: linear-gradient(135deg, var(--saffron), var(--orange)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $row['role']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dept-tag">
                                            <?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span data-tooltip="<?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?>">
                                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td onclick="event.stopPropagation();">
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                                <span>✏️</span> Edit
                                            </a>
                                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('⚠️ Are you sure you want to delete this user? This action cannot be undone.');">
                                                <span>🗑️</span> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                    <?php endwhile; 
                                } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer -->
                    <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: var(--brown);">
                            🕉️ Showing <strong><?php echo $users ? $users->num_rows : 0; ?></strong> users
                        </div>
                        <div class="pagination">
                            <a href="#" class="page-item active">1</a>
                            <a href="#" class="page-item">2</a>
                            <a href="#" class="page-item">3</a>
                            <a href="#" class="page-item">4</a>
                            <a href="#" class="page-item">5</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript for Enhanced Functionality -->
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('#usersTable tbody tr');
            
            tableRows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Role filter
        document.getElementById('roleFilter').addEventListener('change', function() {
            let filterValue = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('#usersTable tbody tr');
            
            tableRows.forEach(row => {
                let roleCell = row.cells[3]; // Role column
                let roleText = roleCell.textContent.toLowerCase();
                
                if (filterValue === '' || roleText.includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add animation on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('tr').forEach(row => {
            row.style.opacity = 0;
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'all 0.5s ease';
            observer.observe(row);
        });

        // Tooltip functionality
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.position = 'absolute';
                tooltip.style.background = 'var(--maroon)';
                tooltip.style.color = 'var(--gold)';
                tooltip.style.padding = '5px 10px';
                tooltip.style.borderRadius = '20px';
                tooltip.style.fontSize = '12px';
                tooltip.style.zIndex = '1000';
                tooltip.style.border = '1px solid var(--gold)';
                
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
                
                this.addEventListener('mouseleave', function() {
                    tooltip.remove();
                });
            });
        });

        // Export functionality
        function exportTableToCSV() {
            let csv = [];
            let rows = document.querySelectorAll('#usersTable tr');
            
            for (let row of rows) {
                let cells = row.querySelectorAll('td, th');
                let rowText = [];
                for (let cell of cells) {
                    // Clean the text content
                    let text = cell.textContent.trim().replace(/,/g, '');
                    rowText.push('"' + text + '"');
                }
                csv.push(rowText.join(','));
            }
            
            let csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            let downloadLink = document.createElement('a');
            downloadLink.download = 'ramakrishna_users_export.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Add export button to topbar
        const topbar = document.querySelector('.topbar');
        const exportBtn = document.createElement('button');
        exportBtn.innerHTML = '📥 Export CSV';
        exportBtn.className = 'btn btn-info';
        exportBtn.style.marginLeft = 'auto';
        exportBtn.onclick = exportTableToCSV;
        topbar.appendChild(exportBtn);

        // Spiritual greeting based on time
        const hour = new Date().getHours();
        let greeting;
        if (hour < 12) greeting = "🙏 Ram Ram";
        else if (hour < 18) greeting = "🙏 Jai Ramakrishna";
        else greeting = "🙏 Shubho Ratri";
        
        const quoteBox = document.querySelector('.quote-box');
        if (quoteBox) {
            quoteBox.innerHTML = greeting + ' - ' + quoteBox.innerHTML;
        }
    </script>
</body>
</html>