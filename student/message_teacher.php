<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Get student info using prepared statement
$user_id = $_SESSION['user_id'];
$student_stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name, u.email as user_email 
                               FROM students s
                               LEFT JOIN departments d ON s.department_id = d.id
                               LEFT JOIN users u ON s.user_id = u.id
                               WHERE s.user_id = ?");
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();
$student_stmt->close();

if (!$student) {
    header("Location: ../unauthorized.php");
    exit;
}

$success = '';
$error = '';

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message_type = $_POST['message_type'] ?? 'teacher';
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message_text)) {
        $error = "❌ Subject and message are required!";
    } else {
        if ($message_type === 'email') {
            // Send to email address
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                $error = "❌ Invalid email address!";
            } else {
                // Prepare email content
                $to = $email;
                $headers = "From: " . $_SESSION['email'] . "\r\n";
                $headers .= "Reply-To: " . $_SESSION['email'] . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                $email_body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
                        .email-wrapper { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; }
                        .email-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%); color: white; padding: 25px; text-align: center; }
                        .content { padding: 30px; }
                        .message-box { background: #f8f9fa; border-left: 4px solid #FF8C42; padding: 20px; margin: 20px 0; border-radius: 5px; }
                        .footer { background: #f1f3f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                        .btn { display: inline-block; padding: 10px 20px; background: #FF8C42; color: white; text-decoration: none; border-radius: 5px; }
                    </style>
                </head>
                <body>
                    <div class='email-wrapper'>
                        <div class='email-container'>
                            <div class='header'>
                                <h2 style='margin:0;'>📧 New Message from RKMS HOME</h2>
                            </div>
                            <div class='content'>
                                <p><strong>From:</strong> " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . " &lt;" . htmlspecialchars($_SESSION['email']) . "&gt;</p>
                                <p><strong>Department:</strong> " . htmlspecialchars($student['dept_name'] ?? 'N/A') . "</p>
                                <p><strong>Registration No:</strong> " . htmlspecialchars($student['registration_number'] ?? 'N/A') . "</p>
                                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                                <div class='message-box'>
                                    <p><strong>Message:</strong></p>
                                    <p>" . nl2br(htmlspecialchars($message_text)) . "</p>
                                </div>
                                <p style='text-align: center;'>
                                    <a href='mailto:" . htmlspecialchars($_SESSION['email']) . "' class='btn'>Reply to Student</a>
                                </p>
                            </div>
                            <div class='footer'>
                                <p>Sent from RKMS HOME Student Information System</p>
                                <p>This is an automated message. Please do not reply to this email.</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                if (mail($to, "[RKMS HOME] " . $subject, $email_body, $headers)) {
                    // Store sent email in database
                    $conn->query("CREATE TABLE IF NOT EXISTS sent_emails (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        sender_id INT,
                        recipient_email VARCHAR(255),
                        subject VARCHAR(255),
                        message TEXT,
                        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (sender_id) REFERENCES users(id)
                    )");
                    
                    $insert_stmt = $conn->prepare("INSERT INTO sent_emails (sender_id, recipient_email, subject, message) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("isss", $user_id, $email, $subject, $message_text);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                    
                    $success = "✅ Email sent successfully to $email!";
                } else {
                    $error = "❌ Failed to send email. Please try again.";
                }
            }
        } else {
            // Send to teacher in system
            $teacher_id = intval($_POST['teacher_id'] ?? 0);
            
            if ($teacher_id == 0) {
                $error = "❌ Please select a teacher!";
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("iiss", $user_id, $teacher_id, $subject, $message_text);
                
                if ($insert_stmt->execute()) {
                    $success = "✅ Message sent successfully to teacher!";
                } else {
                    $error = "❌ Error sending message!";
                }
                $insert_stmt->close();
            }
        }
    }
}

