<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is teacher
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if ($user['role'] != 'class_teacher') {
    header("Location: ../unauthorized.php");
    exit();
}

// Get teacher info
$teacher_stmt = $conn->prepare("SELECT t.*, d.full_name as dept_name 
                                FROM teachers t 
                                LEFT JOIN departments d ON t.department_id = d.id 
                                WHERE t.user_id = ?");
$teacher_stmt->bind_param("i", $user_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher = $teacher_result->fetch_assoc();
$teacher_stmt->close();

// Get today's date
$today = date('Y-m-d');

// Handle attendance marking
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $date = $_POST['attendance_date'] ?? $today;
    $attendance_data = $_POST['attendance'] ?? [];
    
    $conn->begin_transaction();
    
    try {
        foreach ($attendance_data as $student_id => $status) {
            $student_id = intval($student_id);
            $status = $conn->real_escape_string($status);
            
            // Check if attendance already exists for this date
            $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?");
            $check_stmt->bind_param("is", $student_id, $date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing
                $update_stmt = $conn->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE student_id = ? AND attendance_date = ?");
                $update_stmt->bind_param("siis", $status, $user_id, $student_id, $date);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new
                $insert_stmt = $conn->prepare("INSERT INTO attendance (student_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("issi", $student_id, $date, $status, $user_id);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
        
        $conn->commit();
        $success = "✅ Attendance marked successfully for " . date('d M Y', strtotime($date));
    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ Error marking attendance: " . $e->getMessage();
    }
}

// Get students for this teacher's class
$students_stmt = $conn->prepare("SELECT s.*, u.username 
                                 FROM students s 
                                 JOIN users u ON s.user_id = u.id 
                                 WHERE s.department_id = ? 
                                 ORDER BY s.semester, s.registration_number");
$students_stmt->bind_param("i", $teacher['department_id']);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result;
$students_stmt->close();

// Get selected date for attendance view
$view_date = $_GET['date'] ?? $today;

// Get attendance for selected date
$attendance_stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE attendance_date = ?");
$attendance_stmt->bind_param("s", $view_date);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance_data = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_data[$row['student_id']] = $row['status'];
}
$attendance_stmt->close();

// Get attendance statistics
$stats_stmt = $conn->prepare("SELECT 
                              COUNT(DISTINCT student_id) as total_students,
                              SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                              SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                              SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                              SUM(CASE WHEN status = 'holiday' THEN 1 ELSE 0 END) as holiday_count
                              FROM attendance 
                              WHERE attendance_date = ?");
$stats_stmt->bind_param("s", $view_date);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Get recent attendance records
$recent_stmt = $conn->prepare("SELECT a.*, s.first_name, s.last_name, s.registration_number 
                               FROM attendance a
                               JOIN students s ON a.student_id = s.id
                               WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                               ORDER BY a.attendance_date DESC, a.marked_at DESC
                               LIMIT 20");
if ($recent_stmt) {
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    $recent_attendance = $recent_result;
    $recent_stmt->close();
} else {
    error_log("Recent attendance query prepare failed: " . $conn->error);
    $recent_attendance = null;
}

// Get monthly statistics
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$monthly_stmt = $conn->prepare("SELECT 
                                attendance_date,
                                COUNT(*) as total,
                                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                                FROM attendance 
                                WHERE attendance_date BETWEEN ? AND ?
                                GROUP BY attendance_date
                                ORDER BY attendance_date");
$monthly_stmt->bind_param("ss", $month_start, $month_end);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_data = $monthly_result;
$monthly_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - RKMS HOME Teacher Portal</title>
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

        .date-badge {
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
        .stat-card:nth-child(5) { animation-delay: 0.5s; }

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

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-xl);
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

        .card-header .badge {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        /* Date Selector */
        .date-selector {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .date-input-group {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--primary-soft);
            padding: 5px 15px;
            border-radius: 30px;
        }

        .date-input-group i {
            color: var(--primary);
        }

        .date-input {
            border: none;
            background: none;
            padding: 8px;
            font-size: 14px;
            color: var(--dark);
            outline: none;
        }

        .view-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .today-btn {
            background: var(--primary-soft);
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .today-btn:hover {
            background: var(--gradient);
            color: white;
        }

        /* Attendance Table */
        .attendance-table-container {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
            border-radius: 15px;
            border: 1px solid var(--primary-soft);
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .attendance-table thead {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }

        .attendance-table th {
            padding: 15px 20px;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--primary);
        }

        .attendance-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--primary-soft);
            color: var(--dark);
            font-size: 14px;
        }

        .attendance-table tbody tr {
            transition: all 0.3s ease;
        }

        .attendance-table tbody tr:hover {
            background: var(--primary-soft);
        }

        /* Student Info */
        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-avatar {
            width: 35px;
            height: 35px;
            background: var(--gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .student-name {
            font-weight: 600;
            color: var(--dark);
        }

        .student-reg {
            font-size: 11px;
            color: var(--gray);
        }

        /* Status Radio */
        .status-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-radio {
            position: relative;
        }

        .status-radio input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .status-radio label {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 13px;
            border: 2px solid transparent;
        }

        .status-radio input[type="radio"]:checked + label {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .status-radio.present label {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: var(--success);
        }

        .status-radio.present input[type="radio"]:checked + label {
            background: var(--success);
            color: white;
        }

        .status-radio.absent label {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }

        .status-radio.absent input[type="radio"]:checked + label {
            background: var(--danger);
            color: white;
        }

        .status-radio.late label {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-color: var(--warning);
        }

        .status-radio.late input[type="radio"]:checked + label {
            background: var(--warning);
            color: white;
        }

        .status-radio.holiday label {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border-color: var(--info);
        }

        .status-radio.holiday input[type="radio"]:checked + label {
            background: var(--info);
            color: white;
        }

        /* Submit Button */
        .submit-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .submit-btn i {
            font-size: 18px;
        }

        /* Recent Attendance Grid */
        .recent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .recent-item {
            background: var(--primary-soft);
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .recent-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }

        .recent-item.present {
            border-left-color: var(--success);
        }

        .recent-item.absent {
            border-left-color: var(--danger);
        }

        .recent-item.late {
            border-left-color: var(--warning);
        }

        .recent-item.holiday {
            border-left-color: var(--info);
        }

        .recent-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .recent-icon i {
            font-size: 20px;
        }

        .recent-info {
            flex: 1;
        }

        .recent-info h4 {
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .recent-info p {
            color: var(--gray);
            font-size: 12px;
        }

        .recent-date {
            color: var(--primary);
            font-weight: 600;
            font-size: 12px;
        }

        /* Chart Placeholder */
        .chart-container {
            height: 200px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            padding: 20px 0;
        }

        .chart-bar {
            flex: 1;
            background: var(--primary-soft);
            border-radius: 10px 10px 0 0;
            position: relative;
            min-height: 30px;
            transition: height 0.3s ease;
        }

        .chart-bar::before {
            content: attr(data-date);
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: var(--gray);
        }

        .chart-bar::after {
            content: attr(data-value) '%';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .chart-fill {
            height: 0%;
            background: var(--gradient);
            border-radius: 10px 10px 0 0;
            transition: height 0.3s ease;
            position: relative;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #f0fff4;
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: #fff5f5;
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        .alert i {
            font-size: 20px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 60px;
            color: var(--primary-soft);
            margin-bottom: 15px;
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
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .date-selector {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-input-group {
                width: 100%;
            }
            
            .date-input {
                width: 100%;
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

        /* Loading Indicator */
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
        <p style="color: var(--primary); font-weight: 600;">Processing attendance...</p>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Teacher Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="reply_message.php"><i class="fas fa-reply"></i> Reply Message</a></li>
                <li><a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-school" style="color: var(--primary);"></i>
                    <span style="color: var(--gray); font-size: 12px;"><?php echo htmlspecialchars($teacher['dept_name'] ?? 'Department'); ?></span>
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
                    <h1><i class="fas fa-calendar-check"></i> Attendance Management</h1>
                    <span class="date-badge">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('l, d M Y'); ?>
                    </span>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                    <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; color: var(--success); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                    <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; color: var(--danger); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo $students->num_rows; ?></div>
                        <div class="stat-desc">In your class</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Present</div>
                        <div class="stat-value"><?php echo intval($stats['present_count'] ?? 0); ?></div>
                        <div class="stat-desc">Today</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Absent</div>
                        <div class="stat-value"><?php echo intval($stats['absent_count'] ?? 0); ?></div>
                        <div class="stat-desc">Today</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Late</div>
                        <div class="stat-value"><?php echo intval($stats['late_count'] ?? 0); ?></div>
                        <div class="stat-desc">Today</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Attendance %</div>
                        <?php 
                        $total_marked = ($stats['present_count'] ?? 0) + ($stats['absent_count'] ?? 0) + ($stats['late_count'] ?? 0);
                        $percentage = $total_marked > 0 ? round(($stats['present_count'] ?? 0) / $total_marked * 100) : 0;
                        ?>
                        <div class="stat-value"><?php echo $percentage; ?>%</div>
                        <div class="stat-desc">Today</div>
                    </div>
                </div>
            </div>

            <!-- Mark Attendance Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-pen"></i> Mark Attendance</h3>
                    <span class="badge"><?php echo date('d M Y', strtotime($view_date)); ?></span>
                </div>
                <div class="card-body">
                    <!-- Date Selector -->
                    <div class="date-selector">
                        <form method="GET" class="date-selector" style="flex: 1;">
                            <div class="date-input-group">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" name="date" class="date-input" value="<?php echo $view_date; ?>" max="<?php echo $today; ?>">
                            </div>
                            <button type="submit" class="view-btn">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </form>
                        <a href="?date=<?php echo $today; ?>" class="today-btn">
                            <i class="fas fa-calendar-day"></i> Today
                        </a>
                    </div>

                    <?php if ($students->num_rows > 0): ?>
                    <form method="POST" id="attendanceForm" onsubmit="return submitAttendance(event)">
                        <input type="hidden" name="attendance_date" value="<?php echo $view_date; ?>">
                        
                        <div class="attendance-table-container">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Registration No.</th>
                                        <th>Semester</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $students->data_seek(0); ?>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                        <?php 
                                        $current_status = $attendance_data[$student['id']] ?? 'present';
                                        $initials = strtoupper(substr($student['first_name'] ?? 'S', 0, 1) . substr($student['last_name'] ?? 'T', 0, 1));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="student-info">
                                                    <div class="student-avatar">
                                                        <?php echo $initials ?: 'ST'; ?>
                                                    </div>
                                                    <div>
                                                        <div class="student-name">
                                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="student-reg"><?php echo htmlspecialchars($student['registration_number']); ?></span>
                                            </td>
                                            <td>Semester <?php echo intval($student['semester'] ?? 1); ?></td>
                                            <td>
                                                <div class="status-group">
                                                    <div class="status-radio present">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="present_<?php echo $student['id']; ?>" value="present"
                                                               <?php echo $current_status == 'present' ? 'checked' : ''; ?>>
                                                        <label for="present_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-check-circle"></i> Present
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="status-radio absent">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="absent_<?php echo $student['id']; ?>" value="absent"
                                                               <?php echo $current_status == 'absent' ? 'checked' : ''; ?>>
                                                        <label for="absent_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-times-circle"></i> Absent
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="status-radio late">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="late_<?php echo $student['id']; ?>" value="late"
                                                               <?php echo $current_status == 'late' ? 'checked' : ''; ?>>
                                                        <label for="late_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-clock"></i> Late
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="status-radio holiday">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="holiday_<?php echo $student['id']; ?>" value="holiday"
                                                               <?php echo $current_status == 'holiday' ? 'checked' : ''; ?>>
                                                        <label for="holiday_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-umbrella-beach"></i> Holiday
                                                        </label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" name="mark_attendance" class="submit-btn">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <h3>No Students Found</h3>
                            <p>There are no students assigned to your class yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Attendance & Analytics -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Attendance</h3>
                    <span class="badge">Last 7 days</span>
                </div>
                <div class="card-body">
                    <?php if ($recent_attendance->num_rows > 0): ?>
                        <div class="recent-grid">
                            <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                                <div class="recent-item <?php echo $record['status']; ?>">
                                    <div class="recent-icon">
                                        <?php 
                                        $icons = [
                                            'present' => '<i class="fas fa-check-circle" style="color: var(--success);"></i>',
                                            'absent' => '<i class="fas fa-times-circle" style="color: var(--danger);"></i>',
                                            'late' => '<i class="fas fa-clock" style="color: var(--warning);"></i>',
                                            'holiday' => '<i class="fas fa-umbrella-beach" style="color: var(--info);"></i>'
                                        ];
                                        echo $icons[$record['status']] ?? '<i class="fas fa-circle"></i>';
                                        ?>
                                    </div>
                                    <div class="recent-info">
                                        <h4><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($record['registration_number']); ?></p>
                                    </div>
                                    <div class="recent-date">
                                        <?php echo date('d M', strtotime($record['attendance_date'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No recent attendance records</p>
                        </div>
                    <?php endif; ?>

                    <!-- Monthly Chart -->
                    <div style="margin-top: 30px;">
                        <h4 style="color: var(--dark); margin-bottom: 30px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                            Monthly Attendance Trend
                        </h4>
                        
                        <?php if ($monthly_data->num_rows > 0): ?>
                            <div class="chart-container">
                                <?php 
                                $max_present = 0;
                                $monthly_data->data_seek(0);
                                $records = [];
                                while ($row = $monthly_data->fetch_assoc()) {
                                    $records[] = $row;
                                    if ($row['present'] > $max_present) $max_present = $row['present'];
                                }
                                
                                foreach ($records as $row): 
                                    $percentage = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100) : 0;
                                    $height = $max_present > 0 ? ($row['present'] / $max_present) * 100 : 0;
                                ?>
                                    <div class="chart-bar" data-date="<?php echo date('d', strtotime($row['attendance_date'])); ?>" data-value="<?php echo $percentage; ?>">
                                        <div class="chart-fill" style="height: <?php echo $height; ?>%;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-bar"></i>
                                <p>No attendance data for this month</p>
                            </div>
                        <?php endif; ?>
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

        // Submit attendance form
        async function submitAttendance(event) {
            event.preventDefault();
            
            const form = document.getElementById('attendanceForm');
            const formData = new FormData(form);
            formData.append('mark_attendance', '1');
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                // Reload page to show updated data
                window.location.reload();
                
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingOverlay').classList.remove('active');
                alert('Error saving attendance. Please try again.');
            }
            
            return false;
        }

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Quick status selector
        function setAllStatus(status) {
            const radios = document.querySelectorAll(`input[value="${status}"]`);
            radios.forEach(radio => radio.checked = true);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('button[name="mark_attendance"]')?.click();
            }
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Confirm before leaving with unsaved changes
        let formChanged = false;
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => formChanged = true);
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Quick actions menu (optional)
        function showQuickActions() {
            const actions = [
                { label: 'Mark All Present', action: () => setAllStatus('present') },
                { label: 'Mark All Absent', action: () => setAllStatus('absent') },
                { label: 'Clear All', action: () => {
                    document.querySelectorAll('input[type="radio"]').forEach(radio => radio.checked = false);
                }}
            ];
            
            // Custom dropdown implementation
            console.log('Quick actions:', actions);
        }
    </script>
</body>
</html>