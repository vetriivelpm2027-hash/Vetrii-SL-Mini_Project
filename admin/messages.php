<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin();

// process bulk actions
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_all'])) {
        if (isset($conn) && $conn) {
            $del = $conn->query("DELETE FROM messages");
            if ($del) {
                $success = 'All messages have been deleted successfully.';
            } else {
                $error = 'Failed to delete messages: ' . $conn->error;
            }
        } else {
            $error = 'Database connection not available.';
        }
    }
}
// carry over any messages passed through query string
$success = $success ?: ($_GET['success'] ?? '');
$error = $error ?: ($_GET['error'] ?? '');

// Get messages with proper joins
$messages_query = "SELECT m.*, 
                   u1.username as sender_name, 
                   u1.role as sender_role,
                   u2.username as receiver_name,
                   u2.role as receiver_role
                   FROM messages m 
                   LEFT JOIN users u1 ON m.sender_id = u1.id 
                   LEFT JOIN users u2 ON m.receiver_id = u2.id 
                   ORDER BY m.created_at DESC";

$messages = $conn->query($messages_query);

// Check if query executed properly
if (!$messages) {
    $error = "Database error: " . $conn->error;
}

// Get message statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM messages";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'unread' => 0, 'today' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RKMS Home Student System</title>
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
            --maroon-dark: #660000;
            --gold: #FFD700;
            --gold-dark: #e6b800;
            --cream: #FFF8E7;
            --cream-dark: #f5e6d3;
            --gray-50: #f9fafb;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, var(--saffron) 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '🕉️';
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 100px;
            opacity: 0.1;
            color: white;
            transform: rotate(-15deg);
            animation: float 6s ease-in-out infinite;
            z-index: 0;
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
        }

        /* Enhanced Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--maroon) 0%, #4A0404 100%);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
            box-shadow: var(--shadow-2xl);
            z-index: 50;
            border-right: 3px solid var(--gold);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 2px solid var(--gold);
            text-align: center;
        }

        .sidebar-logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--gold);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--cream);
            text-transform: uppercase;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            color: var(--gold);
            margin-top: 0.5rem;
            font-style: italic;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 1rem;
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
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--gold), var(--saffron));
            color: var(--maroon);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            min-height: 100vh;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gold);
        }

        .topbar h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar h1 i {
            color: var(--saffron);
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
            border: 1px solid var(--gold);
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
            border: 2px solid var(--gold);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-xl);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.5s ease;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: #f0fff4;
            color: #166534;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: var(--danger);
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--saffron), var(--maroon));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2xl);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--saffron);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            margin-bottom: 2rem;
            border: 2px solid var(--gold);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--cream-dark);
            background: linear-gradient(135deg, var(--cream), white);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--saffron);
        }

        .message-count {
            background: var(--saffron);
            color: var(--maroon);
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-xl);
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
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--maroon);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        th:first-child {
            border-top-left-radius: var(--radius-xl);
            border-bottom-left-radius: var(--radius-xl);
        }

        th:last-child {
            border-top-right-radius: var(--radius-xl);
            border-bottom-right-radius: var(--radius-xl);
        }

        tbody tr {
            background: white;
            border-radius: var(--radius-xl);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            cursor: pointer;
        }

        tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            background: linear-gradient(135deg, white, var(--cream));
        }

        tbody tr.unread {
            background: #fff3e0;
            border-left: 4px solid var(--saffron);
        }

        tbody tr.new-message {
            animation: highlight 2s ease;
        }

        @keyframes highlight {
            0%, 100% { background: white; }
            50% { background: #fff3e0; }
        }

        td {
            padding: 1rem 1.5rem;
            color: var(--gray-700);
            font-size: 0.95rem;
            border: none;
        }

        /* Message Preview */
        .message-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--gray-600);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-read {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-unread {
            background: #ffebee;
            color: #c62828;
        }

        .status-badge i {
            font-size: 0.75rem;
        }

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .role-badge.admin {
            background: linear-gradient(135deg, var(--maroon), #990000);
            color: var(--gold);
        }

        .role-badge.hod {
            background: linear-gradient(135deg, var(--saffron), #e68a00);
            color: var(--maroon);
        }

        /* Action Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--saffron), var(--orange));
            color: var(--maroon);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 153, 51, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-secondary {
            background: var(--gray-300);
            color: var(--gray-600);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 50%;
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
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
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            transform: translateX(-50%) translateY(-5px);
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
            border: 2px solid var(--gray-200);
            border-radius: 50px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 3px rgba(255, 153, 51, 0.1);
        }

        .filter-select {
            padding: 0.75rem 2rem 0.75rem 1.5rem;
            border: 2px solid var(--gray-200);
            border-radius: 50px;
            font-size: 0.95rem;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23800000' viewBox='0 0 20 20'%3E%3Cpath stroke='%23800000' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-brand,
            .sidebar-subtitle,
            .sidebar-menu a span:not(.emoji) {
                display: none;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .sidebar:hover {
                width: 280px;
            }
            
            .sidebar:hover .sidebar-brand,
            .sidebar:hover .sidebar-subtitle,
            .sidebar:hover .sidebar-menu a span:not(.emoji) {
                display: inline;
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
            
            .search-bar {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            td, th {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--saffron);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- bulk action form will be inserted in topbar for better visibility -->
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🏠 RKMS</div>
            <div class="sidebar-brand">HOME ADMIN</div>
            <div class="sidebar-subtitle">"Service & Discipline"</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span>📊</span> <span>Dashboard</span></a></li>
            <li><a href="students.php"><span>👨‍🎓</span> <span>Students</span></a></li>
            <li><a href="users.php"><span>👥</span> <span>Users</span></a></li>
            <li><a href="departments.php"><span>🏢</span> <span>Departments</span></a></li>
            <li><a href="messages.php" class="active"><span>💬</span> <span>Messages</span></a></li>
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
                <i class="fas fa-envelope"></i>
                Messages
            </h1>
            <div style="margin-left:auto;">
                <form method="post" onsubmit="return confirm('⚠️ This will permanently delete ALL messages. Are you sure?');" style="display:inline;">
                    <button type="submit" name="delete_all" class="btn btn-danger" style="padding:0.5rem 1rem; font-size:0.9rem;">
                        <i class="fas fa-trash-alt"></i> Delete All Messages
                    </button>
                </form>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
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

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="stat-label">Total Messages</div>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                <div class="stat-label">Unread</div>
                <div class="stat-number"><?php echo $stats['unread']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-label">Today</div>
                <div class="stat-number"><?php echo $stats['today']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-label">Active Users</div>
                <div class="stat-number">
                    <?php 
                    $active_users = $conn->query("SELECT COUNT(DISTINCT sender_id) as count FROM messages WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
                    echo $active_users;
                    ?>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-bar">
            <input type="text" class="search-input" placeholder="🔍 Search messages by sender, receiver, or subject..." id="searchInput">
            <select class="filter-select" id="statusFilter">
                <option value="all">All Messages</option>
                <option value="unread">Unread Only</option>
                <option value="read">Read Only</option>
                <option value="today">Today's Messages</option>
            </select>
        </div>

        <!-- Messages Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-comments"></i>
                    All Messages
                </h3>
                <span class="message-count" id="messageCount">
                    <?php echo $stats['total']; ?> total
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="messagesTable">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($messages && $messages->num_rows > 0): ?>
                                <?php while ($row = $messages->fetch_assoc()): 
                                    $is_unread = $row['is_read'] == 0;
                                    $is_admin_message = $row['sender_role'] == 'admin';
                                ?>
                                <tr class="<?php echo $is_unread ? 'unread' : ''; ?>" data-message-id="<?php echo $row['id']; ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--saffron), var(--orange)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--maroon); font-weight: 600;">
                                                <?php echo strtoupper(substr($row['sender_name'] ?? '?', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div><?php echo htmlspecialchars($row['sender_name'] ?? 'Unknown'); ?></div>
                                                <span class="role-badge <?php echo $row['sender_role'] ?? ''; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['sender_role'] ?? 'user')); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--gray-400), var(--gray-500)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo strtoupper(substr($row['receiver_name'] ?? '?', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <?php echo htmlspecialchars($row['receiver_name'] ?? 'All Users'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['subject'] ?? 'No Subject'); ?></strong>
                                    </td>
                                    <td>
                                        <div class="message-preview" title="<?php echo htmlspecialchars($row['message'] ?? ''); ?>">
                                            <?php echo htmlspecialchars(substr($row['message'] ?? '', 0, 50)) . '...'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span data-tooltip="<?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?>">
                                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                            <br>
                                            <small style="color: var(--gray-400);"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $is_unread ? 'status-unread' : 'status-read'; ?>">
                                            <i class="fas fa-<?php echo $is_unread ? 'envelope' : 'envelope-open'; ?>"></i>
                                            <?php echo $is_unread ? 'Unread' : 'Read'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="view_message.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" data-tooltip="View Message">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!$is_admin_message): ?>
                                                <a href="delete_message.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('⚠️ Are you sure you want to delete this message?');"
                                                   data-tooltip="Delete Message">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled data-tooltip="Admin messages cannot be deleted">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem; display: block;"></i>
                                        <h3 style="color: var(--gray-500);">No Messages Found</h3>
                                        <p style="color: var(--gray-400); margin-top: 0.5rem;">There are no messages in the system yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer -->
                <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="color: var(--gray-600);">
                        <i class="fas fa-envelope"></i>
                        Showing <strong><?php echo $messages ? $messages->num_rows : 0; ?></strong> messages
                    </div>
                    <div>
                        <button class="btn btn-primary btn-sm" onclick="refreshMessages()" data-tooltip="Refresh Messages">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Real-time message updates
let lastMessageCount = <?php echo $stats['total']; ?>;

async function checkNewMessages() {
    try {
        const response = await fetch('api/check_new_messages.php');
        const data = await response.json();
        
        if (data.count > lastMessageCount) {
            // New messages arrived
            showNotification('New messages received!');
            highlightNewMessages();
            lastMessageCount = data.count;
        }
    } catch (error) {
        console.error('Error checking messages:', error);
    }
}

function showNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.background = 'linear-gradient(135deg, var(--saffron), var(--orange))';
    notification.style.color = 'var(--maroon)';
    notification.style.padding = '1rem 2rem';
    notification.style.borderRadius = '50px';
    notification.style.boxShadow = 'var(--shadow-xl)';
    notification.style.zIndex = '9999';
    notification.style.animation = 'slideIn 0.5s ease';
    notification.style.display = 'flex';
    notification.style.alignItems = 'center';
    notification.style.gap = '1rem';
    notification.style.border = '2px solid var(--gold)';
    notification.innerHTML = `
        <i class="fas fa-envelope"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--maroon); cursor: pointer; font-size: 1.2rem;">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function highlightNewMessages() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.classList.add('new-message');
        setTimeout(() => {
            row.classList.remove('new-message');
        }, 2000);
    });
}

function refreshMessages() {
    location.reload();
}

// Search functionality
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (row.cells.length > 1) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        }
    });
});

// Status filter
document.getElementById('statusFilter')?.addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (row.cells.length > 1) {
            const statusCell = row.cells[5]; // Status column
            const isUnread = statusCell.textContent.includes('Unread');
            
            if (filterValue === 'all') {
                row.style.display = '';
            } else if (filterValue === 'unread' && isUnread) {
                row.style.display = '';
            } else if (filterValue === 'read' && !isUnread) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

// Mark message as read on click
document.querySelectorAll('tbody tr').forEach(row => {
    row.addEventListener('click', function(e) {
        // Don't trigger if clicking on buttons
        if (e.target.closest('.btn')) return;
        
        const messageId = this.dataset.messageId;
        if (messageId && this.classList.contains('unread')) {
            // AJAX call to mark as read
            fetch('api/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + messageId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.remove('unread');
                    const statusCell = this.cells[5];
                    statusCell.innerHTML = '<span class="status-badge status-read"><i class="fas fa-envelope-open"></i> Read</span>';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + F for search focus
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
    }
    
    // Ctrl + R to refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshMessages();
    }
});

// Check for new messages every 10 seconds
setInterval(checkNewMessages, 10000);

// Tooltip initialization
document.querySelectorAll('[data-tooltip]').forEach(element => {
    element.addEventListener('mouseenter', function(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.getAttribute('data-tooltip');
        tooltip.style.position = 'absolute';
        tooltip.style.background = 'var(--maroon)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '0.5rem 1rem';
        tooltip.style.borderRadius = 'var(--radius-lg)';
        tooltip.style.fontSize = '0.75rem';
        tooltip.style.zIndex = '1000';
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

// Animate stats on load
window.addEventListener('load', () => {
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
        const value = stat.textContent;
        stat.textContent = '0';
        
        let start = 0;
        const end = parseInt(value);
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
</script>
</body>
</html>