// Get class teachers
$teachers_stmt = $conn->prepare("SELECT u.*, t.department_id FROM users u 
                                LEFT JOIN teachers t ON u.id = t.user_id
                                WHERE u.role = 'class_teacher' 
                                ORDER BY u.username");
$teachers_stmt->execute();
$teachers_result = $teachers_stmt->get_result();
$teachers = $teachers_result;
$teacher_count = $teachers_result->num_rows;
$teachers_stmt->close();

// Get unread count
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread'];
$unread_stmt->close();

// Get recent teachers for quick select
$recent_teachers_stmt = $conn->prepare("SELECT DISTINCT u.id, u.username 
                                        FROM messages m
                                        JOIN users u ON m.sender_id = u.id
                                        WHERE m.receiver_id = ? AND u.role = 'class_teacher'
                                        ORDER BY m.created_at DESC
                                        LIMIT 3");
$recent_teachers_stmt->bind_param("i", $user_id);
$recent_teachers_stmt->execute();
$recent_teachers = $recent_teachers_stmt->get_result();
$recent_teachers_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RKMS HOME Student Portal</title>
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
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
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

        .unread-badge {
            background: var(--danger);
            color: white;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
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

        /* Alert Container */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease;
            box-shadow: var(--shadow-lg);
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

        .alert .close-btn {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }

        .alert .close-btn:hover {
            opacity: 1;
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

        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }

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

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--primary-soft);
            padding-bottom: 5px;
        }

        .tab-btn {
            padding: 12px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: var(--gray);
            border-radius: 12px 12px 0 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn i {
            font-size: 16px;
        }

        .tab-btn:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background: var(--primary-soft);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
        }

        .form-control[readonly] {
            background: var(--primary-soft);
            cursor: not-allowed;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23FF8C42' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Quick Select */
        .quick-select {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .quick-select-btn {
            padding: 8px 15px;
            background: var(--primary-soft);
            border: 1px solid var(--primary);
            border-radius: 30px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quick-select-btn:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
        }

        .quick-select-btn i {
            font-size: 12px;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #fff5e6, #fff);
            border-left: 4px solid var(--primary);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-box i {
            font-size: 24px;
            color: var(--primary);
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-content p {
            color: var(--gray);
            font-size: 13px;
            margin-top: 3px;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .messages-table {
            width: 100%;
            border-collapse: collapse;
        }

        .messages-table th {
            text-align: left;
            padding: 15px 20px;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .messages-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--primary-soft);
            color: var(--dark);
            font-size: 14px;
        }

        .messages-table tbody tr {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .messages-table tbody tr:hover {
            background: var(--primary-soft);
            transform: scale(1.01);
            box-shadow: var(--shadow-md);
        }

        .messages-table tbody tr.unread {
            background: rgba(255, 140, 66, 0.05);
            font-weight: 500;
        }

        .messages-table tbody tr.unread td:first-child {
            border-left: 4px solid var(--primary);
        }

        .messages-table tbody tr.new-message {
            animation: highlight 2s ease;
        }

        @keyframes highlight {
            0%, 100% { background: transparent; }
            50% { background: rgba(255, 140, 66, 0.2); }
        }

        /* Sender Info */
        .sender-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sender-avatar {
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

        .sender-name {
            font-weight: 600;
            color: var(--dark);
        }

        .sender-role {
            font-size: 11px;
            color: var(--gray);
        }

        /* Message Preview */
        .message-subject {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }

        .message-preview {
            font-size: 12px;
            color: var(--gray);
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge.unread {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .status-badge.read {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Date */
        .message-date {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gray);
            font-size: 12px;
            white-space: nowrap;
        }

        .message-date i {
            font-size: 12px;
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

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
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
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-row td {
            padding: 20px;
        }

        .skeleton-line {
            height: 20px;
            width: 100%;
            background: #e0e0e0;
            border-radius: 4px;
        }

        /* Message Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: slideUp 0.3s ease;
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

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--gradient);
            color: white;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
        }

        .message-detail {
            margin-bottom: 20px;
        }

        .message-detail-label {
            color: var(--gray);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .message-detail-value {
            color: var(--dark);
            font-size: 16px;
            font-weight: 500;
        }

        .message-content {
            background: var(--primary-soft);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            line-height: 1.6;
            color: var(--dark);
        }

        .reply-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--primary-soft);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .messages-table {
                display: block;
                overflow-x: auto;
            }
        }

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
                gap: 15px;
            }
            
            .page-title {
                width: 100%;
                justify-content: space-between;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
            }
            
            .messages-table td {
                min-width: 120px;
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
        .loading-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: var(--shadow-xl);
            z-index: 2000;
            align-items: center;
            gap: 15px;
        }

        .loading-indicator.active {
            display: flex;
            animation: pulse 2s infinite;
        }

        .loading-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid var(--primary-soft);
            border-top: 3px solid var(--primary);
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

    <!-- Alert Container -->
    <div class="alert-container" id="alertContainer"></div>

    <!-- Loading Indicator -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="loading-spinner"></div>
        <span style="color: var(--primary); font-weight: 600;">Processing...</span>
    </div>

    <!-- Message Modal -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-envelope-open-text"></i> Message Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Messages</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="message_teacher.php" class="active"><i class="fas fa-paper-plane"></i> Message Teacher</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shield-alt" style="color: var(--primary);"></i>
                    <span style="color: var(--gray); font-size: 12px;">Secure Messaging</span>
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
                    <h1><i class="fas fa-paper-plane"></i> Message Teacher</h1>
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge">
                            <i class="fas fa-circle"></i> <?php echo $unread_count; ?> unread
                        </span>
                    <?php endif; ?>
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

            <!-- Send Message Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> Compose Message</h3>
                    <span class="badge"><?php echo $teacher_count; ?> teachers available</span>
                </div>
                <div class="card-body">
                    <!-- Tabs -->
                    <div class="tabs">
                        <button class="tab-btn active" onclick="switchTab('teacher')">
                            <i class="fas fa-chalkboard-teacher"></i> Message Teacher
                        </button>
                        <button class="tab-btn" onclick="switchTab('email')">
                            <i class="fas fa-envelope"></i> Send Email
                        </button>
                    </div>

                    <!-- Teacher Messaging Tab -->
                    <div id="teacher-tab" class="tab-content active">
                        <?php if ($teacher_count == 0): ?>
                            <div class="info-box">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="info-box-content">
                                    <strong>No teachers available</strong>
                                    <p>Please contact the administrator to create teacher accounts.</p>
                                </div>
                            </div>
                        <?php else: ?>

                        <?php if ($recent_teachers->num_rows > 0): ?>
                            <div class="quick-select">
                                <span style="color: var(--gray); font-size: 13px;">Recent teachers:</span>
                                <?php $recent_teachers->data_seek(0); ?>
                                <?php while ($recent = $recent_teachers->fetch_assoc()): ?>
                                    <button class="quick-select-btn" onclick="selectTeacher(<?php echo $recent['id']; ?>, '<?php echo htmlspecialchars($recent['username']); ?>')">
                                        <i class="fas fa-history"></i> <?php echo htmlspecialchars($recent['username']); ?>
                                    </button>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>

                        <form id="teacherMessageForm" onsubmit="return submitTeacherMessage(event)">
                            <input type="hidden" name="message_type" value="teacher">
                            
                            <div class="form-group">
                                <label for="teacher_id">
                                    <i class="fas fa-chalkboard-teacher"></i> Select Teacher
                                </label>
                                <select id="teacher_id" name="teacher_id" class="form-control" required>
                                    <option value="">✉️ Choose a teacher...</option>
                                    <?php 
                                    $teachers->data_seek(0);
                                    while ($teacher = $teachers->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $teacher['id']; ?>">
                                            <?php echo htmlspecialchars($teacher['username']); ?>
                                            <?php if (!empty($teacher['department_id'])): ?>
                                                (<?php echo htmlspecialchars($teacher['department_id']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject_teacher">
                                    <i class="fas fa-heading"></i> Subject
                                </label>
                                <input type="text" id="subject_teacher" name="subject" class="form-control" 
                                       required placeholder="Enter message subject" maxlength="255">
                            </div>

                            <div class="form-group">
                                <label for="message_teacher">
                                    <i class="fas fa-comment"></i> Message
                                </label>
                                <textarea id="message_teacher" name="message" class="form-control" 
                                          required placeholder="Type your message here..."></textarea>
                                <div style="text-align: right; margin-top: 5px;">
                                    <span id="teacherCharCount" style="color: var(--gray); font-size: 12px;">0 characters</span>
                                </div>
                            </div>

                            <div class="info-box" style="margin-bottom: 0;">
                                <i class="fas fa-info-circle"></i>
                                <div class="info-box-content">
                                    <strong>Message will be sent to teacher's inbox</strong>
                                    <p>Teachers can reply directly through the system. You'll be notified when they respond.</p>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Send to Teacher
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- Email Messaging Tab -->
                    <div id="email-tab" class="tab-content">
                        <form id="emailMessageForm" onsubmit="return submitEmailMessage(event)">
                            <input type="hidden" name="message_type" value="email">
                            
                            <div class="form-group">
                                <label for="from_email">
                                    <i class="fas fa-envelope"></i> From (Your Email)
                                </label>
                                <input type="email" id="from_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="to_email">
                                    <i class="fas fa-envelope-open"></i> To (Recipient Email)
                                </label>
                                <input type="email" id="to_email" name="email" class="form-control" 
                                       required placeholder="Enter recipient's email address">
                            </div>

                            <div class="form-group">
                                <label for="subject_email">
                                    <i class="fas fa-heading"></i> Subject
                                </label>
                                <input type="text" id="subject_email" name="subject" class="form-control" 
                                       required placeholder="Enter email subject" maxlength="255">
                            </div>

                            <div class="form-group">
                                <label for="message_email">
                                    <i class="fas fa-envelope"></i> Message
                                </label>
                                <textarea id="message_email" name="message" class="form-control" 
                                          required placeholder="Type your email message here..." rows="6"></textarea>
                                <div style="text-align: right; margin-top: 5px;">
                                    <span id="emailCharCount" style="color: var(--gray); font-size: 12px;">0 characters</span>
                                </div>
                            </div>

                            <div class="info-box" style="margin-bottom: 0;">
                                <i class="fas fa-info-circle"></i>
                                <div class="info-box-content">
                                    <strong>HTML-formatted email will be sent</strong>
                                    <p>Recipient can reply directly to your email address. A copy will be saved in your sent emails.</p>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Send Email
                            </button>
                        </form>

                        <!-- Sent Emails History -->
                        <div style="margin-top: 40px;">
                            <h4 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: var(--dark);">
                                <i class="fas fa-history" style="color: var(--primary);"></i> Recent Sent Emails
                                <span id="sentEmailCount" style="font-size: 14px; color: var(--gray);"></span>
                            </h4>
                            
                            <div class="table-responsive">
                                <table class="messages-table" id="sentEmailsTable">
                                    <thead>
                                        <tr>
                                            <th>To</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sentEmailsTbody">
                                        <tr class="skeleton-row">
                                            <td colspan="4">
                                                <div class="skeleton-line"></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Received Messages Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-inbox"></i> Recent Messages</h3>
                    <a href="messages.php" class="badge" style="text-decoration: none;">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="messages-table" id="receivedMessagesTable">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="receivedMessagesTbody">
                                <tr class="skeleton-row">
                                    <td colspan="5">
                                        <div class="skeleton-line"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // State management
        let lastMessageCount = <?php echo $unread_count; ?>;
        let messageIds = new Set();
        let sentEmailIds = new Set();

        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Switch tabs
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (tab === 'teacher') {
                document.querySelector('.tab-btn[onclick="switchTab(\'teacher\')"]').classList.add('active');
                document.getElementById('teacher-tab').classList.add('active');
            } else {
                document.querySelector('.tab-btn[onclick="switchTab(\'email\')"]').classList.add('active');
                document.getElementById('email-tab').classList.add('active');
                loadSentEmails();
            }
        }

        // Select recent teacher
        function selectTeacher(teacherId, teacherName) {
            document.getElementById('teacher_id').value = teacherId;
            showToast(`Teacher "${teacherName}" selected`, 'info');
        }

        // Submit teacher message
        async function submitTeacherMessage(event) {
            event.preventDefault();
            
            const form = document.getElementById('teacherMessageForm');
            const formData = new FormData(form);
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                // Reset form
                form.reset();
                document.getElementById('teacherCharCount').textContent = '0 characters';
                
                showAlert('✅ Message sent successfully to teacher!', 'success');
                loadReceivedMessages();
                
            } catch (error) {
                console.error('Error:', error);
                showAlert('❌ Error sending message. Please try again.', 'error');
            } finally {
                showLoading(false);
            }
            
            return false;
        }

        // Submit email message
        async function submitEmailMessage(event) {
            event.preventDefault();
            
            const form = document.getElementById('emailMessageForm');
            const formData = new FormData(form);
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                // Reset form
                form.reset();
                document.getElementById('emailCharCount').textContent = '0 characters';
                
                showAlert('✅ Email sent successfully!', 'success');
                loadSentEmails();
                
            } catch (error) {
                console.error('Error:', error);
                showAlert('❌ Error sending email. Please try again.', 'error');
            } finally {
                showLoading(false);
            }
            
            return false;
        }

        // Load received messages
        async function loadReceivedMessages() {
            try {
                const response = await fetch('../api/get_messages.php?limit=5');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }

                const tbody = document.getElementById('receivedMessagesTbody');
                
                if (data.messages.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-envelope-open"></i>
                                    <h3>No messages yet</h3>
                                    <p>When teachers send you messages, they'll appear here.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    tbody.innerHTML = '';
                    
                    data.messages.slice(0, 5).forEach(msg => {
                        const isNew = !messageIds.has(msg.id);
                        if (isNew) messageIds.add(msg.id);
                        
                        const row = document.createElement('tr');
                        row.className = `${!msg.is_read ? 'unread' : ''} ${isNew ? 'new-message' : ''}`;
                        row.onclick = () => openMessage(msg.id);
                        
                        // Format date
                        const msgDate = new Date(msg.date);
                        const now = new Date();
                        let dateDisplay;
                        
                        if (msgDate.toDateString() === now.toDateString()) {
                            dateDisplay = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        } else {
                            dateDisplay = msgDate.toLocaleDateString([], { month: 'short', day: 'numeric' });
                        }
                        
                        row.innerHTML = `
                            <td>
                                <div class="sender-info">
                                    <div class="sender-avatar">
                                        ${msg.from.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <div class="sender-name">${msg.from}</div>
                                        <div class="sender-role">Teacher</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="message-subject">${msg.subject}</div>
                            </td>
                            <td>
                                <div class="message-preview">${msg.message}</div>
                            </td>
                            <td>
                                <div class="message-date">
                                    <i class="far fa-clock"></i>
                                    ${dateDisplay}
                                </div>
                            </td>
                            <td>
                                <span class="status-badge ${msg.is_read ? 'read' : 'unread'}">
                                    <i class="fas fa-${msg.is_read ? 'check-circle' : 'circle'}"></i>
                                    ${msg.is_read ? 'Read' : 'Unread'}
                                </span>
                            </td>
                        `;
                        
                        tbody.appendChild(row);
                    });
                }
                
            } catch (error) {
                console.error('Failed to load messages:', error);
            }
        }

        // Load sent emails
        async function loadSentEmails() {
            try {
                const response = await fetch('../api/get_sent_emails.php');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }

                const tbody = document.getElementById('sentEmailsTbody');
                const countDisplay = document.getElementById('sentEmailCount');
                
                if (data.emails.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-envelope"></i>
                                    <h3>No sent emails yet</h3>
                                    <p>When you send emails, they'll appear here.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    countDisplay.textContent = '';
                } else {
                    tbody.innerHTML = '';
                    
                    data.emails.slice(0, 5).forEach(email => {
                        const isNew = !sentEmailIds.has(email.id);
                        if (isNew) sentEmailIds.add(email.id);
                        
                        const row = document.createElement('tr');
                        row.className = isNew ? 'new-message' : '';
                        
                        // Format date
                        const emailDate = new Date(email.date);
                        const dateDisplay = emailDate.toLocaleString();
                        
                        row.innerHTML = `
                            <td>
                                <div class="sender-info">
                                    <div class="sender-avatar">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <div class="sender-name">${email.to}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="message-subject">${email.subject}</div>
                            </td>
                            <td>
                                <div class="message-preview">${email.message}</div>
                            </td>
                            <td>
                                <div class="message-date">
                                    <i class="far fa-clock"></i>
                                    ${dateDisplay}
                                </div>
                            </td>
                        `;
                        
                        tbody.appendChild(row);
                    });
                    
                    countDisplay.textContent = `(${data.emails.length} total)`;
                }
                
            } catch (error) {
                console.error('Failed to load sent emails:', error);
            }
        }

        // Open message
        async function openMessage(messageId) {
            showLoading(true);
            
            try {
                const response = await fetch(`../api/get_message.php?id=${messageId}`);
                const data = await response.json();
                
                if (data.error) {
                    showAlert('Error loading message: ' + data.error, 'error');
                    return;
                }
                
                // Mark as read
                await fetch(`../api/mark_read.php?id=${messageId}`, { method: 'POST' });
                
                // Update modal content
                const modalBody = document.getElementById('modalBody');
                const msgDate = new Date(data.date);
                
                modalBody.innerHTML = `
                    <div class="message-detail">
                        <div class="message-detail-label">From</div>
                        <div class="message-detail-value">${data.from}</div>
                    </div>
                    <div class="message-detail">
                        <div class="message-detail-label">Subject</div>
                        <div class="message-detail-value">${data.subject}</div>
                    </div>
                    <div class="message-detail">
                        <div class="message-detail-label">Date</div>
                        <div class="message-detail-value">${msgDate.toLocaleString()}</div>
                    </div>
                    <div class="message-content">
                        ${data.full_message.replace(/\n/g, '<br>')}
                    </div>
                    <div class="reply-section">
                        <button class="btn btn-secondary" onclick="replyToTeacher(${data.sender_id})">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                    </div>
                `;
                
                document.getElementById('messageModal').classList.add('active');
                
                // Refresh messages list
                loadReceivedMessages();
                
            } catch (error) {
                console.error('Failed to load message:', error);
                showAlert('Failed to load message details', 'error');
            } finally {
                showLoading(false);
            }
        }

        // Reply to teacher
        function replyToTeacher(teacherId) {
            closeModal();
            switchTab('teacher');
            
            // Select the teacher in dropdown
            const select = document.getElementById('teacher_id');
            for (let option of select.options) {
                if (option.value == teacherId) {
                    select.value = teacherId;
                    break;
                }
            }
            
            // Focus on subject
            document.getElementById('subject_teacher').focus();
        }

        // Close modal
        function closeModal() {
            document.getElementById('messageModal').classList.remove('active');
        }

        // Show alert
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <i class="fas fa-times close-btn" onclick="this.parentElement.remove()"></i>
            `;
            
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Show toast
        function showToast(message, type = 'info') {
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
            toast.innerHTML = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Show/hide loading indicator
        function showLoading(show) {
            document.getElementById('loadingIndicator').classList.toggle('active', show);
        }

        // Character counters
        document.getElementById('message_teacher')?.addEventListener('input', function() {
            document.getElementById('teacherCharCount').textContent = this.value.length + ' characters';
        });
        
        document.getElementById('message_email')?.addEventListener('input', function() {
            document.getElementById('emailCharCount').textContent = this.value.length + ' characters';
        });

        // Auto-refresh
        setInterval(loadReceivedMessages, 30000);
        setInterval(loadSentEmails, 60000);

        // Load on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadReceivedMessages();
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + 1 for teacher tab
            if (e.ctrlKey && e.key === '1') {
                e.preventDefault();
                switchTab('teacher');
            }
            // Ctrl + 2 for email tab
            if (e.ctrlKey && e.key === '2') {
                e.preventDefault();
                switchTab('email');
            }
            // Escape to close modal
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
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