<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user = null;

if ($id > 0 && isset($conn) && $conn) {
    $stmt = $conn->prepare("SELECT id, username, email, role, department, created_at FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows) {
            $user = $res->fetch_assoc();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>View User Profile - RKMS Home</title>
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
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --saffron: #FF9933;
            --saffron-dark: #e68a00;
            --orange: #FF8C42;
            --maroon: #800000;
            --gold: #FFD700;
            --cream: #FFF8E7;
            --dark: #0f172a;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #FF9933 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
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
            font-size: 80px;
            opacity: 0.1;
            color: white;
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

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float-element 15s infinite;
        }

        @keyframes float-element {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .container {
            width: 100%;
            max-width: 1000px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
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

        /* User Profile Card */
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--saffron), var(--maroon), var(--gold));
        }

        /* Profile Header with Cover */
        .profile-cover {
            height: 200px;
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            position: relative;
            overflow: hidden;
        }

        .profile-cover::before {
            content: 'RKMS';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 120px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.1);
            transform: rotate(-10deg);
        }

        .profile-cover::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><path d="M50 15 L61 40 L88 44 L68 62 L73 90 L50 75 L27 90 L32 62 L12 44 L39 40 Z" fill="white"/></svg>');
            background-size: 60px 60px;
        }

        /* Profile Avatar */
        .profile-avatar-wrapper {
            position: relative;
            margin-top: -80px;
            padding: 0 2rem;
            display: flex;
            align-items: flex-end;
            gap: 2rem;
            z-index: 20;
        }

        .profile-avatar {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            font-weight: 700;
            border: 6px solid white;
            box-shadow: var(--shadow-xl);
            position: relative;
            animation: avatar-glow 3s ease-in-out infinite;
        }

        @keyframes avatar-glow {
            0%, 100% { box-shadow: 0 0 20px var(--saffron); }
            50% { box-shadow: 0 0 40px var(--gold); }
        }

        .profile-avatar i {
            font-size: 5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-title {
            flex: 1;
            margin-bottom: 1rem;
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-email {
            font-size: 1.1rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-email i {
            color: var(--saffron);
        }

        /* Role Badge */
        .role-badge-large {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
            box-shadow: var(--shadow-lg);
            margin-top: 0.5rem;
        }

        /* Profile Content */
        .profile-content {
            padding: 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: linear-gradient(135deg, var(--gray-50), white);
            padding: 1.25rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--saffron);
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--saffron);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--maroon);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: linear-gradient(135deg, var(--gray-50), white);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--saffron), var(--maroon));
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px dashed var(--gray-200);
        }

        .info-card-header i {
            font-size: 1.5rem;
            color: var(--saffron);
        }

        .info-card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row .label {
            color: var(--gray-500);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .info-row .value {
            color: var(--gray-800);
            font-weight: 600;
            font-size: 1rem;
        }

        .value-badge {
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Activity Timeline */
        .timeline {
            margin-top: 2rem;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
            animation: slideIn 0.5s ease;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--gray-200);
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: var(--radius-xl);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 0.75rem;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            flex: 1;
            min-width: 200px;
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
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
            box-shadow: 0 4px 15px rgba(255, 153, 51, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 153, 51, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--maroon);
            border: 2px solid var(--saffron);
        }

        .btn-secondary:hover {
            background: var(--saffron);
            color: white;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        /* Not Found Card */
        .not-found-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-2xl);
            padding: 3rem;
            text-align: center;
            box-shadow: var(--shadow-2xl);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .not-found-icon {
            font-size: 5rem;
            color: var(--saffron);
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .not-found-title {
            font-size: 2rem;
            color: var(--maroon);
            margin-bottom: 1rem;
        }

        .not-found-text {
            color: var(--gray-500);
            margin-bottom: 2rem;
        }

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-100) 50%, var(--gray-200) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: var(--radius-md);
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .profile-avatar-wrapper {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-name {
                font-size: 2rem;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 3rem;
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
            padding: 0.5rem 1rem;
            background: var(--maroon);
            color: white;
            font-size: 0.75rem;
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

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .profile-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .btn {
                display: none;
            }

            .floating-elements,
            body::before,
            body::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <div class="floating-element" style="width: 300px; height: 300px; top: 10%; left: 5%;"></div>
        <div class="floating-element" style="width: 200px; height: 200px; bottom: 20%; right: 10%;"></div>
        <div class="floating-element" style="width: 150px; height: 150px; top: 50%; left: 20%;"></div>
        <div class="floating-element" style="width: 250px; height: 250px; bottom: 30%; left: 30%;"></div>
    </div>

    <div class="container">
        <?php if (!$user): ?>
            <!-- Not Found Card -->
            <div class="not-found-card">
                <div class="not-found-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h2 class="not-found-title">User Not Found</h2>
                <p class="not-found-text">The requested user does not exist or may have been removed from the system.</p>
                <a href="users.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Users List
                </a>
            </div>
        <?php else: ?>
            <!-- User Profile Card -->
            <div class="profile-card">
                <!-- Cover Photo -->
                <div class="profile-cover"></div>

                <!-- Avatar and Basic Info -->
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar" data-tooltip="User Initial">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div class="profile-title">
                        <div class="profile-name">
                            <?php echo htmlspecialchars($user['username']); ?>
                            <span class="role-badge-large">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                            </span>
                        </div>
                        <div class="profile-email">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <?php
                        // Get user statistics (guard query results before calling fetch_assoc)
                        $msg_count = 0;
                        $res = $conn->query("SELECT COUNT(*) AS count FROM messages WHERE sender_id = " . (int)$id);
                        if ($res) {
                            $r = $res->fetch_assoc();
                            $msg_count = isset($r['count']) ? (int)$r['count'] : 0;
                        }

                        $student_count = 0;
                        $res = $conn->query("SELECT COUNT(*) AS count FROM students WHERE user_id = " . (int)$id);
                        if ($res) {
                            $r = $res->fetch_assoc();
                            $student_count = isset($r['count']) ? (int)$r['count'] : 0;
                        }

                        $login_count = 0;
                        $res = $conn->query("SELECT COUNT(*) AS count FROM user_logs WHERE user_id = " . (int)$id . " AND action = 'login'");
                        if ($res) {
                            $r = $res->fetch_assoc();
                            $login_count = isset($r['count']) ? (int)$r['count'] : 0;
                        }
                        ?>
                        <div class="stat-item" data-tooltip="Messages sent by user">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-label">Messages</div>
                            <div class="stat-value"><?php echo $msg_count; ?></div>
                        </div>
                        <div class="stat-item" data-tooltip="Students assigned">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-label">Students</div>
                            <div class="stat-value"><?php echo $student_count; ?></div>
                        </div>
                        <div class="stat-item" data-tooltip="Total logins">
                            <div class="stat-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="stat-label">Logins</div>
                            <div class="stat-value"><?php echo $login_count; ?></div>
                        </div>
                        <div class="stat-item" data-tooltip="Account age">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-label">Days Active</div>
                            <div class="stat-value">
                                <?php 
                                $days = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                echo $days;
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Information Grid -->
                    <div class="info-grid">
                        <!-- Account Information -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <i class="fas fa-user-circle"></i>
                                <h3>Account Information</h3>
                            </div>
                            <div class="info-row">
                                <span class="label">User ID</span>
                                <span class="value">#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Username</span>
                                <span class="value">@<?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Email</span>
                                <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Account Status</span>
                                <span class="value">
                                    <span style="color: var(--success);">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Role & Department -->
                        <div class="info-card">
                            <div class="info-card-header">
                                <i class="fas fa-briefcase"></i>
                                <h3>Role & Department</h3>
                            </div>
                            <div class="info-row">
                                <span class="label">Role</span>
                                <span class="value">
                                    <span class="value-badge">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="label">Department</span>
                                <span class="value">
                                    <?php if ($user['department']): ?>
                                        <span class="value-badge" style="background: linear-gradient(135deg, var(--maroon), #660000); color: var(--gold);">
                                            <?php echo htmlspecialchars($user['department']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="value">Not Assigned</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="label">Access Level</span>
                                <span class="value">
                                    <?php
                                    $access_level = $user['role'] == 'admin' ? 'Full Access' : ($user['role'] == 'hod' ? 'Department Access' : 'Limited Access');
                                    echo $access_level;
                                    ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="label">Permissions</span>
                                <span class="value">
                                    <?php
                                    $permissions = [
                                        'admin' => 'All',
                                        'hod' => 'Department Only',
                                        'class_teacher' => 'Class Only',
                                        'secretary' => 'Office Only'
                                    ];
                                    echo $permissions[$user['role']] ?? 'Standard';
                                    ?>
                                </span>
                            </div>
                        </div>

                        <!-- Account Timeline -->
                        <div class="info-card" style="grid-column: 1 / -1;">
                            <div class="info-card-header">
                                <i class="fas fa-history"></i>
                                <h3>Account Timeline</h3>
                            </div>
                            
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Account Created</div>
                                        <div class="timeline-time">
                                            <?php echo date('F d, Y \a\t h:i A', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // Get last login
                                $last_login = null;
                                $res = $conn->query("SELECT created_at FROM user_logs WHERE user_id = " . (int)$id . " AND action = 'login' ORDER BY created_at DESC LIMIT 1");
                                if ($res) {
                                    $last_login = $res->fetch_assoc();
                                }
                                if ($last_login):
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Last Login</div>
                                        <div class="timeline-time">
                                            <?php echo date('F d, Y \a\t h:i A', strtotime($last_login['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php
                                // Get last activity
                                $last_activity = null;
                                $res = $conn->query("SELECT action, created_at FROM user_logs WHERE user_id = " . (int)$id . " ORDER BY created_at DESC LIMIT 1");
                                if ($res) {
                                    $last_activity = $res->fetch_assoc();
                                }
                                if ($last_activity):
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Last Activity</div>
                                        <div class="timeline-time">
                                            <?php echo ucfirst($last_activity['action']) . ' - ' . date('h:i A', strtotime($last_activity['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Account Age</div>
                                        <div class="timeline-time">
                                            <?php
                                            $created = new DateTime($user['created_at']);
                                            $now = new DateTime();
                                            $diff = $created->diff($now);
                                            echo $diff->format('%y years, %m months, %d days');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="action-buttons">
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary" data-tooltip="Edit user details">
                            <i class="fas fa-edit"></i>
                            Edit Profile
                        </a>
                        <a href="users.php" class="btn btn-secondary" data-tooltip="Back to users list">
                            <i class="fas fa-arrow-left"></i>
                            Back to Users
                        </a>
                        <?php if ($user['role'] != 'admin'): ?>
                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('⚠️ Are you sure you want to delete this user? This action cannot be undone.');"
                           data-tooltip="Delete user">
                            <i class="fas fa-trash-alt"></i>
                            Delete User
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Footer Note -->
                    <div style="margin-top: 1.5rem; text-align: center; color: var(--gray-400); font-size: 0.85rem;">
                        <i class="fas fa-shield-alt"></i>
                        Last updated: <?php echo date('F d, Y h:i A'); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.info-card, .stat-item');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            cards.forEach(card => {
                card.style.opacity = 0;
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                observer.observe(card);
            });

            // Tooltip functionality
            document.querySelectorAll('[data-tooltip]').forEach(element => {
                element.addEventListener('mouseenter', function(e) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = this.getAttribute('data-tooltip');
                    tooltip.style.position = 'absolute';
                    tooltip.style.background = 'var(--maroon)';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '0.5rem 1rem';
                    tooltip.style.borderRadius = 'var(--radius-lg)';
                    tooltip.style.fontSize = '0.75rem';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.boxShadow = 'var(--shadow-lg)';
                    tooltip.style.pointerEvents = 'none';
                    tooltip.style.whiteSpace = 'nowrap';
                    
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                    tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
                    
                    this.addEventListener('mouseleave', function() {
                        tooltip.remove();
                    });
                });
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Press 'E' to edit
            if (e.key === 'e' || e.key === 'E') {
                const editBtn = document.querySelector('.btn-primary');
                if (editBtn) {
                    editBtn.click();
                }
            }
            
            // Press 'B' to go back
            if (e.key === 'b' || e.key === 'B') {
                const backBtn = document.querySelector('.btn-secondary');
                if (backBtn) {
                    backBtn.click();
                }
            }
        });

        // Confetti effect for special users (optional)
        function celebrateSpecialUser() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('celebrate')) {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }
        }

        // Copy email to clipboard
        function copyEmail(email) {
            navigator.clipboard.writeText(email).then(() => {
                alert('📧 Email copied to clipboard!');
            });
        }

        // Add email copy functionality
        const emailElement = document.querySelector('.profile-email');
        if (emailElement) {
            emailElement.style.cursor = 'pointer';
            emailElement.addEventListener('click', function() {
                const email = this.textContent.replace('📧', '').trim();
                copyEmail(email);
            });
        }
    </script>
</body>
</html>