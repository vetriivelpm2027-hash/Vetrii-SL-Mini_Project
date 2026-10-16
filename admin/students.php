<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is authenticated
requireLogin();
// require admin access for this page
requireAdmin();

$success = $success ?? '';
$error = $error ?? '';

// Get statistics
$total_students = 0;
$total_departments = 0;
$new_this_month = 0;

if (isset($conn) && $conn) {
    $total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
    $total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
    $new_this_month = $conn->query("SELECT COUNT(*) as count FROM students WHERE MONTH(created_at) = MONTH(CURDATE())")->fetch_assoc()['count'];
}

// pull students list for directory with proper joins
$students = null;
if (isset($conn) && $conn) {
    $students = $conn->query("SELECT s.*, u.username, d.name as dept_name, d.full_name as dept_fullname 
        FROM students s 
        LEFT JOIN users u ON s.user_id = u.id 
        LEFT JOIN departments d ON s.department_id = d.id
        ORDER BY s.created_at DESC");
}

// Get departments for filter
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - RKMS HOME Admin</title>
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
            --gray-50: #f9fafb;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-soft: linear-gradient(135deg, var(--primary-soft) 0%, #fff 100%);
            --shadow-sm: 0 2px 4px rgba(255, 140, 66, 0.1);
            --shadow-md: 0 4px 6px rgba(255, 140, 66, 0.1);
            --shadow-lg: 0 10px 15px rgba(255, 140, 66, 0.1);
            --shadow-xl: 0 20px 25px rgba(255, 140, 66, 0.15);
            --shadow-2xl: 0 25px 50px -12px rgba(255, 140, 66, 0.25);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
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
            background: linear-gradient(180deg, var(--gray-900) 0%, #1a1a2e 100%);
            color: white;
            box-shadow: var(--shadow-2xl);
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
            background: rgba(255,255,255,0.1);
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
            content: 'RKMS';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 5rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.1);
            pointer-events: none;
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
        .sidebar-menu li:nth-child(7) { animation-delay: 0.7s; }
        .sidebar-menu li:nth-child(8) { animation-delay: 0.8s; }

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
            color: var(--gray-300);
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
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--gradient);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .sidebar-menu a:hover::before,
        .sidebar-menu a.active::before {
            transform: scaleY(1);
        }

        .sidebar-menu a i {
            width: 24px;
            font-size: 18px;
            color: var(--primary);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 140, 66, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, rgba(255, 140, 66, 0.2), transparent);
            box-shadow: var(--shadow-md);
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
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 20px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-info i {
            color: var(--primary);
            font-size: 20px;
        }

        .admin-info-details h4 {
            color: white;
            font-size: 14px;
        }

        .admin-info-details p {
            color: var(--gray-400);
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

        .stats-badge {
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
            background: var(--primary-soft);
        }

        .user-profile:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .user-profile:hover span {
            color: white;
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
            color: var(--gray-500);
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

        /* AI Features */
        .ai-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .ai-card {
            background: var(--gradient);
            padding: 20px;
            border-radius: 15px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            box-shadow: var(--shadow-lg);
        }

        .ai-card:nth-child(2) { animation-delay: 0.2s; }
        .ai-card:nth-child(3) { animation-delay: 0.4s; }
        .ai-card:nth-child(4) { animation-delay: 0.6s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .ai-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-2xl);
        }

        .ai-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .ai-content {
            flex: 1;
        }

        .ai-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .ai-badge {
            background: rgba(255,255,255,0.2);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
        }

        /* Search and Filter */
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
        }

        .search-box {
            flex: 1;
            position: relative;
            min-width: 250px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 12px 35px 12px 15px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 14px;
            color: var(--dark);
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23FF8C42' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .action-btn {
            padding: 12px 25px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Students Card */
        .students-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            margin-bottom: 30px;
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

        .total-badge {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .students-table thead {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        .students-table th {
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

        .students-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--primary-soft);
            color: var(--gray-600);
            font-size: 14px;
        }

        .students-table tbody tr {
            transition: all 0.3s ease;
        }

        .students-table tbody tr:hover {
            background: var(--primary-soft);
            transform: scale(1.01);
            box-shadow: var(--shadow-md);
        }

        /* Student Info */
        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-avatar {
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

        .student-details {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .student-email {
            font-size: 11px;
            color: var(--gray-500);
        }

        /* Badges */
        .reg-badge {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary-dark);
            background: var(--primary-soft);
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
            font-size: 12px;
        }

        .dept-badge {
            background: var(--gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .aadhar-mask {
            font-family: 'Courier New', monospace;
            color: var(--gray-500);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-icon {
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

        .action-icon.view {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }

        .action-icon.view:hover {
            background: var(--info);
            color: white;
            transform: translateY(-2px);
        }

        .action-icon.edit {
            background: rgba(255, 140, 66, 0.1);
            color: var(--primary);
        }

        .action-icon.edit:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
        }

        .action-icon.delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .action-icon.delete:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        /* Table Footer */
        .table-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .footer-info {
            color: var(--gray-500);
            font-size: 14px;
        }

        .footer-actions {
            display: flex;
            gap: 10px;
        }

        .footer-btn {
            padding: 10px 20px;
            background: var(--primary-soft);
            border: none;
            border-radius: 10px;
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-btn:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination {
            padding: 20px 25px;
            border-top: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 2px solid var(--primary-soft);
            background: white;
            color: var(--gray-500);
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
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 20px;
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
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-brand,
            .sidebar-menu a span:not(.fa, .fas, .far) {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar-menu a i {
                margin: 0;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .sidebar:hover {
                width: 300px;
            }
            
            .sidebar:hover .sidebar-brand,
            .sidebar:hover .sidebar-menu a span {
                display: inline;
            }
            
            .sidebar:hover .sidebar-menu a {
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
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
            
            .ai-features {
                grid-template-columns: 1fr;
            }
            
            .search-section {
                flex-direction: column;
            }
            
            .search-box {
                width: 100%;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-select {
                flex: 1;
            }
            
            .table-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .footer-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .footer-btn {
                width: 100%;
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

        /* Print Styles */
        @media print {
            .sidebar, .top-bar, .ai-features, .search-section, .action-buttons, .footer-actions, .pagination {
                display: none;
            }
            
            .main-content {
                margin: 0;
                padding: 0;
            }
            
            .students-table {
                border: 1px solid black;
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
        <p style="color: var(--primary); font-weight: 600;">Loading student data...</p>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Admin Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="students.php" class="active"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="departments.php"><i class="fas fa-building"></i> <span>Departments</span></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
                <li><a href="export.php"><i class="fas fa-download"></i> <span>Export Data</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="admin-info">
                    <i class="fas fa-shield-alt"></i>
                    <div class="admin-info-details">
                        <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                        <p>Administrator</p>
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
                    <h1><i class="fas fa-user-graduate"></i> Students Management</h1>
                    <span class="stats-badge">
                        <i class="fas fa-users"></i> <?php echo $total_students; ?> Total
                    </span>
                </div>
                
                <div class="user-profile" onclick="window.location.href='profile.php'">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> Active enrollment
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-value"><?php echo $total_departments; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-layer-group"></i> Active
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="stat-label">New This Month</div>
                    <div class="stat-value"><?php echo $new_this_month; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> +12% vs last month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-label">Completion Rate</div>
                    <div class="stat-value">94%</div>
                    <div class="stat-trend">
                        <i class="fas fa-check-circle"></i> Profile complete
                    </div>
                </div>
            </div>

            <!-- AI Features Section -->
            <div class="ai-features">
                <div class="ai-card" onclick="analyzeData('students')" data-tooltip="AI-powered analysis">
                    <div class="ai-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="ai-content">
                        <div class="ai-title">Student Analytics</div>
                        <span class="ai-badge">AI Active</span>
                    </div>
                </div>
                <div class="ai-card" onclick="predictPerformance()" data-tooltip="Predictive insights">
                    <div class="ai-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="ai-content">
                        <div class="ai-title">Performance Prediction</div>
                        <span class="ai-badge">Beta</span>
                    </div>
                </div>
                <div class="ai-card" onclick="generateReports()" data-tooltip="Automated reporting">
                    <div class="ai-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="ai-content">
                        <div class="ai-title">Smart Reports</div>
                        <span class="ai-badge">New</span>
                    </div>
                </div>
                <div class="ai-card" onclick="attendanceAI()" data-tooltip="Face recognition">
                    <div class="ai-icon">
                        <i class="fas fa-face-smile"></i>
                    </div>
                    <div class="ai-content">
                        <div class="ai-title">Attendance AI</div>
                        <span class="ai-badge">Pro</span>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, registration, email, or department..." onkeyup="filterTable()">
                </div>
                
                <div class="filter-group">
                    <select class="filter-select" id="departmentFilter" onchange="filterTable()">
                        <option value="">All Departments</option>
                        <?php 
                        if ($departments) {
                            while ($dept = $departments->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($dept['name']); ?>">
                                <?php echo htmlspecialchars($dept['full_name']); ?>
                            </option>
                        <?php 
                            endwhile;
                        } 
                        ?>
                    </select>
                    
                    <select class="filter-select" id="sortFilter" onchange="sortTable()">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="name_asc">Name A-Z</option>
                        <option value="name_desc">Name Z-A</option>
                    </select>
                    
                    <button class="action-btn" onclick="addNewStudent()">
                        <i class="fas fa-plus"></i> Add Student
                    </button>
                </div>
            </div>

            <!-- Students Card -->
            <div class="students-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Student Directory</h3>
                    <span class="total-badge">
                        <i class="fas fa-users"></i> <?php echo $students ? $students->num_rows : 0; ?> Records
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="students-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Registration No.</th>
                                <th>Department</th>
                                <th>Username</th>
                                <th>Father's Name</th>
                                <th>Mother's Name</th>
                                <th>Aadhar</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if ($students && $students->num_rows > 0): ?>
                                <?php while ($row = $students->fetch_assoc()): ?>
                                    <?php 
                                    $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                                    $initials = strtoupper(substr($row['first_name'] ?? 'S', 0, 1) . substr($row['last_name'] ?? 'T', 0, 1));
                                    $email = htmlspecialchars($row['email'] ?? 'N/A');
                                    $reg_no = htmlspecialchars($row['registration_number'] ?? 'N/A');
                                    $dept = htmlspecialchars($row['dept_fullname'] ?? $row['dept_name'] ?? 'N/A');
                                    $father = htmlspecialchars($row['father_name'] ?? 'N/A');
                                    $mother = htmlspecialchars($row['mother_name'] ?? 'N/A');
                                    $aadhar = !empty($row['aadhar_number']) ? '••••••' . substr($row['aadhar_number'], -4) : 'N/A';
                                    $joined = date('d M Y', strtotime($row['created_at']));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar">
                                                    <?php echo $initials ?: 'ST'; ?>
                                                </div>
                                                <div class="student-details">
                                                    <div class="student-name"><?php echo $full_name; ?></div>
                                                    <div class="student-email"><?php echo $email; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="reg-badge"><?php echo $reg_no; ?></span>
                                        </td>
                                        <td>
                                            <span class="dept-badge"><?php echo $dept; ?></span>
                                        </td>
                                        <td>
                                            <i class="fas fa-at" style="color: var(--primary);"></i> 
                                            <?php echo htmlspecialchars($row['username'] ?? 'N/A'); ?>
                                        </td>
                                        <td><?php echo $father; ?></td>
                                        <td><?php echo $mother; ?></td>
                                        <td>
                                            <span class="aadhar-mask" data-tooltip="<?php echo htmlspecialchars($row['aadhar_number'] ?? ''); ?>">
                                                <?php echo $aadhar; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="far fa-calendar" style="color: var(--primary);"></i> 
                                            <?php echo $joined; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-icon view" onclick="viewStudent(<?php echo $row['id']; ?>)" data-tooltip="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-icon edit" onclick="editStudent(<?php echo $row['id']; ?>)" data-tooltip="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-icon delete" onclick="deleteStudent(<?php echo $row['id']; ?>)" data-tooltip="Delete Student">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h3>No Students Found</h3>
                                            <p>There are no students in the system yet.</p>
                                            <button class="action-btn" onclick="addNewStudent()">
                                                <i class="fas fa-plus"></i> Add Your First Student
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer -->
                <div class="table-footer">
                    <div class="footer-info">
                        <i class="fas fa-database" style="color: var(--primary);"></i> 
                        Showing <strong><?php echo $students ? $students->num_rows : 0; ?></strong> students
                    </div>
                    <div class="footer-actions">
                        <button class="footer-btn" onclick="exportToCSV()">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button class="footer-btn" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button class="footer-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <button class="page-btn" onclick="changePage('prev')"><i class="fas fa-chevron-left"></i></button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                    <button class="page-btn" onclick="changePage('next')"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Show loading overlay
        function showLoading(show) {
            document.getElementById('loadingOverlay').classList.toggle('active', show);
        }

        // Filter table function
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const departmentFilter = document.getElementById('departmentFilter').value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let row of rows) {
                let show = true;
                const text = row.textContent.toLowerCase();
                
                // Search filter
                if (searchInput && !text.includes(searchInput)) {
                    show = false;
                }
                
                // Department filter
                if (departmentFilter) {
                    const deptCell = row.cells[2].textContent.toLowerCase();
                    if (!deptCell.includes(departmentFilter)) {
                        show = false;
                    }
                }
                
                if (show) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Update footer info
            document.querySelector('.footer-info').innerHTML = 
                `<i class="fas fa-database" style="color: var(--primary);"></i> Showing <strong>${visibleCount}</strong> students`;
        }

        // Sort table function
        function sortTable() {
            const sortBy = document.getElementById('sortFilter').value;
            const table = document.getElementById('studentsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            
            rows.sort((a, b) => {
                const nameA = a.cells[0].textContent.trim();
                const nameB = b.cells[0].textContent.trim();
                const dateA = new Date(a.cells[7].textContent.trim());
                const dateB = new Date(b.cells[7].textContent.trim());
                
                switch(sortBy) {
                    case 'name_asc':
                        return nameA.localeCompare(nameB);
                    case 'name_desc':
                        return nameB.localeCompare(nameA);
                    case 'newest':
                        return dateB - dateA;
                    case 'oldest':
                        return dateA - dateB;
                    default:
                        return 0;
                }
            });
            
            // Reorder rows
            rows.forEach(row => tbody.appendChild(row));
        }

        // Pagination functions
        function changePage(direction) {
            // Implement pagination logic
            console.log('Change page:', direction);
            showToast(`Loading page ${direction}...`, 'info');
        }

        // Student actions
        function viewStudent(id) {
            window.location.href = `view_student.php?id=${id}`;
        }

        function editStudent(id) {
            window.location.href = `edit_student.php?id=${id}`;
        }

        function deleteStudent(id) {
            if (confirm('⚠️ Are you sure you want to delete this student? This action cannot be undone.')) {
                showLoading(true);
                window.location.href = `delete_student.php?id=${id}`;
            }
        }

        function addNewStudent() {
            window.location.href = 'add_student.php';
        }

        // AI feature functions
        function analyzeData(type) {
            showToast(`🤖 AI Analysis started for ${type}...`, 'info');
            setTimeout(() => {
                showToast('✅ Analysis complete! Check reports section.', 'success');
            }, 2000);
        }

        function predictPerformance() {
            showToast('📊 Generating performance predictions...', 'info');
        }

        function generateReports() {
            showToast('📈 Generating smart reports...', 'info');
        }

        function attendanceAI() {
            showToast('👁️ Starting attendance AI module...', 'info');
        }

        // Export functions
        function exportToCSV() {
            let csv = [];
            let rows = document.querySelectorAll('#studentsTable tr');
            
            for (let row of rows) {
                let cells = row.querySelectorAll('td, th');
                let rowText = [];
                for (let cell of cells) {
                    // Clean text content (remove icons, etc.)
                    let text = cell.textContent.trim().replace(/[^\w\s@.-]/g, '');
                    rowText.push('"' + text + '"');
                }
                csv.push(rowText.join(','));
            }
            
            let csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            let downloadLink = document.createElement('a');
            downloadLink.download = 'students_export_' + new Date().toISOString().slice(0,10) + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            showToast('✅ CSV file downloaded successfully!', 'success');
        }

        function exportToPDF() {
            showToast('📄 Preparing PDF export...', 'info');
            setTimeout(() => {
                window.print();
            }, 1000);
        }

        // Toast notification
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
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '10px';
            
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
            toast.innerHTML = `${icon} ${message}`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F for search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            // Ctrl + A for add new
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                addNewStudent();
            }
            // Ctrl + E for export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportToCSV();
            }
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 1024) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading overlay after page load
            setTimeout(() => {
                showLoading(false);
            }, 1000);
            
            // Add animation to table rows
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            document.querySelectorAll('#studentsTable tbody tr').forEach(row => {
                row.style.opacity = 0;
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'all 0.5s ease';
                observer.observe(row);
            });
        });

        // Ripple effect for buttons
        document.querySelectorAll('.action-icon, .footer-btn, .page-btn, .ai-card').forEach(element => {
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
        document.head.appendChild(style);
    </script>
</body>
</html>