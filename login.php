<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();
$error = '';
// display registration redirect message
$success = '';
if (isset($_GET['success']) && !empty($_GET['success'])) {
    // this value used by register pages, not user-supplied
    $success = 'Your account has been created successfully. Please login using your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } elseif ($role === 'staff' && empty($department)) {
        $error = 'Please select a department';
    } else {
        $user = loginUser($username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['department'] = $user['department'];

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
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKMS Home - Student Information System</title>
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
            min-height: 100vh;
            background: #f5f5f5; /* light neutral background instead of full-screen gradient */
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }

        /* outer wrapper limiting the width of the page content */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 0;
        }

        .login-container {
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
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

        /* Animated Background with Saffron-Maroon Theme */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-shape {
            position: absolute;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        .bg-shape:nth-child(1) {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
            background: rgba(128, 0, 0, 0.1);
            animation-delay: 0s;
        }

        .bg-shape:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            background: rgba(255, 153, 51, 0.1);
            animation-delay: 5s;
        }

        .bg-shape:nth-child(3) {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 215, 0, 0.05);
            animation-delay: 10s;
        }

        .bg-shape:nth-child(4) {
            width: 200px;
            height: 200px;
            top: 20%;
            right: 15%;
            background: rgba(128, 0, 0, 0.08);
            animation: floatReverse 15s infinite;
        }

        .bg-shape:nth-child(5) {
            width: 350px;
            height: 350px;
            bottom: 10%;
            left: 5%;
            background: rgba(255, 153, 51, 0.08);
            animation: float 25s infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(100px, 100px) rotate(90deg); }
            50% { transform: translate(0, 200px) rotate(180deg); }
            75% { transform: translate(-100px, 100px) rotate(270deg); }
        }

        @keyframes floatReverse {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-100px, -100px) rotate(-90deg); }
            50% { transform: translate(0, -200px) rotate(-180deg); }
            75% { transform: translate(100px, -100px) rotate(-270deg); }
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            margin: 20px;
            position: relative;
            z-index: 1;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-2xl);
            padding: 40px;
            box-shadow: var(--shadow-2xl);
            animation: slideIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--saffron), var(--maroon), var(--gold), var(--saffron));
            animation: borderGlow 3s linear infinite;
            background-size: 300% 100%;
        }

        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-box::after {
            content: '🚩';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 120px;
            opacity: 0.03;
            transform: rotate(-15deg);
            pointer-events: none;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Enhanced Logo Styling - RKMS Home Theme */
        .logo-wrapper {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }

        .logo-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            padding: 8px;
            animation: pulse 2s infinite;
            box-shadow: 0 15px 40px rgba(128, 0, 0, 0.4);
            position: relative;
        }

        .logo-container::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--saffron));
            opacity: 0.3;
            z-index: -1;
            animation: ripple 2s infinite;
        }

        @keyframes ripple {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.1; }
            100% { transform: scale(1); opacity: 0.3; }
        }

        @keyframes pulse {
            0% { box-shadow: 0 15px 40px rgba(128, 0, 0, 0.4); }
            50% { box-shadow: 0 25px 60px rgba(255, 153, 51, 0.5); }
            100% { box-shadow: 0 15px 40px rgba(128, 0, 0, 0.4); }
        }

        .logo-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 4px solid var(--gold);
            position: relative;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .logo-img:hover {
            transform: scale(1.15) rotate(5deg);
        }

        .logo-fallback {
            font-size: 70px;
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .college-name {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--maroon) 0%, var(--brown) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 15px 0 5px;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 2px 2px 4px rgba(128, 0, 0, 0.2);
            position: relative;
            display: inline-block;
            padding: 0 10px;
        }

        .college-name::before,
        .college-name::after {
            content: '★';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold);
            font-size: 16px;
            opacity: 0.5;
        }

        .college-name::before {
            left: -15px;
        }

        .college-name::after {
            right: -15px;
        }

        .college-subtitle {
            font-size: 14px;
            color: var(--saffron);
            font-weight: 600;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .college-address {
            font-size: 13px;
            color: var(--brown);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
            padding: 8px 15px;
            border-radius: 50px;
            border: 1px solid var(--gold);
        }

        .college-address i {
            color: var(--maroon);
        }

        /* System Stats */
        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
            border-radius: var(--radius-xl);
            animation: fadeInUp 0.6s ease-out;
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .stats-container::before {
            content: '📊';
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 40px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .stat-item {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .stat-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -5px;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 30px;
            background: linear-gradient(to bottom, transparent, var(--gold), transparent);
        }

        .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--maroon);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 10px;
            color: var(--brown);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* System Status */
        .system-status {
            background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
            border-left: 4px solid var(--maroon);
            padding: 15px;
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.7s ease-out;
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .system-status::before {
            content: '🕉️';
            position: absolute;
            right: 10px;
            bottom: -5px;
            font-size: 40px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .status-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            color: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 5px 15px rgba(128, 0, 0, 0.3);
            border: 1px solid var(--gold);
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-out;
            border-left: 4px solid;
            border: 2px solid var(--gold);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee, #fdd);
            border-left-color: var(--danger);
            color: var(--danger);
        }

        .alert-success {
            background: linear-gradient(135deg, #efe, #dfd);
            border-left-color: var(--success);
            color: #166534;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease-out;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--maroon);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: var(--saffron);
            margin-right: 5px;
        }

        /* Role Selector - Admin, Staff, Student */
        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .role-btn {
            padding: 15px 10px;
            border: 2px solid var(--gold);
            background: white;
            border-radius: var(--radius-lg);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: var(--brown);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .role-btn i {
            font-size: 24px;
            color: var(--saffron);
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
            background: rgba(255, 153, 51, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .role-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .role-btn:hover {
            border-color: var(--maroon);
            color: var(--maroon);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 0, 0, 0.2);
        }

        .role-btn:hover i {
            color: var(--maroon);
        }

        .role-btn.active {
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            border-color: var(--gold);
            color: var(--gold);
            box-shadow: 0 5px 15px rgba(255, 153, 51, 0.4);
        }

        .role-btn.active i {
            color: var(--gold);
        }

        /* Select Dropdowns */
        select {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--gold);
            border-radius: var(--radius-lg);
            font-size: 14px;
            background-color: white;
            color: var(--brown);
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23800000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
        }

        select:hover {
            border-color: var(--maroon);
            box-shadow: 0 5px 20px rgba(128, 0, 0, 0.1);
        }

        select:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 4px rgba(255, 153, 51, 0.1);
        }

        /* Input Fields */
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--gold);
            border-radius: var(--radius-lg);
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            color: var(--brown);
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: var(--maroon);
            box-shadow: 0 0 0 4px rgba(128, 0, 0, 0.1);
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder,
        input[type="email"]::placeholder {
            color: var(--gray-400);
            font-size: 13px;
        }

        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--maroon);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }

        .toggle-password:hover {
            color: var(--saffron);
        }

        /* Remember Me */
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .remember-me input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--maroon);
        }

        .remember-me label {
            color: var(--brown);
            font-size: 14px;
            cursor: pointer;
        }

        .remember-me label:hover {
            color: var(--maroon);
        }

        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            border: 2px solid var(--gold);
            border-radius: var(--radius-lg);
            color: var(--gold);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(128, 0, 0, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Divider */
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: var(--gray-400);
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--gold), transparent);
        }

        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .divider span {
            background: white;
            padding: 0 15px;
            color: var(--maroon);
            font-weight: 600;
        }

        /* Quick Links - Now only 2 items */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .quick-link {
            padding: 15px 5px;
            background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
            border: 2px solid var(--gold);
            border-radius: var(--radius-lg);
            color: var(--maroon);
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .quick-link i {
            font-size: 20px;
            color: var(--saffron);
        }

        .quick-link:hover {
            border-color: var(--maroon);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(128, 0, 0, 0.2);
        }

        .quick-link.advanced {
            background: linear-gradient(135deg, var(--saffron) 0%, var(--maroon) 100%);
            color: var(--gold);
            border-color: var(--gold);
        }

        .quick-link.advanced i {
            color: var(--gold);
        }

        .quick-link.advanced:hover {
            box-shadow: 0 15px 35px rgba(255, 153, 51, 0.4);
        }

        /* Contact Info */
        .contact-info {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .contact-info::before {
            content: '📞';
            position: absolute;
            right: -5px;
            bottom: -5px;
            font-size: 40px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .contact-info p {
            color: var(--brown);
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .contact-info i {
            color: var(--maroon);
        }

        /* Quote Section */
        .quote-section {
            text-align: center;
            margin: 25px 0;
            padding: 25px;
            background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
            border-radius: var(--radius-xl);
            position: relative;
            overflow: hidden;
            border: 2px solid var(--gold);
        }

        .quote-section::before {
            content: '"';
            position: absolute;
            top: -20px;
            left: 10px;
            font-size: 100px;
            color: rgba(128, 0, 0, 0.1);
            font-family: serif;
        }

        .quote-section::after {
            content: '"';
            position: absolute;
            bottom: -40px;
            right: 10px;
            font-size: 100px;
            color: rgba(255, 153, 51, 0.1);
            font-family: serif;
        }

        .quote-text {
            font-size: 16px;
            font-style: italic;
            color: var(--brown);
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            line-height: 1.6;
        }

        .quote-author {
            font-size: 12px;
            color: var(--maroon);
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: var(--gray-500);
            font-size: 11px;
        }

        .login-footer p {
            margin: 3px 0;
        }

        .login-footer p:first-child {
            color: var(--maroon);
            font-weight: 600;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 248, 231, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 20px;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--cream-dark);
            border-top: 5px solid var(--maroon);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animations */
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

        /* Floating Elements */
        .floating-symbol {
            position: absolute;
            pointer-events: none;
            opacity: 0.1;
            z-index: 0;
        }

        .floating-symbol i {
            color: var(--gold);
            font-size: 40px;
        }

        .floating-1 { top: 10%; left: 10%; animation: float 15s infinite; }
        .floating-2 { bottom: 10%; right: 10%; animation: floatReverse 18s infinite; }
        .floating-3 { top: 20%; right: 20%; animation: float 20s infinite; }

        /* Responsive */
        @media (max-width: 480px) {
            .login-box {
                padding: 30px 20px;
            }

            .role-selector {
                grid-template-columns: 1fr;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }

            .stats-container {
                flex-direction: column;
                gap: 10px;
            }

            .college-name {
                font-size: 24px;
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
            padding: 5px 10px;
            background: var(--maroon);
            color: var(--gold);
            font-size: 11px;
            border-radius: var(--radius-md);
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 100;
            border: 1px solid var(--gold);
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            transform: translateX(-50%) translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
    </div>

    <!-- Floating Symbols -->
    <div class="floating-symbol floating-1">
        <i class="fas fa-om"></i>
    </div>
    <div class="floating-symbol floating-2">
        <i class="fas fa-pray"></i>
    </div>
    <div class="floating-symbol floating-3">
        <i class="fas fa-star-and-crescent"></i>
    </div>

    <!-- Loading Animation -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p style="color: var(--maroon); font-weight: 600;">🙏 Welcome to RKMS Home...</p>
    </div>

    <div class="container">
        <div class="login-container">
            <div class="login-box">
            <!-- Logo Section with RKMS Home Theme -->
            <div class="logo-wrapper">
                <div class="logo-container">
                    <div class="logo-inner">
                        <!-- RKMS Home Logo -->
                        <div class="logo-fallback">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>
                <h1 class="college-name">RKMS HOME</h1>
                <div class="college-subtitle">Ramakrishna Mission Polytechnic College</div>
                <div class="college-address">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Mylapore, Chennai 600004</span>
                    <i class="fas fa-phone-alt"></i>
                    <span>+91 44 1234 5678</span>
                </div>
            </div>

            <!-- System Statistics -->
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-value">15K+</div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">250+</div>
                    <div class="stat-label">Staff</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">99%</div>
                    <div class="stat-label">Success</div>
                </div>
            </div>

            <!-- System Status -->
            <div class="system-status">
                <div class="status-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <strong>System Status:</strong> All systems operational<br>
                    <small>Last updated: <?php echo date('h:i A'); ?></small>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="action" value="login">
                
                <!-- Select Login Type (Campus Help Desk removed) -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3 style="color: var(--maroon); font-size: 18px; margin-bottom: 10px;">Select Login Type</h3>
                    <p style="color: var(--brown); font-size: 14px;">Choose your role to continue</p>
                </div>

                <!-- Role Selection - Admin, Staff, Student -->
                <div class="role-selector">
                    <button type="button" class="role-btn <?php echo ($selected_role == '' || $selected_role == 'admin') ? 'active' : ''; ?>" data-role="admin" data-tooltip="Administrator Access">
                        <i class="fas fa-crown"></i>
                        <span>Admin</span>
                    </button>
                    <button type="button" class="role-btn <?php echo $selected_role == 'staff' ? 'active' : ''; ?>" data-role="staff" data-tooltip="Staff Access">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Staff</span>
                    </button>
                    <button type="button" class="role-btn <?php echo $selected_role == 'student' ? 'active' : ''; ?>" data-role="student" data-tooltip="Student Access">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student</span>
                    </button>
                </div>
                <input type="hidden" id="selected_role" name="role" value="<?php echo htmlspecialchars($selected_role ?? 'admin'); ?>">

                <!-- Department Selection (for Staff) -->
                <div class="form-group" id="department_group" style="display: none;">
                    <label><i class="fas fa-building"></i> Select Department:</label>
                    <select id="department" name="department">
                        <option value="">-- Choose Department --</option>
                        <option value="DME">Mechanical Engineering</option>
                        <option value="DAE">Automobile Engineering</option>
                        <option value="DCE">Civil Engineering</option>
                        <option value="EEE">Electrical Engineering</option>
                        <option value="ECE">Electronics Engineering</option>
                    </select>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your RKMS Home username/email" autofocus>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required placeholder="Enter your RKMS Home password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me on this device</label>
                </div>

                <!-- Login Button -->
                <button type="submit" class="btn-login" onclick="showLoading()">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to RKMS Home
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>CAMPUS PORTAL</span>
            </div>

            <!-- Quick Links - Only Register and Forgot Password -->
            <div class="quick-links">
                <a href="register.php" class="quick-link" data-tooltip="New Student Registration">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
                <a href="forgot_password.php" class="quick-link advanced" data-tooltip="Reset Password">
                    <i class="fas fa-key"></i>
                    <span>Forgot Password</span>
                </a>
            </div>

            <!-- Contact Info -->
            <div class="contact-info">
                <p>
                    <i class="fas fa-envelope"></i>
                    support@rkmshome.edu
                </p>
                <p>
                    <i class="fas fa-clock"></i>
                    24/7 Support Available
                </p>
            </div>

            <!-- Vivekananda Quote -->
            <div class="quote-section">
                <div class="quote-text">
                    "Arise, awake, and stop not until the goal is reached"
                </div>
                <div class="quote-author">
                    — Swami Vivekananda
                </div>
            </div>

            <!-- Footer -->
            <div class="login-footer">
                <p>© 2026 RKMS Home - Ramakrishna Mission Polytechnic College</p>
                <p>All Rights Reserved | ISO 9001:2025 Certified</p>
                <p>Version 2.0.0 | Spiritual Heritage Theme</p>
            </div>
        </div>
    </div>

    <script>
        // Role selector functionality
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedRole = this.getAttribute('data-role');
                
                // Update active button
                document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selected_role').value = selectedRole;
                
                // Show/hide department group for staff
                const departmentGroup = document.getElementById('department_group');
                const departmentSelect = document.getElementById('department');
                
                if (selectedRole === 'staff') {
                    departmentGroup.style.display = 'block';
                    departmentSelect.required = true;
                } else {
                    departmentGroup.style.display = 'none';
                    departmentSelect.required = false;
                }
            });
        });

        // Set initial role on page load
        window.addEventListener('DOMContentLoaded', function() {
            const initialRole = document.getElementById('selected_role').value || 'admin';
            const departmentGroup = document.getElementById('department_group');
            const departmentSelect = document.getElementById('department');
            
            if (initialRole === 'staff') {
                departmentGroup.style.display = 'block';
            }
            
            document.querySelector(`[data-role="${initialRole}"]`)?.classList.add('active');

            // Check for saved credentials
            if (localStorage.getItem('rkms_rememberedUsername')) {
                document.getElementById('username').value = localStorage.getItem('rkms_rememberedUsername');
                document.getElementById('remember').checked = true;
            }
        });

        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Show loading animation
        function showLoading() {
            const form = document.getElementById('loginForm');
            if (form.checkValidity()) {
                document.getElementById('loading').classList.add('active');
            }
        }

        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const role = document.getElementById('selected_role').value;
            const departmentSelect = document.getElementById('department');
            
            if (!username || !password) {
                e.preventDefault();
                showAlert('❌ Please fill in all fields');
                return false;
            }
            
            if (role === 'staff' && !departmentSelect.value) {
                e.preventDefault();
                showAlert('❌ Please select a department');
                return false;
            }

            // Save username if remember me is checked
            if (document.getElementById('remember').checked) {
                localStorage.setItem('rkms_rememberedUsername', username);
            } else {
                localStorage.removeItem('rkms_rememberedUsername');
            }
        });

        // Custom alert
        function showAlert(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.left = '50%';
            alertDiv.style.transform = 'translateX(-50%)';
            alertDiv.style.zIndex = '10000';
            alertDiv.style.minWidth = '300px';
            alertDiv.style.boxShadow = '0 10px 30px rgba(128, 0, 0, 0.3)';
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.5s';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + 1 for Admin
            if (e.ctrlKey && e.key === '1') {
                e.preventDefault();
                document.querySelector('[data-role="admin"]').click();
            }
            // Ctrl + 2 for Staff
            if (e.ctrlKey && e.key === '2') {
                e.preventDefault();
                document.querySelector('[data-role="staff"]').click();
            }
            // Ctrl + 3 for Student
            if (e.ctrlKey && e.key === '3') {
                e.preventDefault();
                document.querySelector('[data-role="student"]').click();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Easter egg - RKMS Home secret code
        let secretCode = ['r', 'k', 'm', 's', 'h', 'o', 'm', 'e'];
        let codeIndex = 0;
        
        document.addEventListener('keydown', function(e) {
            if (e.key.toLowerCase() === secretCode[codeIndex]) {
                codeIndex++;
                if (codeIndex === secretCode.length) {
                    createConfetti();
                    showAlert('🎉 Welcome to RKMS Home! Jai Ramakrishna!');
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
                confetti.style.background = i % 2 === 0 ? 'var(--saffron)' : 'var(--maroon)';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.zIndex = '10001';
                confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear`;
                confetti.style.boxShadow = '0 0 10px var(--gold)';
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 5000);
            }
        }

        // Add falling animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);

        // Input field animations
        document.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            field.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Mouse move parallax effect
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.bg-shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 20;
                const x = (mouseX * speed) - (speed / 2);
                const y = (mouseY * speed) - (speed / 2);
                shape.style.transform = `translate(${x}px, ${y}px)`;
            });
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
                tooltip.style.padding = '5px 10px';
                tooltip.style.borderRadius = 'var(--radius-md)';
                tooltip.style.fontSize = '11px';
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
    </script>
</body>
</html> 