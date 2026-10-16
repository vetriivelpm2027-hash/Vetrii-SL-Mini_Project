<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, redirect to appropriate dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'secretary':
            header("Location: secretary/dashboard.php");
            break;
        case 'principal':
            header("Location: principal/dashboard.php");
            break;
        case 'hod':
            header("Location: hod/dashboard.php");
            break;
        case 'class_teacher':
            header("Location: teacher/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'login') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $selected_role = $_POST['selected_role'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password';
        } else {
            $user = loginUser($username, $password);
            
            if ($user) {
                // Verify role matches selected role
                if ($selected_role && $user['role'] != $selected_role) {
                    $error = 'Invalid role selection. Please select correct role.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['department'] = $user['department'] ?? '';
                    
                    // Send login email notification
                    $to = 'admin@rksmshome.edu';
                    $subject = 'Login Notification - RKSMSHOME';
                    $message = "User {$user['username']} ({$user['role']}) logged in to RKSMSHOME at " . date('Y-m-d H:i:s');
                    
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: admin/dashboard.php");
                            break;
                        case 'secretary':
                            header("Location: secretary/dashboard.php");
                            break;
                        case 'principal':
                            header("Location: principal/dashboard.php");
                            break;
                        case 'hod':
                            header("Location: hod/dashboard.php");
                            break;
                        case 'class_teacher':
                            header("Location: teacher/dashboard.php");
                            break;
                        case 'student':
                            header("Location: student/dashboard.php");
                            break;
                    }
                    exit();
                }
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}

// Get system statistics
$total_students = 0;
$total_teachers = 0;
$total_departments = 0;

