<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Get student info using prepared statement
$user_id = $_SESSION['user_id'];
$student_stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name, d.dept_code as department_code, u.email as user_email 
                               FROM students s
                               LEFT JOIN departments d ON s.department_id = d.id
                               LEFT JOIN users u ON s.user_id = u.id
                               WHERE s.user_id = ?");
if (!$student_stmt) {
    die("Prepare failed: " . $conn->error);
}
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
if ($student_stmt->error) {
    die("Execute failed: " . $student_stmt->error);
}
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();
$student_stmt->close();

if (!$student) {
    header("Location: ../unauthorized.php");
    exit;
}

// Get activities using prepared statement
$activities_stmt = $conn->prepare("SELECT a.* FROM activities a 
                           JOIN student_activities sa ON a.id = sa.activity_id 
                           WHERE sa.student_id = ?");
$activities_stmt->bind_param("i", $student['id']);
$activities_stmt->execute();
$activities_result = $activities_stmt->get_result();
$activities = $activities_result;
$activities_stmt->close();

// Get sports using prepared statement
$sports_stmt = $conn->prepare("SELECT s.* FROM sports s 
                       JOIN student_sports ss ON s.id = ss.sport_id 
                       WHERE ss.student_id = ?");
$sports_stmt->bind_param("i", $student['id']);
$sports_stmt->execute();
$sports_result = $sports_stmt->get_result();
$sports = $sports_result;
$sports_stmt->close();

// Get attendance statistics
$attendance_stmt = $conn->prepare("SELECT 
                                   COUNT(*) as total_days,
                                   SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
                                   FROM attendance 
                                   WHERE student_id = ?");
$attendance_stmt->bind_param("i", $student['id']);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance = $attendance_result->fetch_assoc();
$attendance_percentage = $attendance['total_days'] > 0 ? round(($attendance['present_days'] / $attendance['total_days']) * 100) : 0;
$attendance_stmt->close();

// Get CGPA
$cgpa_stmt = $conn->prepare("SELECT ROUND(AVG(cgpa), 2) as current_cgpa FROM results WHERE student_id = ?");
if ($cgpa_stmt) {
    $cgpa_stmt->bind_param("i", $student['id']);
    $cgpa_stmt->execute();
    $cgpa_result = $cgpa_stmt->get_result();
    $cgpa_data = $cgpa_result ? $cgpa_result->fetch_assoc() : null;
    $current_cgpa = $cgpa_data['current_cgpa'] ?? '0.00';
    $cgpa_stmt->close();
} else {
    // Could not prepare CGPA query, maybe table doesn't exist
    error_log("CGPA query prepare failed: " . $conn->error);
    $current_cgpa = '0.00';
}

// Calculate age from DOB
$age = '';
if (!empty($student['date_of_birth'])) {
    $dob = new DateTime($student['date_of_birth']);
    $now = new DateTime();
    $age = $dob->diff($now)->y;
}

// Format registration number for display
$reg_no = $student['registration_number'] ?? '';
$formatted_reg = substr($reg_no, 0, 4) . ' ' . substr($reg_no, 4, 4) . ' ' . substr($reg_no, 8, 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RKMS HOME Student Portal</title>
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
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-desc {
            font-size: 12px;
            color: var(--gray);
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            margin-bottom: 30px;
            animation: fadeInUp 0.5s ease 0.2s both;
        }

        .profile-cover {
            height: 150px;
            background: var(--gradient);
            position: relative;
            overflow: hidden;
        }

        .profile-cover::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .profile-header {
            padding: 0 30px 30px;
            margin-top: -60px;
            position: relative;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            padding: 5px;
            box-shadow: var(--shadow-xl);
            position: relative;
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
        }

        .profile-pic .default-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            border: 4px solid white;
        }

        .profile-pic-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .profile-pic-overlay:hover {
            transform: scale(1.1);
            background: var(--primary-dark);
        }

        .profile-pic-overlay a {
            color: white;
            text-decoration: none;
        }

        .profile-info {
            flex: 1;
            padding-top: 30px;
        }

        .profile-info h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .profile-info .badge {
            display: inline-block;
            padding: 5px 15px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .profile-info .reg-number {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info .reg-number i {
            color: var(--primary);
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease 0.3s both;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-soft);
        }

        .info-card-header i {
            font-size: 30px;
            color: var(--primary);
        }

        .info-card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px dashed var(--primary-soft);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 140px;
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .info-value {
            flex: 1;
            color: var(--dark);
            font-weight: 600;
        }

        .info-value i {
            color: var(--primary);
            margin-right: 5px;
        }

        /* Tags Grid */
        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .tag {
            background: var(--primary-soft);
            padding: 12px 15px;
            border-radius: 12px;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .tag:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .tag i {
            font-size: 16px;
        }

        .tag-empty {
            background: var(--primary-soft);
            padding: 20px;
            border-radius: 12px;
            color: var(--gray);
            text-align: center;
            font-style: italic;
            border: 2px dashed var(--primary-light);
        }

        /* Buttons */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            transform: translateY(-2px);
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
            transform: translateY(-2px);
        }

        .btn-edit-pic {
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Progress Bar */
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
            font-size: 12px;
        }

        .progress-bar {
            height: 8px;
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
            
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-pic {
                margin: 0 auto;
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Document Card */
        .document-card {
            background: var(--primary-soft);
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .document-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary);
        }

        .document-info {
            flex: 1;
        }

        .document-info h4 {
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .document-info p {
            color: var(--gray);
            font-size: 12px;
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

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Student Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="message_teacher.php"><i class="fas fa-paper-plane"></i> Message Teacher</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shield-alt" style="color: var(--primary);"></i>
                    <span style="color: var(--gray); font-size: 12px;">Secure Profile • RKMS HOME</span>
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
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
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

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">CGPA</div>
                        <div class="stat-value"><?php echo $current_cgpa; ?></div>
                        <div class="stat-desc">Current Semester</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Attendance</div>
                        <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                        <div class="stat-desc"><?php echo $attendance['present_days'] ?? 0; ?>/<?php echo $attendance['total_days'] ?? 0; ?> days</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Sports</div>
                        <div class="stat-value"><?php echo $sports->num_rows; ?></div>
                        <div class="stat-desc">Enrolled</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Activities</div>
                        <div class="stat-value"><?php echo $activities->num_rows; ?></div>
                        <div class="stat-desc">Clubs & Activities</div>
                    </div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-header">
                    <div class="profile-pic">
                        <?php if (!empty($student['profile_pic']) && file_exists("../uploads/profile_pic/" . $student['profile_pic'])): ?>
                            <img src="../uploads/profile_pic/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <div class="default-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        <?php endif; ?>
                        <div class="profile-pic-overlay" data-tooltip="Change Photo">
                            <a href="edit_profile.php"><i class="fas fa-camera"></i></a>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                        <div class="badge">
                            <i class="fas fa-graduation-cap"></i> 
                            <?php echo htmlspecialchars($student['dept_name'] ?? 'Department'); ?>
                        </div>
                        <div class="reg-number">
                            <i class="fas fa-id-card"></i>
                            <?php echo htmlspecialchars($formatted_reg); ?>
                        </div>
                        
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                            <a href="message_teacher.php" class="btn btn-secondary">
                                <i class="fas fa-paper-plane"></i> Message Teacher
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Grid -->
            <div class="info-grid">
                <!-- Personal Details -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-user-circle"></i>
                        <h3>Personal Details</h3>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user"></i> First Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['first_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user"></i> Last Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['last_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar"></i> Date of Birth</span>
                        <span class="info-value">
                            <?php echo !empty($student['date_of_birth']) ? date('d M Y', strtotime($student['date_of_birth'])) : 'N/A'; ?>
                            <?php if ($age): ?><small style="color: var(--gray);"> (<?php echo $age; ?> years)</small><?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-tint"></i> Blood Group</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['blood_group'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row" style="display: none;">
                        <span class="info-label"><i class="fas fa-venus-mars"></i> Gender</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-address-card"></i>
                        <h3>Contact Information</h3>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['email'] ?? $student['user_email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-phone"></i> Mobile</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['mobile'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-phone-alt"></i> Emergency</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['emergency_contact'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Family Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-users"></i>
                        <h3>Family Details</h3>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-male"></i> Father's Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-female"></i> Mother's Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Academic Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Academic Details</h3>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-building"></i> Department</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-layer-group"></i> Semester</span>
                        <span class="info-value"><?php echo intval($student['semester'] ?? 1); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-id-card"></i> Aadhar Number</span>
                        <span class="info-value">
                            <?php 
                            if (!empty($student['aadhar_number'])) {
                                echo '••••••' . substr($student['aadhar_number'], -4);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sports & Activities Grid -->
            <div class="info-grid">
                <!-- Sports Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-futbol"></i>
                        <h3>Sports Enrolled</h3>
                    </div>
                    <div class="tags-grid">
                        <?php if ($sports && $sports->num_rows > 0): ?>
                            <?php $sports->data_seek(0); ?>
                            <?php while ($sport = $sports->fetch_assoc()): ?>
                                <div class="tag">
                                    <i class="fas fa-futbol"></i>
                                    <?php echo htmlspecialchars($sport['sport_name']); ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="tag-empty">
                                <i class="fas fa-info-circle"></i> No sports selected yet
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="edit_profile.php" style="display: block; text-align: center; margin-top: 20px; color: var(--primary); text-decoration: none;">
                        <i class="fas fa-plus-circle"></i> Update Sports
                    </a>
                </div>

                <!-- Activities Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-star"></i>
                        <h3>Activities & Clubs</h3>
                    </div>
                    <div class="tags-grid">
                        <?php if ($activities && $activities->num_rows > 0): ?>
                            <?php $activities->data_seek(0); ?>
                            <?php while ($activity = $activities->fetch_assoc()): ?>
                                <div class="tag">
                                    <i class="fas fa-star"></i>
                                    <?php echo htmlspecialchars($activity['activity_name']); ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="tag-empty">
                                <i class="fas fa-info-circle"></i> No activities selected yet
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="edit_profile.php" style="display: block; text-align: center; margin-top: 20px; color: var(--primary); text-decoration: none;">
                        <i class="fas fa-plus-circle"></i> Update Activities
                    </a>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="info-card" style="margin-top: 20px;">
                <div class="info-card-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>Documents & Media</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <!-- Profile Video -->
                    <div class="document-card">
                        <div class="document-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="document-info">
                            <h4>Promotional Video</h4>
                            <?php if (!empty($student['video_file'])): ?>
                                <p><i class="fas fa-check-circle" style="color: var(--success);"></i> Uploaded</p>
                            <?php else: ?>
                                <p><i class="fas fa-times-circle" style="color: var(--gray);"></i> Not uploaded</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ID Card -->
                    <div class="document-card">
                        <div class="document-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="document-info">
                            <h4>Student ID Card</h4>
                            <p><i class="fas fa-check-circle" style="color: var(--success);"></i> Active</p>
                        </div>
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

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Add active class to current menu item
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });

        // Print profile
        function printProfile() {
            window.print();
        }

        // Download as PDF (would need additional library for actual PDF generation)
        function downloadPDF() {
            alert('PDF download feature will be available soon!');
        }

        // Share profile
        function shareProfile() {
            if (navigator.share) {
                navigator.share({
                    title: 'Student Profile',
                    text: 'Check out my student profile at RKMS HOME',
                    url: window.location.href,
                });
            } else {
                alert('Share feature not supported in this browser');
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + E to edit profile
            if (e.altKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = 'edit_profile.php';
            }
            // Alt + P to print
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                printProfile();
            }
        });

        // Add ripple effect to cards
        document.querySelectorAll('.info-card, .stat-card, .document-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('a')) {
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
                }
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
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.background = type === 'success' ? 'var(--success)' : 'var(--primary)';
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

        // Welcome message (only show once per session)
        <?php if (!isset($_COOKIE['profile_welcome'])): ?>
        setTimeout(() => {
            showToast('Welcome to your profile, <?php echo addslashes($student['first_name']); ?>! 👋');
        }, 1500);
        
        // Set cookie to not show again
        document.cookie = "profile_welcome=1; path=/";
        <?php endif; ?>
    </script>
</body>
</html>