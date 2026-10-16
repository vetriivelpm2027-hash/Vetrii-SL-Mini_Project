<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// sessions are initialized in includes/auth.php, so no need to start again
requireLogin();

// restrict to admin role
if (!hasRole('admin')) {
    header('Location: ../unauthorized.php');
    exit();
}

// prepare message variables
$message = '';
$messageType = 'info';

// simple handling for settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_settings') {
    // TODO: add persistence logic here (e.g. save to database or config file)
    $message = 'Settings have been updated successfully.';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - RKMS Home</title>
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

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        /* Modern Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--cream);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--maroon);
        }

        /* Enhanced Sidebar - Maroon theme */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--maroon-dark) 0%, var(--sidebar-bg) 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow-y: hidden;
            border-right: 2px solid var(--gold);
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 2px solid var(--gold);
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
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
            font-size: 3rem;
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
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: 2px;
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
            gap: 1rem;
            padding: 0.8rem 1.2rem;
            color: var(--cream);
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: all 0.3s ease;
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

        .sidebar-menu i {
            width: 20px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            background: var(--cream);
        }

        /* Enhanced Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 2rem;
            background: white;
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
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .topbar h1 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--maroon), var(--brown));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar h1 i {
            background: linear-gradient(135deg, var(--saffron), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1.5rem 0.5rem 0.5rem;
            background: var(--cream);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--gold);
        }

        .user-profile:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--saffron);
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
            font-size: 1.2rem;
            border: 2px solid var(--gold);
        }

        /* Settings Container */
        .settings-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Settings Sidebar */
        .settings-sidebar {
            background: white;
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gold);
            height: fit-content;
            position: relative;
            overflow: hidden;
        }

        .settings-sidebar::before {
            content: '⚙️';
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 60px;
            opacity: 0.05;
            transform: rotate(-15deg);
        }

        .settings-nav {
            list-style: none;
        }

        .settings-nav li {
            margin-bottom: 0.5rem;
        }

        .settings-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: var(--maroon);
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .settings-nav a:hover,
        .settings-nav a.active {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            transform: translateX(5px);
            border-color: var(--gold);
        }

        .settings-nav i {
            width: 20px;
        }

        /* Settings Content */
        .settings-content {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .settings-content::before {
            content: '🕉️';
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 60px;
            opacity: 0.05;
            transform: rotate(-15deg);
        }

        .settings-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gold);
        }

        .settings-header h2 {
            font-size: 1.5rem;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .settings-header h2 i {
            color: var(--saffron);
        }

        .settings-header p {
            color: var(--brown);
            margin-top: 0.5rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--maroon);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--gold);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 3px rgba(255, 153, 51, 0.1);
        }

        .input-group {
            position: relative;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--maroon);
        }

        /* Button Styles */
        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
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

        .btn-primary {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            border-color: var(--gold);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 153, 51, 0.4);
        }

        .btn-secondary {
            background: var(--cream);
            color: var(--maroon);
            border: 2px solid var(--gold);
        }

        .btn-secondary:hover {
            background: var(--saffron);
            color: var(--maroon);
            transform: translateY(-2px);
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
            border: 2px solid var(--gold);
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
            border: 2px solid var(--gold);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fff4, #dcfce7);
            color: #166534;
            border-left-color: var(--success);
        }

        .alert-error {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #991b1b;
            border-left-color: var(--danger);
        }

        /* Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .info-card {
            background: linear-gradient(135deg, var(--cream), white);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid var(--gold);
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
        }

        .info-card::after {
            content: '🚩';
            position: absolute;
            bottom: -5px;
            right: -5px;
            font-size: 40px;
            opacity: 0.05;
            transform: rotate(-15deg);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--saffron);
        }

        .info-card h4 {
            color: var(--maroon);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card h4 i {
            color: var(--saffron);
        }

        .info-card p {
            color: var(--brown);
            margin-bottom: 0.5rem;
        }

        .info-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--maroon-dark);
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
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 80px;
                overflow: visible;
            }
            
            .sidebar-brand,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 1rem;
            }
            
            .sidebar-menu i {
                margin: 0;
            }

            .main-content {
                margin-left: 80px;
            }
            
            .sidebar:hover {
                width: 280px;
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
                padding: 1rem;
                margin-left: 0;
            }
            
            .topbar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar - Maroon theme with gold accents -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="sidebar-brand">RKMS <span>HOME</span></div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
            <li><a href="departments.php"><i class="fas fa-building"></i> <span>Departments</span></a></li>
            <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
            <li><a href="export.php"><i class="fas fa-download"></i> <span>Export Data</span></a></li>
            <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h1>
                <i class="fas fa-cog"></i>
                System Settings
            </h1>
            <div class="user-profile" data-tooltip="Your Profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: var(--maroon);"></i>
            </div>
        </div>

        <!-- Settings Container -->
        <div class="settings-container">
            <!-- Settings Navigation -->
            <div class="settings-sidebar">
                <ul class="settings-nav">
                    <li><a href="#general" class="active" onclick="showSection('general')">
                        <i class="fas fa-sliders-h"></i> General Settings
                    </a></li>
                    <li><a href="#security" onclick="showSection('security')">
                        <i class="fas fa-shield-alt"></i> Security
                    </a></li>
                    <li><a href="#notifications" onclick="showSection('notifications')">
                        <i class="fas fa-bell"></i> Notifications
                    </a></li>
                    <li><a href="#system" onclick="showSection('system')">
                        <i class="fas fa-server"></i> System Info
                    </a></li>
                    <li><a href="#backup" onclick="showSection('backup')">
                        <i class="fas fa-database"></i> Backup
                    </a></li>
                </ul>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- General Settings Section -->
                <div id="general" class="settings-section">
                    <div class="settings-header">
                        <h2><i class="fas fa-sliders-h"></i> General Settings</h2>
                        <p>Configure general system preferences and options</p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-group">
                            <label for="site_name">System Name</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                   value="RKMS Home Student System">
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                   value="admin@rkmshome.edu">
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select class="form-control" id="timezone" name="timezone">
                                <option value="UTC">UTC</option>
                                <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                                <option value="America/New_York">America/New York</option>
                                <option value="Europe/London">Europe/London</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="language">Default Language</label>
                            <select class="form-control" id="language" name="language">
                                <option value="en" selected>English</option>
                                <option value="hi">हिन्दी (Hindi)</option>
                                <option value="bn">বাংলা (Bengali)</option>
                                <option value="te">తెలుగు (Telugu)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Security Section -->
                <div id="security" class="settings-section" style="display: none;">
                    <div class="settings-header">
                        <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                        <p>Manage security preferences and password</p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="old_pass">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="old_pass" name="old_pass" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('old_pass')"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_pass">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_pass" name="new_pass" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('new_pass')"></i>
                            </div>
                            <small style="color: var(--brown);">Minimum 8 characters with at least one number and one letter</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_pass">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_pass" name="confirm_pass" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_pass')"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 1rem;">
                                <div class="toggle-switch">
                                    <input type="checkbox" id="two_factor" name="two_factor">
                                    <span class="toggle-slider"></span>
                                </div>
                                Enable Two-Factor Authentication
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </form>
                </div>

                <!-- Notifications Section -->
                <div id="notifications" class="settings-section" style="display: none;">
                    <div class="settings-header">
                        <h2><i class="fas fa-bell"></i> Notification Settings</h2>
                        <p>Configure email and system notifications</p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 1rem;">
                                <div class="toggle-switch">
                                    <input type="checkbox" id="email_notify" name="email_notify" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                Email Notifications
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 1rem;">
                                <div class="toggle-switch">
                                    <input type="checkbox" id="student_alerts" name="student_alerts" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                Student Registration Alerts
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 1rem;">
                                <div class="toggle-switch">
                                    <input type="checkbox" id="system_updates" name="system_updates" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                System Update Notifications
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </form>
                </div>

                <!-- System Information Section -->
                <div id="system" class="settings-section" style="display: none;">
                    <div class="settings-header">
                        <h2><i class="fas fa-server"></i> System Information</h2>
                        <p>View system details and environment</p>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h4><i class="fas fa-tag"></i> Application</h4>
                            <p>RKMS Home Student System</p>
                            <div class="value">v2.0.0</div>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-code"></i> PHP Version</h4>
                            <p>Runtime Environment</p>
                            <div class="value"><?php echo phpversion(); ?></div>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-database"></i> Database</h4>
                            <p>MySQL / MariaDB</p>
                            <div class="value">v8.0</div>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-clock"></i> Server Time</h4>
                            <p><?php echo date('l, F j, Y'); ?></p>
                            <div class="value"><?php echo date('h:i A'); ?></div>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-memory"></i> Memory Limit</h4>
                            <p>PHP Configuration</p>
                            <div class="value"><?php echo ini_get('memory_limit'); ?></div>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-upload"></i> Max Upload Size</h4>
                            <p>File Upload Limit</p>
                            <div class="value"><?php echo ini_get('upload_max_filesize'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Backup Section -->
                <div id="backup" class="settings-section" style="display: none;">
                    <div class="settings-header">
                        <h2><i class="fas fa-database"></i> Database Backup</h2>
                        <p>Create and manage system backups</p>
                    </div>
                    
                    <div style="text-align: center; padding: 3rem;">
                        <i class="fas fa-database" style="font-size: 5rem; color: var(--saffron); margin-bottom: 1rem;"></i>
                        <h3 style="margin-bottom: 1rem; color: var(--maroon);">Create a new backup</h3>
                        <p style="color: var(--brown); margin-bottom: 2rem;">Generate a complete backup of your database</p>
                        <button class="btn btn-primary" onclick="createBackup()">
                            <i class="fas fa-download"></i> Create Backup Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Show selected section
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected section
    document.getElementById(sectionId).style.display = 'block';
    
    // Update active state in navigation
    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.classList.remove('active');
    });
    
    event.target.classList.add('active');
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.target;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Create backup (placeholder function)
function createBackup() {
    alert('Backup functionality will be implemented here!');
    
    // Show success notification
    showNotification('✅ Backup created successfully!');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.background = 'linear-gradient(135deg, var(--saffron), var(--maroon))';
    notification.style.color = 'var(--gold)';
    notification.style.padding = '1rem 2rem';
    notification.style.borderRadius = 'var(--radius-lg)';
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

// Handle form submissions with animation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Add loading state to button
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        button.disabled = true;
        
        // Simulate form submission (replace with actual AJAX)
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-check"></i> Success!';
            button.style.background = 'var(--success)';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
        }, 1500);
    });
});

// Initialize first section
document.addEventListener('DOMContentLoaded', function() {
    showSection('general');
});

// User profile dropdown
document.querySelector('.user-profile').addEventListener('click', function() {
    alert('🙏 Jai Ramakrishna!');
});

// Tooltip functionality
document.querySelectorAll('[data-tooltip]').forEach(element => {
    element.addEventListener('mouseenter', function(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'dynamic-tooltip';
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

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S for save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.querySelector('button[type="submit"]')?.click();
    }
});
</script>
</body>
</html>