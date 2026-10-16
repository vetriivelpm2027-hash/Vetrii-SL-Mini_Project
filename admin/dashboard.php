<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$success = $success ?? '';
$error = $error ?? '';
$stats = null;
$pending_profile_requests = 0;

// Create table if not exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS profile_edit_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by_hod INT,
    hod_approval_date TIMESTAMP NULL,
    approved_by_admin INT,
    admin_approval_date TIMESTAMP NULL,
    rejection_reason VARCHAR(500),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by_hod) REFERENCES users(id),
    FOREIGN KEY (approved_by_admin) REFERENCES users(id),
    UNIQUE KEY unique_request (student_id)
)";
$conn->query($create_table_sql);

if (isset($conn) && $conn) {
    $stats = $conn->query("SELECT 
        (SELECT COUNT(*) FROM students) as total_students,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM departments) as total_depts,
        (SELECT COUNT(*) FROM messages WHERE is_read=0) as total_messages,
        (SELECT COUNT(*) FROM profile_edit_requests) as total_profile_requests,
        (SELECT COUNT(*) FROM profile_edit_requests WHERE status = 'pending') as pending_profile_requests,
        (SELECT COUNT(*) FROM profile_edit_requests WHERE status = 'approved' AND approved_by_hod IS NOT NULL AND approved_by_admin IS NULL) as awaiting_admin_profile_requests,
        (SELECT COUNT(*) FROM profile_edit_requests WHERE status = 'approved' AND approved_by_admin IS NOT NULL) as approved_profile_requests,
        (SELECT COUNT(*) FROM profile_edit_requests WHERE status = 'rejected') as rejected_profile_requests")->fetch_assoc();
    
    $pending_profile_requests = $stats['awaiting_admin_profile_requests'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKMS Home - Admin Dashboard</title>
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

        html {
            background: var(--maroon-dark);
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

        /* Layout */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
            width:100%;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            background: inherit; /* ensure no white gap */
        }

        /* Sidebar - Maroon theme */
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

        /* Top Bar */
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
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar h1 i {
            background: linear-gradient(135deg, var(--saffron), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .topbar h1::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, var(--saffron), var(--maroon));
            border-radius: 2px;
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
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-weight: 600;
            font-size: 1.25rem;
            border: 2px solid var(--gold);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
            border: 2px solid transparent;
        }

        .alert-success {
            background: #f0fff4;
            color: #065f46;
            border-left-color: var(--success);
            border-color: var(--gold);
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: var(--danger);
            border-color: var(--gold);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Stats Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            border: 2px solid var(--gold);
            position: relative;
            overflow: hidden;
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

        .stat-card::after {
            content: '🚩';
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 60px;
            opacity: 0.05;
            transform: rotate(-15deg);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2xl);
            border-color: var(--saffron);
        }

        .stat-card h3 {
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
        }

        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.1;
            color: var(--maroon);
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--radius-xl);
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
            height: 4px;
            background: linear-gradient(90deg, var(--saffron), var(--maroon), var(--gold));
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--cream-dark);
            background: linear-gradient(90deg, white, var(--cream));
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--maroon);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title span {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
            background: white;
        }

        /* Forms */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--maroon);
            font-size: 0.875rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gold);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 3px rgba(255, 153, 51, 0.1);
        }

        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23800000' viewBox='0 0 20 20'%3E%3Cpath stroke='%23800000' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            appearance: none;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 500;
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

        .btn-info {
            background: linear-gradient(135deg, var(--saffron-light), var(--saffron));
            color: var(--maroon);
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-color: var(--gold);
        }

        .btn-info:hover {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            color: var(--gold);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-color: var(--gold);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-lg);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        thead {
            background: linear-gradient(90deg, var(--maroon), var(--saffron));
        }

        th {
            text-align: left;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--gold);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
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
            border: 1px solid transparent;
        }

        tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            background: linear-gradient(90deg, white, var(--cream));
            border-color: var(--gold);
        }

        td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--cream-dark);
            color: var(--brown);
        }

        td:first-child {
            font-weight: 600;
            color: var(--maroon);
        }

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid var(--gold);
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
            background: linear-gradient(135deg, var(--saffron-dark), var(--saffron));
            color: var(--maroon);
        }

        .role-badge.class_teacher {
            background: linear-gradient(135deg, var(--brown), #5a2e0e);
            color: var(--cream);
        }

        /* Action Buttons Container */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Loading Animation */
        .loading {
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
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .topbar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            td, th {
                padding: 0.75rem;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--cream);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--saffron), var(--maroon));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--maroon);
        }

        /* RKMS Home Specific Styles */
        .welcome-text {
            background: linear-gradient(135deg, var(--maroon), var(--brown));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
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

        /* Sidebar scroll mode - hide icons, show only names */
        .sidebar.scroll-mode .sidebar-menu a span:first-child {
            display: none;
        }

        .sidebar.scroll-mode .sidebar-menu a {
            justify-content: center;
            padding: 0.875rem 0.5rem;
        }

        /* Profile Dropdown Menu */
        .user-profile {
            cursor: pointer;
            position: relative;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 2px solid var(--gold);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-2xl);
            min-width: 250px;
            margin-top: 0.5rem;
            display: none;
            z-index: 1000;
            overflow: hidden;
        }

        .profile-dropdown.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            background: linear-gradient(135deg, var(--maroon-dark), var(--maroon));
            padding: 1.5rem;
            text-align: center;
            color: white;
            border-bottom: 1px solid var(--gold);
        }

        .profile-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--saffron));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: var(--maroon);
            margin: 0 auto 1rem;
            border: 3px solid var(--gold);
        }

        .profile-username {
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            margin-bottom: 0.25rem;
        }

        .profile-role {
            font-size: 0.875rem;
            color: var(--gold);
            font-weight: 500;
        }

        .profile-menu-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .profile-menu-items li {
            margin: 0;
        }

        .profile-menu-items a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: var(--maroon);
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
            gap: 0.75rem;
            font-weight: 500;
        }

        .profile-menu-items a:last-child {
            border-bottom: none;
        }

        .profile-menu-items a:hover {
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent);
            color: var(--maroon-dark);
            padding-left: 2rem;
        }

        .profile-menu-items a i {
            width: 20px;
            text-align: center;
            color: var(--saffron);
        }

        .profile-menu-items a:hover i {
            color: var(--gold);
        }

    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🚩 RKMS</div>
                <div class="sidebar-brand">HOME <span>ADMIN</span></div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><span>📊</span> <span>Dashboard</span></a></li>
                <li><a href="students.php"><span>👨‍🎓</span> <span>Students</span></a></li>
                <li><a href="users.php"><span>👥</span> <span>Users</span></a></li>
                <li><a href="departments.php"><span>🏢</span> <span>Departments</span></a></li>
                <li><a href="messages.php"><span>💬</span> <span>Messages</span></a></li>
                <li><a href="profile_edit_requests.php"><span>📝</span> <span>Profile Requests</span>
                    <?php if($pending_profile_requests > 0): ?>
                        <span class="badge" style="margin-left: auto; background: #ef4444; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;"><?php echo $pending_profile_requests; ?></span>
                    <?php endif; ?>
                </a></li>
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
                    <i class="fas fa-tachometer-alt"></i>
                    Admin Dashboard
                </h1>
                <div class="user-profile" id="profileToggle">
                    <div class="user-avatar"><?php echo isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : ''; ?></div>
                    <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></span>
                    
                    <!-- Profile Dropdown Menu -->
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-header">
                            <div class="profile-avatar-large">
                                <?php echo isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'G'; ?>
                            </div>
                            <div class="profile-username">
                                <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?>
                            </div>
                            <div class="profile-role">
                                🔐 Administrator
                            </div>
                        </div>
                        <ul class="profile-menu-items">
                            <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Profile Settings</span></a></li>
                            <li><a href="settings.php"><i class="fas fa-key"></i> <span>Change Password</span></a></li>
                            <li><a href="../logout.php" style="color: var(--maroon-dark);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Flash messages -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>❌</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid">
                <div class="stat-card" data-tooltip="Total enrolled students">
                    <div class="stat-icon">👨‍🎓</div>
                    <h3>Total Students</h3>
                    <div class="stat-number"><?php echo $stats ? $stats['total_students'] : 0; ?></div>
                </div>
                <div class="stat-card" data-tooltip="Total system users">
                    <div class="stat-icon">👥</div>
                    <h3>Total Users</h3>
                    <div class="stat-number"><?php echo $stats ? $stats['total_users'] : 0; ?></div>
                </div>
                <div class="stat-card" data-tooltip="Academic departments">
                    <div class="stat-icon">🏢</div>
                    <h3>Departments</h3>
                    <div class="stat-number"><?php echo $stats ? $stats['total_depts'] : 0; ?></div>
                </div>
                <div class="stat-card" data-tooltip="Unread messages">
                    <div class="stat-icon">💬</div>
                    <h3>Unread Messages</h3>
                    <div class="stat-number"><?php echo $stats ? $stats['total_messages'] : 0; ?></div>
                </div>
            </div>

            <!-- Profile Edit Request Stats -->
            <div style="grid-column: 1 / -1; margin-top: 10px;">
                <h4 style="font-size: 14px; color: var(--gray-600); margin-bottom: 15px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Profile Edit Permission System</h4>
                <div class="grid">
                    <div class="stat-card" data-tooltip="Total edit requests submitted" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 69, 19, 0.1)); border-left: 4px solid #636EF1;">
                        <div class="stat-icon">📋</div>
                        <h3>Total Requests</h3>
                        <div class="stat-number"><?php echo $stats ? $stats['total_profile_requests'] : 0; ?></div>
                    </div>
                    <div class="stat-card" data-tooltip="Pending HOD approval" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(255, 193, 7, 0.1)); border-left: 4px solid #FBBF24;">
                        <div class="stat-icon">⏳</div>
                        <h3>Awaiting HOD</h3>
                        <div class="stat-number"><?php echo $stats ? $stats['pending_profile_requests'] : 0; ?></div>
                    </div>
                    <div class="stat-card" data-tooltip="HOD approved, awaiting your approval" style="background: linear-gradient(135deg, rgba(251, 146, 60, 0.1), rgba(255, 153, 51, 0.1)); border-left: 4px solid #FF8C42;">
                        <div class="stat-icon">✋</div>
                        <h3>Awaiting Admin</h3>
                        <div class="stat-number" style="color: #FF6B35;"><?php echo $stats ? $stats['awaiting_admin_profile_requests'] : 0; ?></div>
                    </div>
                    <div class="stat-card" data-tooltip="Approved and finalized" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(34, 197, 94, 0.1)); border-left: 4px solid #10B981;">
                        <div class="stat-icon">✅</div>
                        <h3>Approved</h3>
                        <div class="stat-number"><?php echo $stats ? $stats['approved_profile_requests'] : 0; ?></div>
                    </div>
                    <div class="stat-card" data-tooltip="Rejected requests" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1)); border-left: 4px solid #EF4444;">
                        <div class="stat-icon">❌</div>
                        <h3>Rejected</h3>
                        <div class="stat-number"><?php echo $stats ? $stats['rejected_profile_requests'] : 0; ?></div>
                    </div>
                </div>
            </div>

            <!-- Add User Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>➕</span>
                        Add New User
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" placeholder="Enter password" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" placeholder="Enter email" required>
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="secretary">Secretary</option>
                                    <option value="principal">Principal</option>
                                    <option value="hod">HOD</option>
                                    <option value="class_teacher">Class Teacher</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row" id="teacherExtras" style="display:none;">
                            <div class="form-group full">
                                <label for="class_year">Year / Class (for Class Teacher)</label>
                                <input type="text" id="class_year" name="class_year" placeholder="e.g. Grade 10 or 2026">
                            </div>
                        </div>
                        <div class="form-group full">
                            <label for="department">Department (Optional)</label>
                            <select id="department" name="department">
                                <option value="">Select Department</option>
                                <?php
                                $depts = $conn->query("SELECT * FROM departments");
                                while ($row = $depts->fetch_assoc()):
                                ?>
                                <option value="<?php echo htmlspecialchars($row['dept_name']); ?>">
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <span>➕</span>
                            Add User
                        </button>
                    </form>
                </div>
            </div>

            <!-- Class Teachers Assignment -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>👨‍🏫</span>
                        Class Teachers & Assignments
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Assigned Year/Class</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $teachers = $conn->query("SELECT id, username, email, department, created_at FROM users WHERE role = 'class_teacher' ORDER BY created_at DESC");
                                if ($teachers && $teachers->num_rows > 0) {
                                    while ($row = $teachers->fetch_assoc()): 
                                    ?>
                                    <tr data-tooltip="Class Teacher Assignment">
                                        <td>
                                            <span class="id-badge">#<?php echo str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <span style="background: var(--saffron); color: white; padding: 0.4rem 0.8rem; border-radius: 0.375rem; font-weight: 600; display: inline-block;">
                                                📚 <?php echo htmlspecialchars($row['department'] ?? 'Not Assigned'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" data-tooltip="Edit">
                                                    <span>✏️</span> Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile;
                                } else {
                                    echo '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray-500);">No class teachers assigned yet</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>👥</span>
                        Users List
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
                                while ($row = $users->fetch_assoc()): 
                                ?>
                                <tr data-tooltip="Click to view details">
                                    <td>
                                        <span class="id-badge">#<?php echo str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $row['role']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" data-tooltip="Edit User">
                                                <span>✏️</span> Edit
                                            </a>
                                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('⚠️ Are you sure you want to delete this user?');"
                                               data-tooltip="Delete User">
                                                <span>🗑️</span> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle Year/Class field when role is Class Teacher
        (function(){
            var roleSelect = document.getElementById('role');
            var teacherExtras = document.getElementById('teacherExtras');

            function toggleExtras(){
                if (!roleSelect || !teacherExtras) return;
                if (roleSelect.value === 'class_teacher') {
                    teacherExtras.style.display = '';
                } else {
                    teacherExtras.style.display = 'none';
                    var classInput = document.getElementById('class_year');
                    if (classInput) classInput.value = '';
                }
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleExtras);
                toggleExtras();
            }
        })();

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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + N for new user
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                document.getElementById('username').focus();
            }
        });

        // Profile Dropdown Toggle
        const profileToggle = document.getElementById('profileToggle');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!profileToggle.contains(e.target)) {
                    profileDropdown.classList.remove('active');
                }
            });

            // Close dropdown when a menu item is clicked
            const menuItems = profileDropdown.querySelectorAll('a');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    profileDropdown.classList.remove('active');
                });
            });
        }
    </script>
</body>
</html>