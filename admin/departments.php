<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

// initialize messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// department statistics
$total_depts = 0;
$depts_with_hod = 0;
$total_students = 0;
$total_teachers = 0;
if (isset($conn) && $conn) {
    $res = $conn->query("SELECT COUNT(*) as count FROM departments");
    if ($res) $total_depts = (int)($res->fetch_assoc()['count'] ?? 0);
    $res = $conn->query("SELECT COUNT(*) as count FROM departments WHERE hod_id IS NOT NULL");
    if ($res) $depts_with_hod = (int)($res->fetch_assoc()['count'] ?? 0);
    $res = $conn->query("SELECT COUNT(*) as count FROM students");
    if ($res) $total_students = (int)($res->fetch_assoc()['count'] ?? 0);
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='class_teacher'");
    if ($res) $total_teachers = (int)($res->fetch_assoc()['count'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments Management - RKMS Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --saffron: #FF9933;
            --saffron-dark: #e68a00;
            --saffron-light: #ffb366;
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
            --gray-50: #f9fafb;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --sidebar-bg: #4A0404;
            --sidebar-hover: #660000;
            --accent: #FF9933;
            --accent-glow: rgba(255, 153, 51, 0.3);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
        }

        body {
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
            z-index: 0;
        }

        body::after {
            content: '🕉️';
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 100px;
            opacity: 0.1;
            color: var(--gold);
            transform: rotate(-15deg);
            animation: float 6s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(-15deg); }
            50% { transform: translateY(-20px) rotate(-10deg); }
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        /* Enhanced Sidebar - Maroon theme */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--maroon-dark) 0%, var(--sidebar-bg) 100%);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
            box-shadow: var(--shadow-2xl);
            z-index: 50;
            border-right: 2px solid var(--gold);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--maroon-dark);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--gold);
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 2px solid var(--gold);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .sidebar-header::before {
            content: '🚩';
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 5rem;
            font-weight: 800;
            color: rgba(255, 215, 0, 0.1);
            pointer-events: none;
            transform: rotate(-15deg);
        }

        .sidebar-logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--gold), var(--saffron));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            animation: glow 3s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { filter: drop-shadow(0 0 5px var(--gold)); }
            50% { filter: drop-shadow(0 0 15px var(--saffron)); }
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--cream);
        }

        .sidebar-brand span {
            color: var(--gold);
            font-weight: 800;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 1rem;
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
            border-radius: var(--radius-lg);
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: linear-gradient(180deg, var(--gold), var(--saffron));
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .sidebar-menu a:hover::before,
        .sidebar-menu a.active::before {
            transform: scaleY(1);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--maroon-light), transparent);
            color: var(--gold);
            transform: translateX(5px);
            border-color: var(--gold);
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: var(--cream);
            min-height: 100vh;
        }

        /* Top Bar - Matching image style */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gold);
            animation: slideDown 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .topbar::before {
            content: '🚩';
            position: absolute;
            left: -10px;
            top: -10px;
            font-size: 60px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .topbar h1 {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--maroon), var(--brown));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar h1 i {
            background: linear-gradient(135deg, var(--saffron), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--cream);
            padding: 0.5rem 1.5rem 0.5rem 0.5rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--gold);
        }

        .user-profile:hover {
            background: white;
            box-shadow: var(--shadow-md);
            border-color: var(--saffron);
            transform: translateY(-2px);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-weight: 600;
            font-size: 1rem;
            border: 2px solid var(--gold);
        }

        /* Stats Cards - Like image */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gold);
            transition: all 0.3s ease;
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
            background: linear-gradient(90deg, var(--saffron), var(--maroon));
        }

        .stat-card::after {
            content: '🚩';
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 40px;
            opacity: 0.05;
            transform: rotate(-15deg);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2xl);
            border-color: var(--saffron);
        }

        .stat-label {
            color: var(--maroon);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--maroon-dark);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .stat-footer {
            color: var(--brown);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-footer i {
            color: var(--saffron);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 0.5rem;
            text-decoration: none;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            box-shadow: 0 4px 15px rgba(128, 0, 0, 0.3);
            border-color: var(--gold);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, var(--maroon), var(--saffron));
        }

        .btn-success {
            background: white;
            color: var(--maroon);
            border: 2px solid var(--saffron);
        }

        .btn-success:hover {
            background: var(--saffron);
            color: var(--maroon);
            transform: translateY(-2px);
            border-color: var(--gold);
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.75rem;
            border-radius: var(--radius-md);
        }

        /* Enhanced Card */
        .card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            margin-bottom: 2rem;
            border: 2px solid var(--gold);
            overflow: hidden;
            animation: fadeInUp 0.5s ease;
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--saffron), var(--maroon), var(--gold));
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--cream-dark);
            background: linear-gradient(90deg, white, var(--cream));
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--saffron);
        }

        .card-badge {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid var(--gold);
        }

        .card-body {
            padding: 2rem;
            background: white;
        }

        /* Enhanced Table - Matching image style */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-lg);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        thead tr {
            background: linear-gradient(90deg, var(--maroon), var(--saffron));
        }

        th {
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--gold);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        th:first-child {
            border-top-left-radius: var(--radius-lg);
            border-bottom-left-radius: var(--radius-lg);
        }

        th:last-child {
            border-top-right-radius: var(--radius-lg);
            border-bottom-right-radius: var(--radius-lg);
        }

        tbody tr {
            background: white;
            border-radius: var(--radius-lg);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            border: 1px solid transparent;
        }

        tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            background: linear-gradient(90deg, white, var(--cream));
            border-color: var(--gold);
        }

        td {
            padding: 1.25rem 1.5rem;
            color: var(--brown);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--cream-dark);
        }

        td:first-child {
            font-weight: 600;
            color: var(--maroon);
        }

        /* Department Badge */
        .dept-code {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid var(--gold);
        }

        /* HOD Badge */
        .hod-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            background: var(--cream);
            color: var(--maroon);
            font-size: 0.875rem;
            border: 1px solid var(--gold);
        }

        .hod-badge i {
            color: var(--saffron);
        }

        .hod-badge.empty {
            background: var(--gray-100);
            color: var(--gray-500);
            border-color: var(--gray-300);
        }

        .hod-badge.empty i {
            color: var(--gray-400);
        }

        /* Action Icons - Like image */
        .action-icons {
            display: flex;
            gap: 0.5rem;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--cream);
            color: var(--maroon);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid var(--gold);
        }

        .action-icon:hover {
            background: var(--saffron);
            color: var(--maroon);
            transform: scale(1.1);
            border-color: var(--maroon);
        }

        .action-icon.delete:hover {
            background: var(--danger);
            color: white;
            border-color: white;
        }

        /* ID Badge */
        .id-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            background: linear-gradient(135deg, var(--cream), var(--cream-dark));
            border-radius: 50px;
            font-weight: 600;
            color: var(--maroon);
            font-size: 0.875rem;
            border: 1px solid var(--gold);
        }

        /* Export Button - Like image */
        .export-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: white;
            border: 2px solid var(--saffron);
            border-radius: 50px;
            color: var(--maroon);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--gold);
        }

        .export-btn i {
            font-size: 1rem;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--gold);
            border-radius: 50px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 3px rgba(255, 153, 51, 0.2);
        }

        select.search-input {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23800000' viewBox='0 0 20 20'%3E%3Cpath stroke='%23800000' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            appearance: none;
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.5rem 1rem;
            background: var(--maroon);
            color: var(--gold);
            font-size: 0.75rem;
            border-radius: var(--radius-lg);
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gold);
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            transform: translateX(-50%) translateY(-5px);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-brand,
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
            .sidebar:hover .sidebar-menu a span:not(.emoji) {
                display: inline;
            }
            
            .sidebar:hover .sidebar-menu a {
                justify-content: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            td, th {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--cream);
            border-top-color: var(--saffron);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* RKMS Special Elements */
        .sacred-symbol {
            position: absolute;
            opacity: 0.03;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar - Maroon theme with gold accents -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🚩 RKMS</div>
            <div class="sidebar-brand">HOME <span>ADMIN</span></div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span>📊</span> <span>Dashboard</span></a></li>
            <li><a href="students.php"><span>👨‍🎓</span> <span>Students</span></a></li>
            <li><a href="users.php"><span>👥</span> <span>Users</span></a></li>
            <li><a href="departments.php" class="active"><span>🏢</span> <span>Departments</span></a></li>
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
            <h1>
                <i class="fas fa-building"></i>
                Departments Management
            </h1>
            <div class="topbar-right">
                <div class="export-btn" onclick="exportDepartments()" data-tooltip="Export to CSV">
                    <i class="fas fa-download"></i>
                    Export CSV
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <!-- Stats Cards - Matching image style -->
        <div class="stats-grid">
            
            <div class="stat-card">
                <div class="stat-label">TOTAL DEPARTMENTS</div>
                <div class="stat-number"><?php echo $total_depts; ?></div>
                <div class="stat-footer">
                    <i class="fas fa-building"></i>
                    Academic units
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">WITH HOD</div>
                <div class="stat-number"><?php echo $depts_with_hod; ?></div>
                <div class="stat-footer">
                    <i class="fas fa-user-tie"></i>
                    Assigned heads
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">STUDENTS</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-footer">
                    <i class="fas fa-user-graduate"></i>
                    Enrolled
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">TEACHERS</div>
                <div class="stat-number"><?php echo $total_teachers; ?></div>
                <div class="stat-footer">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Faculty
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="action-buttons">
            <a href="add_department.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                Add New Department
            </a>
            <a href="assign_hod.php" class="btn btn-success">
                <i class="fas fa-user-tie"></i>
                Assign HOD
            </a>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" class="search-input" placeholder="🔍 Search departments by code, name, or HOD..." id="searchInput">
            <select class="search-input" id="filterHOD" style="max-width: 200px;">
                <option value="all">All Departments</option>
                <option value="with_hod">With HOD</option>
                <option value="without_hod">Without HOD</option>
            </select>
        </div>

        <!-- Departments Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building"></i>
                    All Departments
                </h3>
                <span class="card-badge">
                    <i class="fas fa-layer-group"></i>
                    <?php echo $total_depts; ?> total
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="departmentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Short Name</th>
                                <th>Full Name</th>
                                <th>HOD</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($conn)) {
                                $departments = $conn->query("SELECT d.*, u.username as hod_name 
                                                            FROM departments d 
                                                            LEFT JOIN users u ON d.hod_id = u.id 
                                                            ORDER BY d.dept_code");
                                while ($row = $departments->fetch_assoc()): 
                                    $hod_name = $row['hod_name'] ? htmlspecialchars($row['hod_name']) : null;
                            ?>
                            <tr data-dept-id="<?php echo $row['id']; ?>">
                                <td>
                                    <span class="id-badge">#<?php echo str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td>
                                    <span class="dept-code"><?php echo htmlspecialchars($row['dept_code']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['dept_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <?php if ($hod_name): ?>
                                        <span class="hod-badge">
                                            <i class="fas fa-user-check"></i>
                                            <?php echo $hod_name; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="hod-badge empty">
                                            <i class="fas fa-user-slash"></i>
                                            Not Assigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-icons">
                                        <a href="view_department.php?id=<?php echo $row['id']; ?>" class="action-icon" data-tooltip="View Department">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_department.php?id=<?php echo $row['id']; ?>" class="action-icon" data-tooltip="Edit Department">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$hod_name): ?>
                                            <a href="assign_hod.php?dept_id=<?php echo $row['id']; ?>" class="action-icon" data-tooltip="Assign HOD">
                                                <i class="fas fa-user-tie"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="delete_department.php?id=<?php echo $row['id']; ?>" 
                                           class="action-icon delete" 
                                           onclick="return confirm('⚠️ Are you sure you want to delete this department?');"
                                           data-tooltip="Delete Department">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer -->
                <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="color: var(--maroon);">
                        <i class="fas fa-building"></i>
                        Showing <strong><?php echo isset($departments) ? $departments->num_rows : 0; ?></strong> departments
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary btn-sm" onclick="refreshTable()" data-tooltip="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    filterTable();
});

document.getElementById('filterHOD').addEventListener('change', function() {
    filterTable();
});

function filterTable() {
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const hodFilter = document.getElementById('filterHOD').value;
    const rows = document.querySelectorAll('#departmentsTable tbody tr');
    
    rows.forEach(row => {
        let show = true;
        const text = row.textContent.toLowerCase();
        const hodCell = row.cells[4].textContent;
        const hasHOD = !hodCell.includes('Not Assigned');
        
        // Search filter
        if (searchText && !text.includes(searchText)) {
            show = false;
        }
        
        // HOD filter
        if (hodFilter === 'with_hod' && !hasHOD) {
            show = false;
        } else if (hodFilter === 'without_hod' && hasHOD) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// Export to CSV function (matching image)
function exportDepartments() {
    const rows = [];
    const headers = ['ID', 'Code', 'Short Name', 'Full Name', 'HOD'];
    rows.push(headers.join(','));
    
    document.querySelectorAll('#departmentsTable tbody tr').forEach(row => {
        if (row.style.display !== 'none') {
            const rowData = [
                row.cells[0].textContent.replace('#', ''),
                row.cells[1].textContent,
                row.cells[2].textContent,
                row.cells[3].textContent,
                row.cells[4].textContent.replace('Not Assigned', '')
            ];
            rows.push(rowData.join(','));
        }
    });
    
    const csv = rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'departments_export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    // Show success notification
    showNotification('✅ Departments exported successfully!');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.background = 'linear-gradient(135deg, var(--saffron), var(--maroon))';
    notification.style.color = 'var(--gold)';
    notification.style.padding = '1rem 2rem';
    notification.style.borderRadius = '50px';
    notification.style.boxShadow = 'var(--shadow-xl)';
    notification.style.zIndex = '9999';
    notification.style.animation = 'slideIn 0.5s ease';
    notification.style.border = '2px solid var(--gold)';
    notification.innerHTML = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function refreshTable() {
    location.reload();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + F for search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    
    // Ctrl + E for export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportDepartments();
    }
});

// Tooltip initialization
document.querySelectorAll('[data-tooltip]').forEach(element => {
    element.addEventListener('mouseenter', function(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.getAttribute('data-tooltip');
        tooltip.style.position = 'absolute';
        tooltip.style.background = 'var(--maroon)';
        tooltip.style.color = 'var(--gold)';
        tooltip.style.padding = '0.5rem 1rem';
        tooltip.style.borderRadius = 'var(--radius-lg)';
        tooltip.style.fontSize = '0.75rem';
        tooltip.style.zIndex = '1000';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.whiteSpace = 'nowrap';
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

// Animate stats on load
window.addEventListener('load', () => {
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
        const value = stat.textContent;
        stat.textContent = '0';
        
        let start = 0;
        let end = parseInt(value);
    if (isNaN(end)) end = 0; // fallback if non-numeric
        const duration = 1000;
        const increment = end / (duration / 10);
        
        const timer = setInterval(() => {
            start += increment;
            if (start > end) {
                stat.textContent = end;
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(start);
            }
        }, 10);
    });
});

// Add sacred symbols randomly
function addSacredSymbols() {
    const symbols = ['🕉️', '🚩', '🙏', '🔔'];
    for (let i = 0; i < 5; i++) {
        const symbol = document.createElement('div');
        symbol.className = 'sacred-symbol';
        symbol.style.top = Math.random() * 100 + '%';
        symbol.style.left = Math.random() * 100 + '%';
        symbol.style.fontSize = (20 + Math.random() * 40) + 'px';
        symbol.innerHTML = symbols[Math.floor(Math.random() * symbols.length)];
        document.body.appendChild(symbol);
    }
}
// addSacredSymbols();
</script>
</body>
</html>