if (isset($conn) && $conn) {
    $total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;
    $total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'] ?? 0;
    $total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKSMSHOME - Ramakrishna Mission Student Information System</title>
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
            --radius-3xl: 2rem;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
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

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 140, 66, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 140, 66, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.1), rgba(255, 107, 53, 0.1));
            filter: blur(60px);
            animation: float 20s infinite;
        }

        .bg-circle:nth-child(1) {
            width: 600px;
            height: 600px;
            top: -200px;
            left: -200px;
        }

        .bg-circle:nth-child(2) {
            width: 500px;
            height: 500px;
            bottom: -150px;
            right: -150px;
            animation-delay: -5s;
        }

        .bg-circle:nth-child(3) {
            width: 400px;
            height: 400px;
            top: 40%;
            left: 60%;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(50px, 50px) rotate(120deg); }
            66% { transform: translate(-50px, -50px) rotate(240deg); }
        }

        /* Main Container */
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        .login-container {
            width: 100%;
            max-width: 1400px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-3xl);
            box-shadow: var(--shadow-2xl);
            overflow: hidden;
            border: 1px solid rgba(255, 140, 66, 0.2);
            animation: slideIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Left Panel - Branding & Info */
        .brand-panel {
            background: var(--gradient);
            padding: 60px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .brand-content {
            position: relative;
            z-index: 2;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 50px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--primary);
            transform: rotate(-5deg);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-xl);
        }

        .logo-icon:hover {
            transform: rotate(0deg) scale(1.1);
        }

        .logo-text h1 {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .welcome-text {
            margin-bottom: 40px;
        }

        .welcome-text h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .welcome-text p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-radius: var(--radius-xl);
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            background: rgba(255, 255, 255, 0.25);
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }

        /* Vivekananda Quote */
        .quote-box {
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--radius-xl);
            padding: 30px;
            margin-top: auto;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .quote-box::before {
            content: '"';
            position: absolute;
            top: 10px;
            left: 20px;
            font-size: 80px;
            color: rgba(255, 255, 255, 0.2);
            font-family: serif;
        }

        .quote-box::after {
            content: '"';
            position: absolute;
            bottom: 0;
            right: 20px;
            font-size: 80px;
            color: rgba(255, 255, 255, 0.2);
            font-family: serif;
        }

        .quote-text {
            font-size: 18px;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }

        .quote-author {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        /* Right Panel - Login Form */
        .login-panel {
            padding: 60px 50px;
            background: white;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--gray-500);
            font-size: 14px;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fff5f5, #ffe5e5);
            border-left-color: var(--danger);
            color: var(--danger);
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fff4, #dcfce7);
            border-left-color: var(--success);
            color: var(--success);
        }

        .alert i {
            font-size: 20px;
        }

        /* Role Selector */
        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }

        .role-btn {
            padding: 12px 10px;
            border: 2px solid var(--gray-200);
            background: white;
            border-radius: var(--radius-lg);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .role-btn i {
            font-size: 14px;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .role-btn::before {
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

        .role-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .role-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .role-btn:hover i {
            color: var(--primary-dark);
        }

        .role-btn.active {
            background: var(--gradient);
            border-color: transparent;
            color: white;
            box-shadow: var(--shadow-lg);
        }

        .role-btn.active i {
            color: white;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 8px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .input-group input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-xl);
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
        }

        .input-group input:focus + i {
            color: var(--primary);
        }

        /* Password Strength */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            background: var(--gradient);
            transition: width 0.3s ease;
        }

        .strength-text {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 5px;
            text-align: right;
        }

        /* Remember Me & Forgot Password */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .remember-me span {
            font-size: 14px;
            color: var(--gray-600);
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 16px;
            background: var(--gradient);
            border: none;
            border-radius: var(--radius-xl);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn::before {
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

        .login-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .login-btn:hover i {
            transform: translateX(5px);
        }

        /* Register Link */
        .register-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }

        .register-section p {
            color: var(--gray-600);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .register-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .register-link:hover {
            color: var(--primary-dark);
            gap: 10px;
        }

        /* Features List */
        .features-list {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .feature-item i {
            color: var(--primary);
            font-size: 16px;
        }

        .feature-item span {
            color: var(--gray-700);
            font-size: 13px;
            font-weight: 500;
        }

        /* Footer */
        .login-footer {
            margin-top: 40px;
            text-align: center;
            color: var(--gray-500);
            font-size: 12px;
        }

        .login-footer p {
            margin: 3px 0;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
            gap: 20px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid var(--primary-soft);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: var(--primary);
            font-weight: 600;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 600px;
            }
            
            .brand-panel {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .login-panel {
                padding: 40px 30px;
            }
            
            .role-selector {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .features-list {
                grid-template-columns: 1fr;
            }
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
            padding: 8px 12px;
            background: var(--dark);
            color: white;
            font-size: 12px;
            border-radius: var(--radius-lg);
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: var(--shadow-lg);
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            transform: translateX(-50%) translateY(-5px);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-grid"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Accessing RKSMSHOME Portal...</div>
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <!-- Left Panel - Branding & Info -->
            <div class="brand-panel">
                <div class="brand-content">
                    <div class="logo-container">
                        <div class="logo-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="logo-text">
                            <h1>RKSMSHOME</h1>
                            <p>Ramakrishna Mission Student System</p>
                        </div>
                    </div>

                    <div class="welcome-text">
                        <h2>Welcome to RKSMSHOME</h2>
                        <p>Your comprehensive student information system designed to streamline academic management and enhance educational experience.</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card" data-tooltip="Total enrolled students">
                            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="stat-number"><?php echo number_format($total_students); ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                        <div class="stat-card" data-tooltip="Total faculty members">
                            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="stat-number"><?php echo number_format($total_teachers); ?></div>
                            <div class="stat-label">Teachers</div>
                        </div>
                        <div class="stat-card" data-tooltip="Academic departments">
                            <div class="stat-icon"><i class="fas fa-building"></i></div>
                            <div class="stat-number"><?php echo number_format($total_departments); ?></div>
                            <div class="stat-label">Departments</div>
                        </div>
                    </div>

                    <!-- Vivekananda Quote -->
                    <div class="quote-box">
                        <div class="quote-text">
                            "Arise, awake, and stop not until the goal is reached."
                        </div>
                        <div class="quote-author">
                            — Swami Vivekananda
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Login Form -->
            <div class="login-panel">
                <div class="login-header">
                    <h2>Secure Login</h2>
                    <p>Enter your credentials to access the portal</p>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="selected_role" id="selectedRole" value="">

                    <!-- Role Selection -->
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Select Your Role</label>
                        <div class="role-selector">
                            <button type="button" class="role-btn" data-role="admin" onclick="selectRole('admin')">
                                <i class="fas fa-crown"></i> Admin
                            </button>
                            <button type="button" class="role-btn" data-role="secretary" onclick="selectRole('secretary')">
                                <i class="fas fa-user-tie"></i> Secretary
                            </button>
                            <button type="button" class="role-btn" data-role="principal" onclick="selectRole('principal')">
                                <i class="fas fa-chalkboard-teacher"></i> Principal
                            </button>
                            <button type="button" class="role-btn" data-role="hod" onclick="selectRole('hod')">
                                <i class="fas fa-users-cog"></i> HOD
                            </button>
                            <button type="button" class="role-btn" data-role="class_teacher" onclick="selectRole('class_teacher')">
                                <i class="fas fa-chalkboard"></i> Teacher
                            </button>
                            <button type="button" class="role-btn" data-role="student" onclick="selectRole('student')">
                                <i class="fas fa-user-graduate"></i> Student
                            </button>
                        </div>
                    </div>

                    <!-- Username Field -->
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Enter your RKSMSHOME username" autocomplete="username">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter your RKSMSHOME password" autocomplete="current-password"
                                   onkeyup="checkPasswordStrength(this.value)">
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> 
                            <span>Remember me</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-link">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="login-btn" onclick="showLoading()">
                        <span>Access RKSMSHOME</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <!-- Register Link -->
                    <div class="register-section">
                        <p>New to RKSMSHOME?</p>
                        <a href="register.php" class="register-link">
                            Create Student Account <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </form>

                <!-- System Features -->
                <div class="features-list">
                    <div class="feature-item" data-tooltip="Track attendance in real-time">
                        <i class="fas fa-calendar-check"></i>
                        <span>Attendance Tracking</span>
                    </div>
                    <div class="feature-item" data-tooltip="View academic results">
                        <i class="fas fa-chart-line"></i>
                        <span>Results & Reports</span>
                    </div>
                    <div class="feature-item" data-tooltip="Send and receive messages">
                        <i class="fas fa-envelope"></i>
                        <span>Messaging System</span>
                    </div>
                    <div class="feature-item" data-tooltip="Manage student profiles">
                        <i class="fas fa-id-card"></i>
                        <span>Profile Management</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="login-footer">
                    <p>© 2026 RKSMSHOME - Ramakrishna Mission Polytechnic College</p>
                    <p>Mylapore, Chennai 600004 | ISO 9001:2025 Certified</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Role selection
        function selectRole(role) {
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            event.target.closest('.role-btn').classList.add('active');
            document.getElementById('selectedRole').value = role;
        }

        // Show loading overlay
        function showLoading() {
            const form = document.getElementById('loginForm');
            const role = document.getElementById('selectedRole').value;
            
            if (!role) {
                event.preventDefault();
                showAlert('❌ Please select your role');
                return false;
            }
            
            if (form.checkValidity()) {
                document.getElementById('loadingOverlay').classList.add('active');
                
                setTimeout(() => {
                    form.submit();
                }, 1500);
                return false;
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            if (password.match(/[$@#&!]+/)) strength += 25;
            
            strength = Math.min(100, strength);
            strengthBar.style.width = strength + '%';
            
            if (strength === 0) {
                strengthText.textContent = '';
            } else if (strength < 25) {
                strengthText.textContent = 'Weak password';
            } else if (strength < 50) {
                strengthText.textContent = 'Fair password';
            } else if (strength < 75) {
                strengthText.textContent = 'Good password';
            } else {
                strengthText.textContent = 'Strong password';
            }
        }

        // Custom alert
        function showAlert(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '10000';
            alertDiv.style.minWidth = '300px';
            alertDiv.style.boxShadow = 'var(--shadow-xl)';
            alertDiv.style.animation = 'slideInRight 0.3s ease';
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.5s';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const role = document.getElementById('selectedRole').value;
            
            if (!role) {
                e.preventDefault();
                showAlert('❌ Please select your role');
                return false;
            }
            
            if (!username || !password) {
                e.preventDefault();
                showAlert('❌ Please enter both username and password');
                return false;
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + 1-6 for role selection
            if (e.ctrlKey && e.key >= '1' && e.key <= '6') {
                e.preventDefault();
                const roles = ['admin', 'secretary', 'principal', 'hod', 'class_teacher', 'student'];
                const index = parseInt(e.key) - 1;
                if (roles[index]) {
                    selectRole(roles[index]);
                }
            }
            
            // Ctrl + U for username
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                document.getElementById('username').focus();
            }
            
            // Ctrl + P for password
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Mouse move parallax effect
        document.addEventListener('mousemove', function(e) {
            const circles = document.querySelectorAll('.bg-circle');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            circles.forEach((circle, index) => {
                const speed = (index + 1) * 30;
                const x = (mouseX * speed) - (speed / 2);
                const y = (mouseY * speed) - (speed / 2);
                circle.style.transform = `translate(${x}px, ${y}px)`;
            });
        });

        // Ripple effect on buttons
        document.querySelectorAll('.role-btn, .login-btn').forEach(button => {
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
                ripple.style.animation = 'rippleEffect 0.6s ease-out';
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rippleEffect {
                to {
                    transform: translate(-50%, -50%) scale(20);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Easter egg - RKSMSHOME secret
        let secretCode = ['r', 'k', 's', 'm', 's', 'h', 'o', 'm', 'e'];
        let codeIndex = 0;
        
        document.addEventListener('keydown', function(e) {
            if (e.key.toLowerCase() === secretCode[codeIndex]) {
                codeIndex++;
                if (codeIndex === secretCode.length) {
                    // Orange theme celebration
                    createConfetti();
                    showAlert('🎉 Welcome to RKSMSHOME Premium Access!');
                    codeIndex = 0;
                }
            } else {
                codeIndex = 0;
            }
        });

        // Confetti effect
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = `hsl(${Math.random() * 30 + 15}, 100%, 50%)`;
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.zIndex = '10001';
                confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear`;
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 5000);
            }
        }

        // Add falling animation
        const fallStyle = document.createElement('style');
        fallStyle.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(fallStyle);

        // Input field animations
        document.querySelectorAll('input').forEach(field => {
            field.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            field.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Set default role (optional)
        window.addEventListener('load', function() {
            // You can set a default role here if needed
            // selectRole('student');
        });
    </script>
</body>
</html>