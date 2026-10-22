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

// Get students with department and user info
$students_stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name, u.email as user_email 
                                FROM students s 
                                LEFT JOIN departments d ON s.department_id = d.id
                                LEFT JOIN users u ON s.user_id = u.id
                                WHERE s.department_id = ? OR ? IS NULL
                                ORDER BY s.semester, s.first_name");
$dept_id = $user['department_id'] ?? null;
$students_stmt->bind_param("ii", $dept_id, $dept_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result;
$students_stmt->close();

// Get statistics
$total_students = $students->num_rows;
$total_male = 0;
$total_female = 0;
$students->data_seek(0);
while ($student = $students->fetch_assoc()) {
    if (($student['gender'] ?? '') == 'male') $total_male++;
    if (($student['gender'] ?? '') == 'female') $total_female++;
}
$students->data_seek(0);

// Get semester distribution
$semester_counts = [];
$students->data_seek(0);
while ($student = $students->fetch_assoc()) {
    $sem = $student['semester'] ?? 1;
    $semester_counts[$sem] = ($semester_counts[$sem] ?? 0) + 1;
}
$students->data_seek(0);

// Get recent activities
$recent_activities = $conn->query("SELECT 'New Student' as action, CONCAT(first_name, ' ', last_name) as target, created_at as date 
                                   FROM students 
                                   ORDER BY created_at DESC 
                                   LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - RKMS HOME Teacher Portal</title>
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

        .student-count {
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

        /* Search and Filter Bar */
        .search-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            border: 1px solid var(--primary-soft);
        }

        .search-input-group {
            flex: 1;
            position: relative;
            min-width: 250px;
        }

        .search-input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
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
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: var(--dark);
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .export-btn {
            background: var(--primary-soft);
            color: var(--primary);
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Students Table */
        .table-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to right, rgba(255, 140, 66, 0.02), transparent);
        }

        .table-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h3 i {
            color: var(--primary);
        }

        .table-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .rows-per-page {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            font-size: 13px;
        }

        .rows-per-page select {
            padding: 5px 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
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
            color: var(--dark);
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
            font-size: 12px;
            color: var(--gray);
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
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

        .action-btn.edit {
            background: rgba(255, 140, 66, 0.1);
            color: var(--primary);
        }

        .action-btn.edit:hover {
            background: var(--gradient);
            color: white;
        }

        .action-btn.message {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .action-btn.message:hover {
            background: var(--success);
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }

        .quick-action-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid var(--primary-soft);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .quick-action-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-soft);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary);
        }

        .quick-action-content {
            flex: 1;
        }

        .quick-action-content h4 {
            color: var(--dark);
            font-size: 15px;
            margin-bottom: 3px;
        }

        .quick-action-content p {
            color: var(--gray);
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
            
            .search-bar {
                flex-direction: column;
            }
            
            .search-input-group {
                width: 100%;
            }
            
            .filter-group {
                width: 100%;
                justify-content: stretch;
            }
            
            .filter-select {
                flex: 1;
            }
            
            .pagination {
                flex-direction: column;
                align-items: center;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
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
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Teacher Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php" class="active"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
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
                    <h1><i class="fas fa-user-graduate"></i> Student List</h1>
                    <span class="student-count">
                        <i class="fas fa-users"></i> 
                        <?php echo $total_students; ?> Students
                    </span>
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
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                        <div class="stat-desc">Enrolled this semester</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-male"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Male Students</div>
                        <div class="stat-value"><?php echo $total_male; ?></div>
                        <div class="stat-desc"><?php echo $total_students > 0 ? round(($total_male / $total_students) * 100) : 0; ?>% of total</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-female"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Female Students</div>
                        <div class="stat-value"><?php echo $total_female; ?></div>
                        <div class="stat-desc"><?php echo $total_students > 0 ? round(($total_female / $total_students) * 100) : 0; ?>% of total</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Semesters</div>
                        <div class="stat-value"><?php echo count($semester_counts); ?></div>
                        <div class="stat-desc">Active semesters</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-bar">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search by name, registration, email..." onkeyup="filterStudents()">
                </div>
                
                <div class="filter-group">
                    <select class="filter-select" id="semesterFilter" onchange="filterStudents()">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <select class="filter-select" id="genderFilter" onchange="filterStudents()">
                        <option value="">All Genders</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    
                    <button class="export-btn" onclick="exportToCSV()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Students Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Student Directory</h3>
                    <div class="table-controls">
                        <div class="rows-per-page">
                            <span>Show</span>
                            <select id="rowsPerPage" onchange="changeRowsPerPage()">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span>entries</span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="students-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Registration No.</th>
                                <th>Contact</th>
                                <th>Semester</th>
                                <th>Department</th>
                                <th>Aadhar Number</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if ($students->num_rows > 0): ?>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <?php 
                                    $initials = strtoupper(substr($student['first_name'] ?? 'S', 0, 1) . substr($student['last_name'] ?? 'T', 0, 1));
                                    $full_name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                                    $reg_no = htmlspecialchars($student['registration_number'] ?? 'N/A');
                                    $email = htmlspecialchars($student['email'] ?? $student['user_email'] ?? 'N/A');
                                    $mobile = htmlspecialchars($student['mobile'] ?? 'N/A');
                                    $semester = intval($student['semester'] ?? 1);
                                    $dept = htmlspecialchars($student['dept_name'] ?? 'N/A');
                                    $aadhar = !empty($student['aadhar_number']) ? '••••••' . substr($student['aadhar_number'], -4) : 'N/A';
                                    $joined = date('d M Y', strtotime($student['created_at']));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar">
                                                    <?php echo $initials; ?>
                                                </div>
                                                <div class="student-details">
                                                    <div class="student-name"><?php echo $full_name; ?></div>
                                                    <div class="student-email"><?php echo $email; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-info"><?php echo $reg_no; ?></span></td>
                                        <td>
                                            <div><i class="fas fa-phone" style="color: var(--primary); width: 20px;"></i> <?php echo $mobile; ?></div>
                                            <div><i class="fas fa-envelope" style="color: var(--primary); width: 20px;"></i> <?php echo $email; ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">Semester <?php echo $semester; ?></span>
                                        </td>
                                        <td><?php echo $dept; ?></td>
                                        <td><?php echo $aadhar; ?></td>
                                        <td>
                                            <div><i class="far fa-calendar" style="color: var(--primary);"></i> <?php echo $joined; ?></div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn view" onclick="viewStudent(<?php echo $student['id']; ?>)" data-tooltip="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn edit" onclick="editStudent(<?php echo $student['id']; ?>)" data-tooltip="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn message" onclick="messageStudent(<?php echo $student['id']; ?>)" data-tooltip="Send Message">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h3>No Students Found</h3>
                                            <p>There are no students in your class yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">
                        Showing 1 to <?php echo min(25, $total_students); ?> of <?php echo $total_students; ?> entries
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

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="quick-action-card" onclick="window.location.href='add_student.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="quick-action-content">
                        <h4>Add New Student</h4>
                        <p>Register a new student</p>
                    </div>
                </div>

                <div class="quick-action-card" onclick="window.location.href='attendance.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-action-content">
                        <h4>Mark Attendance</h4>
                        <p>Take today's attendance</p>
                    </div>
                </div>

                <div class="quick-action-card" onclick="window.location.href='messages.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="quick-action-content">
                        <h4>Send Message</h4>
                        <p>Message all students</p>
                    </div>
                </div>

                <div class="quick-action-card" onclick="generateReport()">
                    <div class="quick-action-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="quick-action-content">
                        <h4>Generate Report</h4>
                        <p>Export student list</p>
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

        // Show loading overlay
        document.getElementById('loadingOverlay').classList.add('active');
        setTimeout(() => {
            document.getElementById('loadingOverlay').classList.remove('active');
        }, 1000);

        // Filter students
        function filterStudents() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const semesterFilter = document.getElementById('semesterFilter').value;
            const genderFilter = document.getElementById('genderFilter').value;
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let row of rows) {
                let show = true;
                
                // Search filter
                if (searchInput) {
                    const text = row.textContent.toLowerCase();
                    if (!text.includes(searchInput)) {
                        show = false;
                    }
                }
                
                // Semester filter (you would need to add data attributes for proper filtering)
                // This is a simplified version
                
                if (show) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Update pagination info
            document.getElementById('paginationInfo').textContent = 
                `Showing 1 to ${visibleCount} of ${visibleCount} entries`;
        }

        // Change rows per page
        function changeRowsPerPage() {
            const rowsPerPage = document.getElementById('rowsPerPage').value;
            // Implement pagination logic here
            console.log('Rows per page:', rowsPerPage);
        }

        // Change page
        function changePage(page) {
            // Implement pagination logic here
            console.log('Change to page:', page);
        }

        // Export to CSV
        function exportToCSV() {
            const table = document.getElementById('studentsTable');
            const rows = table.querySelectorAll('tr');
            const csv = [];
            
            for (let row of rows) {
                const cells = row.querySelectorAll('td, th');
                const rowData = [];
                for (let cell of cells) {
                    rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
                }
                csv.push(rowData.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'students_list.csv';
            a.click();
            window.URL.revokeObjectURL(url);
            
            showToast('CSV file downloaded successfully!');
        }

        // Generate report
        function generateReport() {
            showToast('Generating report...');
            // Implement report generation
            setTimeout(() => {
                exportToCSV();
            }, 1000);
        }

        // Student actions
        function viewStudent(id) {
            window.location.href = `view_student.php?id=${id}`;
        }

        function editStudent(id) {
            window.location.href = `edit_student.php?id=${id}`;
        }

        function messageStudent(id) {
            window.location.href = `message_student.php?id=${id}`;
        }

        // Show toast notification
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F for search focus
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            // Alt + A for add student
            if (e.altKey && e.key === 'a') {
                e.preventDefault();
                window.location.href = 'add_student.php';
            }
            // Alt + E for export
            if (e.altKey && e.key === 'e') {
                e.preventDefault();
                exportToCSV();
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
        document.querySelectorAll('.action-btn, .quick-action-card, .page-btn').forEach(element => {
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