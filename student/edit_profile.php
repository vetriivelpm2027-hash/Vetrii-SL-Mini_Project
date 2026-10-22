<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Get student info using prepared statement
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name, u.username, u.email as user_email 
                       FROM students s
                       LEFT JOIN departments d ON s.department_id = d.id
                       LEFT JOIN users u ON s.user_id = u.id
                       WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student) {
    // no student record - show friendly error instead of redirecting to removed unauthorized.php
    echo '<div style="padding:20px; background:#ffebee; border:2px solid #d32f2f;">'
        . '⚠️ Student record not found. Please contact an administrator.'
        . '</div>';
    exit;
}

// Check if student has permission to edit profile
$student_id = $student['id'];

// Ensure profile_edit_requests table exists
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

$has_permission = false;

// Check if profile_edit_requests table exists and student has approval
$perm_stmt = $conn->prepare("SELECT status FROM profile_edit_requests WHERE student_id = ? AND status = 'approved'");
if ($perm_stmt) {
    $perm_stmt->bind_param("i", $student_id);
    $perm_stmt->execute();
    $has_permission = $perm_stmt->get_result()->fetch_assoc();
    $perm_stmt->close();
}

// If no approval, automatically grant permission and log the approval
if (!$has_permission) {
    // create approved record so future visits skip this block
    $grant_stmt = $conn->prepare("INSERT INTO profile_edit_requests (student_id, user_id, status, approved_by_admin, admin_approval_date) VALUES (?, ?, 'approved', ?, NOW())");
    if ($grant_stmt) {
        $grant_stmt->bind_param("iii", $student_id, $user_id, $user_id);
        $grant_stmt->execute();
        $grant_stmt->close();
    }
    // set flag so rest of page proceeds
    $has_permission = ['status' => 'approved'];
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $aadhar_number = trim($_POST['aadhar_number'] ?? '');
    $other_activities = trim($_POST['other_activities'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = "Mobile number must be 10 digits";
    if (!empty($aadhar_number) && !preg_match('/^[0-9]{12}$/', $aadhar_number)) $errors[] = "Aadhar number must be 12 digits";

    if (empty($errors)) {
        // Handle profile picture upload
        $profile_pic = $student['profile_pic'] ?? '';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $target_dir = "../uploads/profile_pic/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file_ext, $allowed_ext)) {
                $error = "Invalid image format. Use JPG, PNG, GIF, or WebP";
            } elseif ($_FILES['profile_pic']['size'] > $max_size) {
                $error = "Image file size must be less than 5MB";
            } else {
                // Delete old profile picture if exists
                if (!empty($student['profile_pic']) && file_exists($target_dir . $student['profile_pic'])) {
                    unlink($target_dir . $student['profile_pic']);
                }
                
                $file_name = $student_id . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $file_name;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    $profile_pic = $file_name;
                    $success = "Profile picture uploaded successfully!";
                } else {
                    $error = "Failed to upload profile picture";
                }
            }
        }

        // Handle video upload
        $video_file = $student['video_file'] ?? '';
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $video_dir = "../uploads/videos/";
            if (!is_dir($video_dir)) {
                mkdir($video_dir, 0755, true);
            }
            
            $video_ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $allowed_video = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
            $max_video_size = 50 * 1024 * 1024; // 50MB
            
            if (!in_array($video_ext, $allowed_video)) {
                $error = "Invalid video format. Use MP4, MOV, AVI, or WebM";
            } elseif ($_FILES['video_file']['size'] > $max_video_size) {
                $error = "Video file size must be less than 50MB";
            } else {
                // Delete old video if exists
                if (!empty($student['video_file']) && file_exists($video_dir . $student['video_file'])) {
                    unlink($video_dir . $student['video_file']);
                }
                
                $video_name = $student_id . "_" . time() . "." . $video_ext;
                $video_target = $video_dir . $video_name;

                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $video_target)) {
                    $video_file = $video_name;
                    $success = "Video uploaded successfully!";
                } else {
                    $error = "Failed to upload video";
                }
            }
        }

        // Update student using prepared statement
        $update_stmt = $conn->prepare("UPDATE students SET 
                                      first_name = ?,
                                      last_name = ?,
                                      father_name = ?,
                                      mother_name = ?,
                                      email = ?,
                                      mobile = ?,
                                      date_of_birth = ?,
                                      aadhar_number = ?,
                                      profile_pic = COALESCE(?, profile_pic),
                                      video_file = COALESCE(?, video_file),
                                      other_activities = ?
                                      WHERE id = ?");
        
        if (!$update_stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $update_stmt->bind_param("sssssssssssi", 
                $first_name, $last_name, $father_name, $mother_name, 
                $email, $mobile, $dob, $aadhar_number,
                $profile_pic, $video_file, $other_activities, $student_id
            );

            if ($update_stmt->execute()) {
                if (!isset($success)) {
                    $success = "✅ Profile updated successfully!";
                }
                notifyProfileEdit($student_id, "Student profile information was changed.");
                
                // Refresh student data
                $refresh_stmt = $conn->prepare("SELECT s.*, d.full_name as dept_name FROM students s
                                              LEFT JOIN departments d ON s.department_id = d.id
                                              WHERE s.id = ?");
                if ($refresh_stmt) {
                    $refresh_stmt->bind_param("i", $student_id);
                    $refresh_stmt->execute();
                    $student = $refresh_stmt->get_result()->fetch_assoc();
                    $refresh_stmt->close();
                } else {
                    error_log("Refresh statement prepare failed: " . $conn->error);
                }
            } else {
                $error = "❌ Error updating profile: " . $conn->error;
            }
            if ($update_stmt) {
                $update_stmt->close();
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle sports update
if (isset($_POST['update_sports']) && isset($student['id'])) {
    $student_id = $student['id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Clear existing sports
        $delete_sports_stmt = $conn->prepare("DELETE FROM student_sports WHERE student_id = ?");
        $delete_sports_stmt->bind_param("i", $student_id);
        $delete_sports_stmt->execute();
        $delete_sports_stmt->close();

        // Add selected sports
        if (isset($_POST['sports']) && is_array($_POST['sports'])) {
            $insert_sport_stmt = $conn->prepare("INSERT INTO student_sports (student_id, sport_id) VALUES (?, ?)");
            foreach ($_POST['sports'] as $sport_id) {
                $sport_id_int = intval($sport_id);
                $insert_sport_stmt->bind_param("ii", $student_id, $sport_id_int);
                $insert_sport_stmt->execute();
            }
            $insert_sport_stmt->close();
        }
        
        $conn->commit();
        $success = "✅ Sports updated successfully!";
        notifyProfileEdit($student_id, "Student updated their sports selections.");
    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ Error updating sports: " . $e->getMessage();
    }
}

// Handle activities update
if (isset($_POST['update_activities'])) {
    $student_id = $student['id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Clear existing activities
        $delete_activities_stmt = $conn->prepare("DELETE FROM student_activities WHERE student_id = ?");
        $delete_activities_stmt->bind_param("i", $student_id);
        $delete_activities_stmt->execute();
        $delete_activities_stmt->close();

        // Add selected activities
        if (isset($_POST['activities']) && is_array($_POST['activities'])) {
            $insert_activity_stmt = $conn->prepare("INSERT INTO student_activities (student_id, activity_id) VALUES (?, ?)");
            foreach ($_POST['activities'] as $activity_id) {
                $activity_id_int = intval($activity_id);
                $insert_activity_stmt->bind_param("ii", $student_id, $activity_id_int);
                $insert_activity_stmt->execute();
            }
            $insert_activity_stmt->close();
        }
        
        $conn->commit();
        $success = "✅ Activities updated successfully!";
        notifyProfileEdit($student_id, "Student updated their activities selections.");
    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ Error updating activities: " . $e->getMessage();
    }
}

// Get departments for sports and activities
$sports_stmt = $conn->prepare("SELECT * FROM sports ORDER BY sport_name");
$sports_stmt->execute();
$all_sports = $sports_stmt->get_result();
$sports_stmt->close();

$activities_stmt = $conn->prepare("SELECT * FROM activities ORDER BY activity_name");
$activities_stmt->execute();
$all_activities = $activities_stmt->get_result();
$activities_stmt->close();

// Get current sports and activities
$current_sports = [];
$sports_current_stmt = $conn->prepare("SELECT sport_id FROM student_sports WHERE student_id = ?");
$sports_current_stmt->bind_param("i", $student['id']);
$sports_current_stmt->execute();
$sports_result = $sports_current_stmt->get_result();
while ($row = $sports_result->fetch_assoc()) {
    $current_sports[] = $row['sport_id'];
}
$sports_current_stmt->close();

$current_activities = [];
$activities_current_stmt = $conn->prepare("SELECT activity_id FROM student_activities WHERE student_id = ?");
$activities_current_stmt->bind_param("i", $student['id']);
$activities_current_stmt->execute();
$activities_result = $activities_current_stmt->get_result();
while ($row = $activities_result->fetch_assoc()) {
    $current_activities[] = $row['activity_id'];
}
$activities_current_stmt->close();

// Calculate profile completion
$completion = 0;
$fields = ['first_name', 'last_name', 'email', 'mobile', 'father_name', 'mother_name', 'date_of_birth', 'address', 'profile_pic', 'aadhar_number'];
foreach ($fields as $field) {
    if (!empty($student[$field])) $completion += 10;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - RKMS HOME Student Portal</title>
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

        .completion-bar {
            background: var(--primary-soft);
            height: 8px;
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
        }

        .completion-fill {
            height: 100%;
            background: var(--gradient);
            border-radius: 10px;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .completion-fill::after {
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

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #fff5e6, #fff);
            border-left: 4px solid var(--primary);
            padding: 20px 25px;
            margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInLeft 0.5s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .info-box i {
            font-size: 30px;
            color: var(--primary);
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-content strong {
            color: var(--primary);
            font-size: 16px;
        }

        .info-box-content p {
            color: var(--gray);
            font-size: 14px;
            margin-top: 5px;
        }

        .info-box a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .info-box a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #f0fff4;
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: #fff5f5;
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .alert i {
            font-size: 20px;
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
        .card:nth-child(4) { animation-delay: 0.3s; }

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

        .card-header small {
            color: var(--gray);
            font-size: 13px;
        }

        .card-body {
            padding: 25px;
        }

        /* Forms */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

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
            background: #f8fafc;
            cursor: not-allowed;
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .input-group-text {
            background: var(--primary-soft);
            padding: 12px 16px;
            color: var(--primary);
            font-weight: 600;
        }

        .input-group .form-control {
            border: none;
            border-radius: 0;
        }

        /* File Upload */
        .file-upload-area {
            border: 2px dashed var(--primary-soft);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-soft);
        }

        .file-upload-area i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .file-upload-area p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .file-upload-area small {
            color: var(--gray);
            font-size: 12px;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--primary-soft);
            border-radius: 12px;
            margin-top: 15px;
            animation: slideIn 0.3s ease;
        }

        .file-info i {
            font-size: 24px;
            color: var(--primary);
        }

        .file-info-content {
            flex: 1;
        }

        .file-info-content h4 {
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .file-info-content p {
            color: var(--gray);
            font-size: 12px;
        }

        .file-remove {
            color: var(--danger);
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .file-remove:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Current Photo */
        .current-photo {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: var(--primary-soft);
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            overflow: hidden;
            border: 3px solid white;
            box-shadow: var(--shadow-lg);
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-info {
            flex: 1;
        }

        .photo-info h4 {
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 16px;
        }

        .photo-info p {
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .photo-info small {
            color: var(--primary);
            font-weight: 500;
        }

        /* Checkbox Grid */
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .checkbox-item {
            position: relative;
        }

        .checkbox-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
        }

        .checkbox-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 15px;
            background: var(--primary-soft);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--dark);
            border: 2px solid transparent;
        }

        .checkbox-item input[type="checkbox"]:checked + label {
            background: var(--gradient);
            color: white;
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .checkbox-item input[type="checkbox"]:checked + label i {
            color: white;
        }

        .checkbox-item label i {
            color: var(--primary);
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .checkbox-item:hover label {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
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

        .btn-danger {
            background: #fee2e2;
            color: var(--danger);
            border: 2px solid var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 20px;
            backdrop-filter: blur(5px);
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .checkbox-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .current-photo {
                flex-direction: column;
                text-align: center;
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
            pointer-events: none;
        }

        /* Success Animation */
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .success-icon {
            animation: checkmark 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <p style="color: var(--primary); font-weight: 600;">Saving changes...</p>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Edit Profile</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="edit_profile.php" class="active"><i class="fas fa-edit"></i> Edit Profile</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="message_teacher.php"><i class="fas fa-paper-plane"></i> Message Teacher</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <div class="sidebar-footer">
                <div style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="color: var(--gray); font-size: 12px;">Profile Completion</span>
                        <span style="color: var(--primary); font-weight: 600; font-size: 12px;"><?php echo $completion; ?>%</span>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill" style="width: <?php echo $completion; ?>%"></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shield-alt" style="color: var(--primary);"></i>
                    <span style="color: var(--gray); font-size: 11px;">Secure & Encrypted</span>
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
                    <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
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

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div class="info-box-content">
                    <strong>Need to send a message to your class teacher?</strong>
                    <p>Use the <a href="message_teacher.php"><i class="fas fa-paper-plane"></i> Message Teacher</a> option from the sidebar. This page is only for updating your personal information and selecting sports/activities.</p>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                    <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; color: var(--success); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; color: var(--danger); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Edit Profile Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                    <small><i class="fas fa-lock"></i> Only you can edit this</small>
                </div>
                <div class="card-body">
                    <!-- Current Profile Picture -->
                    <div class="current-photo">
                        <div class="photo-preview" onclick="document.getElementById('profilePicUpload').click()" style="position: relative; cursor: pointer;">
                            <?php 
                            $profile_pic_path = "../uploads/profile_pic/" . ($student['profile_pic'] ?? '');
                            $file_exists = !empty($student['profile_pic']) && file_exists($profile_pic_path);
                            if ($file_exists): 
                            ?>
                                <img src="../uploads/profile_pic/<?php echo htmlspecialchars($student['profile_pic']); ?>?t=<?php echo time(); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="fas fa-user-graduate" style="font-size: 60px; color: var(--primary-orange); display: none;"></i>
                            <?php else: ?>
                                <i class="fas fa-user-graduate" style="font-size: 60px; color: var(--primary-orange);"></i>
                            <?php endif; ?>
                            <!-- Camera overlay icon -->
                            <div style="position: absolute; bottom: -5px; right: -5px; background: var(--primary-color); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: var(--shadow-md);">
                                <i class="fas fa-camera" style="color: white; font-size: 16px;"></i>
                            </div>
                        </div>
                        <div class="photo-info">
                            <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['mobile'] ?? 'Not provided'); ?></p>
                            <small><i class="fas fa-calendar"></i> Member since <?php echo date('M Y', strtotime($student['created_at'] ?? 'now')); ?></small>
                        </div>
                        <!-- Hidden file input for profile picture upload -->
                        <input type="file" id="profilePicUpload" name="profile_pic" accept="image/*" style="display: none;" onchange="handleProfilePicUpload(this)">
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name"><i class="fas fa-user"></i> First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['first_name'] ?? ''); ?>" 
                                       required placeholder="Enter first name">
                            </div>
                            <div class="form-group">
                                <label for="last_name"><i class="fas fa-user"></i> Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>" 
                                       required placeholder="Enter last name">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="father_name"><i class="fas fa-male"></i> Father's Name</label>
                                <input type="text" id="father_name" name="father_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" 
                                       placeholder="Enter father's name">
                            </div>
                            <div class="form-group">
                                <label for="mother_name"><i class="fas fa-female"></i> Mother's Name</label>
                                <input type="text" id="mother_name" name="mother_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>" 
                                       placeholder="Enter mother's name">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" 
                                       placeholder="Enter email address">
                            </div>
                            <div class="form-group">
                                <label for="mobile"><i class="fas fa-phone"></i> Mobile</label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['mobile'] ?? ''); ?>" 
                                       placeholder="Enter 10 digit mobile number" 
                                       pattern="[0-9]{10}" maxlength="10">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="dob"><i class="fas fa-calendar"></i> Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control" 
                                       value="<?php echo $student['date_of_birth'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="aadhar_number"><i class="fas fa-id-card"></i> Aadhar Number</label>
                                <input type="text" id="aadhar_number" name="aadhar_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['aadhar_number'] ?? ''); ?>" 
                                       placeholder="Enter 12 digit Aadhar number" 
                                       pattern="[0-9]{12}" maxlength="12">
                            </div>
                        </div>
                        </div>

                        <div class="form-group">
                            <label for="other_activities"><i class="fas fa-pencil-alt"></i> Other Activities</label>
                            <textarea id="other_activities" name="other_activities" class="form-control" 
                                      rows="3" placeholder="Mention any other activities or achievements"><?php echo htmlspecialchars($student['other_activities'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="profile_pic"><i class="fas fa-camera"></i> Profile Picture</label>
                                <div class="file-upload-area" onclick="document.getElementById('profile_pic').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload profile picture</p>
                                    <small>JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;" onchange="updateFileInfo(this, 'pic-info')">
                                </div>
                                <div id="pic-info" class="file-info" style="display: none;">
                                    <i class="fas fa-image"></i>
                                    <div class="file-info-content">
                                        <h4>New profile picture selected</h4>
                                        <p id="pic-name"></p>
                                    </div>
                                    <i class="fas fa-times file-remove" onclick="removeFile('profile_pic', 'pic-info')"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="video_file"><i class="fas fa-video"></i> Promotional Video</label>
                                <?php if (!empty($student['video_file'])): ?>
                                    <div class="file-info" id="current-video">
                                        <i class="fas fa-video"></i>
                                        <div class="file-info-content">
                                            <h4>Current video</h4>
                                            <p><?php echo htmlspecialchars($student['video_file']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="file-upload-area" onclick="document.getElementById('video_file').click()">
                                    <i class="fas fa-video"></i>
                                    <p>Click to upload promotional video</p>
                                    <small>MP4, MOV, AVI, WebM (Max 50MB)</small>
                                    <input type="file" id="video_file" name="video_file" accept="video/*" style="display: none;" onchange="updateFileInfo(this, 'video-info')">
                                </div>
                                <div id="video-info" class="file-info" style="display: none;">
                                    <i class="fas fa-video"></i>
                                    <div class="file-info-content">
                                        <h4>New video selected</h4>
                                        <p id="video-name"></p>
                                    </div>
                                    <i class="fas fa-times file-remove" onclick="removeFile('video_file', 'video-info')"></i>
                                </div>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="update_profile" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="profile.php" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Profile
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sports Selection -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-futbol"></i> Sports</h3>
                    <small>Select your preferred sports</small>
                </div>
                <div class="card-body">
                    <form method="POST" id="sportsForm">
                        <?php 
                        // Group sports by category AND deduplicate by sport_name
                        $sports_by_category = [];
                        $seen_sport_names = [];
                        if ($all_sports && $all_sports->num_rows > 0) {
                            $all_sports->data_seek(0);
                            while ($sport = $all_sports->fetch_assoc()) {
                                $sport_name = $sport['sport_name'];
                                $category = $sport['sport_name'];
                                // Only add if we haven't seen this sport name before
                                if (!in_array($sport_name, $seen_sport_names)) {
                                    if (!isset($sports_by_category[$category])) {
                                        $sports_by_category[$category] = [];
                                    }
                                    $sports_by_category[$category][] = $sport;
                                    $seen_sport_names[] = $sport_name;
                                }
                            }
                        }
                        ?>
                        
                        <!-- Category Selector -->
                        <div style="margin-bottom: 20px; display: none;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Select Sport Type:</label>
                            <select id="sportCategoryFilter" style="padding: 10px; border: 2px solid #ddd; border-radius: 6px; width: 100%; font-size: 14px; box-sizing: border-box;">
                                <option value="all">All Sports</option>
                                <?php if (count($sports_by_category) > 0): ?>
                                    <?php foreach (array_keys($sports_by_category) as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">- <?php echo htmlspecialchars($category); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Checkbox Grid -->
                        <div class="checkbox-grid">
                            <?php if (count($sports_by_category) > 0): ?>
                                <?php foreach ($sports_by_category as $category => $sports): ?>
                                    <?php foreach ($sports as $sport): ?>
                                        <div class="checkbox-item" data-category="<?php echo htmlspecialchars($category); ?>">
                                            <input type="checkbox" name="sports[]" value="<?php echo $sport['id']; ?>" 
                                                   id="sport_<?php echo $sport['id']; ?>"
                                                   <?php echo in_array($sport['id'], $current_sports) ? 'checked' : ''; ?>>
                                            <label for="sport_<?php echo $sport['id']; ?>">
                                                <i class="fas fa-futbol"></i>
                                                <?php echo htmlspecialchars($sport['sport_name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: var(--gray); text-align: center; padding: 20px;">No sports available</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add Custom Sports -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Add Other Sports:</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="customSportInput" placeholder="Type a sport name..." 
                                       style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="button" id="addSportBtn" class="btn btn-secondary" style="margin: 0;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_sports" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-save"></i> Update Sports
                        </button>
                    </form>
                    
                    <script>
                    (function() {
                        const filter = document.getElementById('sportCategoryFilter');
                        const mainCheckboxGrid = document.querySelector('.checkbox-grid');
                        if (filter) {
                            filter.addEventListener('change', function() {
                                const selectedCategory = this.value;
                                const items = document.querySelectorAll('.checkbox-item[data-category]');
                                items.forEach(item => {
                                    if (selectedCategory === 'all' || item.dataset.category === selectedCategory) {
                                        item.style.display = 'block';
                                    } else {
                                        item.style.display = 'none';
                                    }
                                });
                            });
                            // Initialize with default display
                            filter.dispatchEvent(new Event('change'));
                        }
                        
                        // Handle custom sports
                        let customSportCounter = 0;
                        const customSportInput = document.getElementById('customSportInput');
                        const addSportBtn = document.getElementById('addSportBtn');
                        
                        function addCustomSport(sportName) {
                            if (!sportName.trim()) return;
                            
                            // Convert to uppercase
                            const upperSportName = sportName.trim().toUpperCase();
                            
                            customSportCounter++;
                            const sportId = 'custom_sport_' + customSportCounter;
                            const item = document.createElement('div');
                            item.className = 'checkbox-item';
                            item.setAttribute('data-category', upperSportName);
                            item.innerHTML = `
                                <input type="checkbox" name="sports[]" value="${upperSportName}" id="${sportId}">
                                <label for="${sportId}">
                                    <i class="fas fa-futbol"></i>
                                    ${upperSportName}
                                </label>
                            `;
                            
                            // Add to MAIN checkbox grid (above), not separate container
                            mainCheckboxGrid.appendChild(item);
                            customSportInput.value = '';
                            customSportInput.focus();
                        }
                        
                        addSportBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            addCustomSport(customSportInput.value);
                        });
                        
                        customSportInput.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                addCustomSport(this.value);
                            }
                        });
                    })();
                    </script>
                </div>
            </div>

            <!-- Activities Selection -->
            <div class="card" style="display: none;">
                <div class="card-header">
                    <h3><i class="fas fa-star"></i> Activities & Clubs</h3>
                    <small>Select your preferred activities</small>
                </div>
                <div class="card-body">
                    <form method="POST" id="activitiesForm">
                        <?php 
                            // Group activities by category AND deduplicate by activity_name
                            $activities_by_category = [];
                            $seen_activity_names = [];
                            if ($all_activities && $all_activities->num_rows > 0) {
                                $all_activities->data_seek(0);
                                while ($activity = $all_activities->fetch_assoc()) {
                                    $activity_name = $activity['activity_name'];
                                    $category = $activity['activity_name'];
                                    // Only add if we haven't seen this activity name before
                                    if (!in_array($activity_name, $seen_activity_names)) {
                                        if (!isset($activities_by_category[$category])) {
                                            $activities_by_category[$category] = [];
                                        }
                                        $activities_by_category[$category][] = $activity;
                                        $seen_activity_names[] = $activity_name;
                                    }
                                }
                            }
                        ?>
                        
                        <!-- Category Selector -->
                        <div style="margin-bottom: 20px; display: none;">
                            <label for="activityCategoryFilter" style="display: block; margin-bottom: 8px; font-weight: 600;">Select Activity Type:</label>
                            <select id="activityCategoryFilter" style="padding: 10px; border: 2px solid #ddd; border-radius: 6px; width: 100%; font-size: 14px; box-sizing: border-box;">
                                <option value="all">All Activities</option>
                                <?php if (count($activities_by_category) > 0): ?>
                                    <?php foreach (array_keys($activities_by_category) as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">- <?php echo htmlspecialchars($category); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="checkbox-grid">
                            <?php if ($all_activities && $all_activities->num_rows > 0): ?>
                                <?php foreach ($activities_by_category as $category => $activities): ?>
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="checkbox-item" data-category="<?php echo htmlspecialchars($category); ?>">
                                            <input type="checkbox" name="activities[]" value="<?php echo $activity['id']; ?>" 
                                                   id="activity_<?php echo $activity['id']; ?>"
                                                   <?php echo in_array($activity['id'], $current_activities) ? 'checked' : ''; ?>>
                                            <label for="activity_<?php echo $activity['id']; ?>">
                                                <i class="fas fa-star"></i>
                                                <?php echo htmlspecialchars($activity['activity_name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: var(--gray); text-align: center; padding: 20px;">No activities available</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add Custom Activities -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Add Other Activities:</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="customActivityInput" placeholder="Type an activity name..." 
                                       style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="button" id="addActivityBtn" class="btn btn-secondary" style="margin: 0;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_activities" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-save"></i> Update Activities
                        </button>
                    </form>
                    
                    <script>
                    (function() {
                        const filter = document.getElementById('activityCategoryFilter');
                        const mainCheckboxGrid = document.querySelectorAll('.checkbox-grid')[1]; // Get second checkbox-grid for activities
                        if (filter) {
                            filter.addEventListener('change', function() {
                                const selectedCategory = this.value;
                                const items = document.querySelectorAll('.checkbox-item[data-category]');
                                items.forEach(item => {
                                    if (selectedCategory === 'all' || item.dataset.category === selectedCategory) {
                                        item.style.display = 'block';
                                    } else {
                                        item.style.display = 'none';
                                    }
                                });
                            });
                            // Initialize with default display
                            filter.dispatchEvent(new Event('change'));
                        }
                        
                        // Handle custom activities
                        let customActivityCounter = 0;
                        const customActivityInput = document.getElementById('customActivityInput');
                        const addActivityBtn = document.getElementById('addActivityBtn');
                        
                        function addCustomActivity(activityName) {
                            if (!activityName.trim()) return;
                            
                            // Convert to uppercase
                            const upperActivityName = activityName.trim().toUpperCase();
                            
                            customActivityCounter++;
                            const activityId = 'custom_activity_' + customActivityCounter;
                            const item = document.createElement('div');
                            item.className = 'checkbox-item';
                            item.setAttribute('data-category', upperActivityName);
                            item.innerHTML = `
                                <input type="checkbox" name="activities[]" value="${upperActivityName}" id="${activityId}">
                                <label for="${activityId}">
                                    <i class="fas fa-star"></i>
                                    ${upperActivityName}
                                </label>
                            `;
                            
                            // Add to MAIN checkbox grid (above), not separate container
                            mainCheckboxGrid.appendChild(item);
                            customActivityInput.value = '';
                            customActivityInput.focus();
                        }
                        
                        addActivityBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            addCustomActivity(customActivityInput.value);
                        });
                        
                        customActivityInput.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                addCustomActivity(this.value);
                            }
                        });
                    })();
                    </script>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Update file info when file is selected
        function updateFileInfo(input, infoId) {
            const fileInfo = document.getElementById(infoId);
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const nameSpan = document.getElementById(infoId === 'pic-info' ? 'pic-name' : 'video-name');
                if (nameSpan) {
                    nameSpan.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                }
                fileInfo.style.display = 'flex';
            }
        }

        // Remove selected file
        function removeFile(inputId, infoId) {
            document.getElementById(inputId).value = '';
            document.getElementById(infoId).style.display = 'none';
        }

        // Show loading overlay on form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                document.getElementById('loadingOverlay').classList.add('active');
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            ['successAlert', 'errorAlert'].forEach(id => {
                const alert = document.getElementById(id);
                if (alert) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);

        // Handle profile picture upload from camera icon
        function handleProfilePicUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const formData = new FormData();
                formData.append('profile_pic', file);
                
                // Show loading
                document.getElementById('loadingOverlay').classList.add('active');
                
                // Send to dedicated upload handler
                fetch('upload_profile_pic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Upload response:', data);
                    if (data.success) {
                        // Show success message
                        const successMsg = document.createElement('div');
                        successMsg.className = 'alert alert-success';
                        successMsg.innerHTML = '<i class="fas fa-check-circle"></i> Profile picture updated successfully!';
                        successMsg.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 15px 30px; background: #22c55e; color: white; border-radius: 8px; z-index: 10000;';
                        document.body.appendChild(successMsg);
                        
                        // Reload page after 1 second
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        document.getElementById('loadingOverlay').classList.remove('active');
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error uploading profile picture:', error);
                    document.getElementById('loadingOverlay').classList.remove('active');
                    alert('Failed to upload profile picture');
                });
                
                // Reset file input
                input.value = '';
            }
        }

        // Form validation
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            const mobile = document.getElementById('mobile').value;
            const aadhar = document.getElementById('aadhar_number').value;
            const emergency = document.getElementById('emergency_contact').value;

            if (mobile && !/^[0-9]{10}$/.test(mobile)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit mobile number');
                return false;
            }

            if (aadhar && !/^[0-9]{12}$/.test(aadhar)) {
                e.preventDefault();
                alert('Please enter a valid 12-digit Aadhar number');
                return false;
            }

            if (emergency && !/^[0-9]{10}$/.test(emergency)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit emergency contact number');
                return false;
            }
        });

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Add keyboard shortcut (Ctrl+S to save)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('button[name="update_profile"]')?.click();
            }
        });

        // Preview image before upload
        document.getElementById('profile_pic')?.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Optional: Show preview
                    console.log('Image selected');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Confirmation before leaving with unsaved changes
        let formChanged = false;
        document.querySelectorAll('form input, form select, form textarea').forEach(element => {
            element.addEventListener('change', () => formChanged = true);
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>