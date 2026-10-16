<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
session_start();

// If already logged in, redirect to their dashboard
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
$current_step = 1;
$debug_info = array();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'validate_step') {
        $step = intval($_POST['step']);
        $reg_no = trim($_POST['reg_no'] ?? '');
        $response = ['valid' => true, 'message' => '', 'errors' => []];
        
        // Step 1: Account Info Validation
        if ($step == 1) {
            $reg_no_input = trim($_POST['reg_no'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');
            
            if (empty($reg_no_input)) $response['errors'][] = 'Registration number is required';
            if (empty($username)) $response['errors'][] = 'Username is required';
            if (empty($email)) $response['errors'][] = 'Email is required';
            if (empty($password)) $response['errors'][] = 'Password is required';
            if (empty($confirm_password)) $response['errors'][] = 'Confirm password is required';
            
            if (!empty($password)) {
                if (strlen($password) < 6) $response['errors'][] = 'Password must be at least 6 characters';
                if ($password != $confirm_password) $response['errors'][] = 'Passwords do not match';
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $response['errors'][] = 'Invalid email format';
            
            // Check if registration number is unique
            if (!empty($reg_no_input)) {
                $stmt = $conn->prepare("SELECT id FROM students WHERE registration_number = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $reg_no_input);
                    $stmt->execute();
                    $check_reg_no = $stmt->get_result();
                    if ($check_reg_no && $check_reg_no->num_rows > 0) {
                        $response['errors'][] = 'This registration number is already taken by another student';
                    }
                    $stmt->close();
                }
            }
            
            // Check if username is unique
            if (!empty($username)) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $check_user = $stmt->get_result();
                    if ($check_user && $check_user->num_rows > 0) {
                        $response['errors'][] = 'This username is already taken';
                    }
                    $stmt->close();
                }
            }
        }
        
        // Step 2: Personal Info Validation
        if ($step == 2) {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $dob = $_POST['dob'] ?? '';
            $aadhar = trim($_POST['aadhar'] ?? '');
            $gender = $_POST['gender'] ?? '';
            
            if (empty($first_name)) $response['errors'][] = 'First name is required';
            if (empty($last_name)) $response['errors'][] = 'Last name is required';
            if (empty($mobile)) $response['errors'][] = 'Mobile number is required';
            if (empty($dob)) $response['errors'][] = 'Date of birth is required';
            if (empty($aadhar)) $response['errors'][] = 'Aadhar number is required';
            if (empty($gender)) $response['errors'][] = 'Gender is required';
            
            if (strlen($aadhar) != 12) $response['errors'][] = 'Aadhar must be 12 digits';
            if (strlen($mobile) != 10) $response['errors'][] = 'Mobile must be 10 digits';
            
            // Check if Aadhar number is unique
            if (!empty($aadhar) && strlen($aadhar) == 12) {
                $stmt = $conn->prepare("SELECT id FROM students WHERE aadhar_number = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $aadhar);
                    $stmt->execute();
                    $check_aadhar = $stmt->get_result();
                    if ($check_aadhar && $check_aadhar->num_rows > 0) {
                        $response['errors'][] = 'This Aadhar number is already registered with another student';
                    }
                    $stmt->close();
                }
            }
        }
        
        // Step 3: Family Info Validation
        if ($step == 3) {
            $father_name = trim($_POST['father_name'] ?? '');
            $mother_name = trim($_POST['mother_name'] ?? '');
            
            if (empty($father_name)) $response['errors'][] = 'Father name is required';
            if (empty($mother_name)) $response['errors'][] = 'Mother name is required';
        }
        
        // Step 4: Department Validation
        if ($step == 4) {
            $department = $_POST['department'] ?? '';
            if (empty($department)) $response['errors'][] = 'Please select a department';
        }
        
        $response['valid'] = count($response['errors']) == 0;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Final Registration
    if ($action == 'complete_registration') {
        $debug_info['action'] = 'complete_registration';
        $debug_info['db_connected'] = $conn ? 'Yes' : 'No';
        $debug_info['steps'] = [];
        
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $department = $_POST['department'];
        $semester = isset($_POST['semester']) ? intval($_POST['semester']) : 1;
        $mobile = trim($_POST['mobile']);
        $father_name = trim($_POST['father_name']);
        $mother_name = trim($_POST['mother_name']);
        $aadhar = trim($_POST['aadhar']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $selected_sports = isset($_POST['sports']) ? $_POST['sports'] : [];
        $other_sport = isset($_POST['other_sport']) ? trim($_POST['other_sport']) : '';
        $selected_activities = isset($_POST['activities']) ? $_POST['activities'] : [];
        $other_activity = isset($_POST['other_activity']) ? trim($_POST['other_activity']) : '';
        
        $debug_info['steps']['input_validation'] = 'Started';
        
        // Handle video upload
        $video_file = '';
        if (isset($_FILES['video']) && $_FILES['video']['size'] > 0) {
            $target_dir = "uploads/videos/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['mp4', 'avi', 'mov', 'webm'];
            $max_size = 50 * 1024 * 1024;
            
            if ($_FILES['video']['size'] > $max_size) {
                $error = 'Video file size must be less than 50MB';
                $debug_info['steps']['video_upload'] = 'Failed - File too large';
            } elseif (!in_array($file_ext, $allowed_ext)) {
                $error = 'Video format not allowed. Use MP4, AVI, MOV, or WebM';
                $debug_info['steps']['video_upload'] = 'Failed - Invalid format: ' . $file_ext;
            } else {
                $video_filename = uniqid() . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $video_filename;
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                    $video_file = $target_file;
                    $debug_info['steps']['video_upload'] = 'Success - ' . $video_filename;
                } else {
                    $error = 'Failed to upload video file';
                    $debug_info['steps']['video_upload'] = 'Failed - Move error';
                }
            }
        } else {
            $debug_info['steps']['video_upload'] = 'Skipped - No video provided';
        }
        
        if (empty($error)) {
            // Final uniqueness check before registration
            $reg_no = trim($_POST['reg_no'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $aadhar = trim($_POST['aadhar'] ?? '');
            
            $debug_info['steps']['uniqueness_checks'] = 'Started';
            
            // Check registration number
            $stmt = $conn->prepare("SELECT id FROM students WHERE registration_number = ?");
            if ($stmt) {
                $stmt->bind_param("s", $reg_no);
                $stmt->execute();
                $check_reg = $stmt->get_result();
                if ($check_reg && $check_reg->num_rows > 0) {
                    $error = 'Registration number is already taken. Please go back and use a different number.';
                    $debug_info['steps']['reg_number_check'] = 'DUPLICATE FOUND';
                } else {
                    $debug_info['steps']['reg_number_check'] = 'OK - ' . htmlspecialchars($reg_no);
                }
                $stmt->close();
            }
            // Check email
            if (empty($error)) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $check_email = $stmt->get_result();
                    if ($check_email && $check_email->num_rows > 0) {
                        $error = 'Email is already registered. Please go back and use a different email.';
                        $debug_info['steps']['email_check'] = 'DUPLICATE FOUND';
                    } else {
                        $debug_info['steps']['email_check'] = 'OK - ' . htmlspecialchars($email);
                    }
                    $stmt->close();
                }
            }
            // Check Aadhar
            if (empty($error)) {
                $stmt = $conn->prepare("SELECT id FROM students WHERE aadhar_number = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $aadhar);
                    $stmt->execute();
                    $check_aadhar = $stmt->get_result();
                    if ($check_aadhar && $check_aadhar->num_rows > 0) {
                        $error = 'Aadhar number is already registered. Please go back and use a different Aadhar number.';
                        $debug_info['steps']['aadhar_check'] = 'DUPLICATE FOUND';
                    } else {
                        $debug_info['steps']['aadhar_check'] = 'OK - ' . htmlspecialchars($aadhar);
                    }
                    $stmt->close();
                }
            }
            
            if (empty($error)) {
                $debug_info['steps']['uniqueness_checks'] = 'All passed ✓';
            }
        }
        
        if (empty($error)) {
            $debug_info['steps']['register_user_call'] = 'Starting...';
            
            $result = registerUser([
                'reg_no' => $reg_no,
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'department' => $department,
                'semester' => $semester,
                'mobile' => $mobile,
                'father_name' => $father_name,
                'mother_name' => $mother_name,
                'aadhar' => $aadhar,
                'dob' => $dob,
                'gender' => $gender,
                'sports' => $selected_sports,
                'other_sport' => $other_sport,
                'activities' => $selected_activities,
                'other_activity' => $other_activity,
                'video_file' => $video_file
            ]);
            
            if ($result['success']) {
                $regnum = htmlspecialchars($result['registration_number'] ?? '');
                $user_id = $result['user_id'] ?? null;
                
                // Auto-login the newly registered student
                if ($user_id) {
                    // Fetch user data for session
                    $user_stmt = $conn->prepare("SELECT id, username, password, role, email, department FROM users WHERE id = ?");
                    if ($user_stmt) {
                        $user_stmt->bind_param("i", $user_id);
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        $user_stmt->close();
                        
                        if ($user_data) {
                            // Set session variables
                            $_SESSION['user_id'] = $user_data['id'];
                            $_SESSION['username'] = $user_data['username'];
                            $_SESSION['role'] = $user_data['role'];
                            $_SESSION['email'] = $user_data['email'];
                            $_SESSION['department'] = $user_data['department'];
                            
                            $debug_info['steps']['auto_login'] = 'SUCCESS - Session set for user ID: ' . $user_id;
                        }
                    }
                }
                
                $success = 'Registration successful! Your registration number is <strong>' . $regnum . '</strong>';
                if(!empty($username)) {
                    $success .= ' and your username is <strong>' . htmlspecialchars($username) . '</strong>';
                }
                $success .= '. Redirecting to your dashboard...';
                
                $debug_info['steps']['register_user_call'] = 'SUCCESS ✓';
                $debug_info['steps']['registration_number'] = $regnum;
                $debug_info['user_id'] = $result['user_id'] ?? 'N/A';
                $debug_info['timestamp'] = date('Y-m-d H:i:s');
                $debug_info['function_debug'] = $result['debug'] ?? [];
                $debug_info['redirect_to'] = 'student/dashboard.php';
                $debug_info['db_error_after_reg'] = $conn->error ?? 'No error';
                
                // Create comprehensive debug output
                $debug_output = '
                <div style="background: #e8f5e9; border: 3px solid #4caf50; border-radius: 12px; padding: 20px; margin: 20px 0; font-family: monospace; font-size: 14px;">
                    <div style="color: #2e7d32; font-weight: bold; margin-bottom: 15px; font-size: 18px;">
                        ✅ REGISTRATION SUCCESSFUL - DEBUG INFO
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                        <div style="margin: 8px 0;"><strong>Registration Number:</strong> ' . htmlspecialchars($regnum) . '</div>
                        <div style="margin: 8px 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</div>
                        <div style="margin: 8px 0;"><strong>Email:</strong> ' . htmlspecialchars($email) . '</div>
                        <div style="margin: 8px 0;"><strong>User ID:</strong> ' . ($result["user_id"] ?? "N/A") . '</div>
                        <div style="margin: 8px 0;"><strong>Department:</strong> ' . htmlspecialchars($department) . '</div>
                        <div style="margin: 8px 0;"><strong>Semester:</strong> ' . htmlspecialchars($semester) . '</div>
                        <div style="margin: 8px 0;"><strong>Status:</strong> Auto-logged in ✓</div>
                        <div style="margin: 8px 0;"><strong>Redirect:</strong> student/dashboard.php</div>
                        <div style="margin: 8px 0;"><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</div>
                    </div>
                    <div style="margin-top: 15px; color: #1976d2; font-weight: bold;">
                        ⏳ Redirecting to dashboard in 3 seconds...
                    </div>
                </div>
                ';
                
                // Show debug output
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    $success .= $debug_output;
                    $success .= '<div style="background: #f5f5f5; border: 2px solid #666; border-radius: 8px; padding: 15px; margin-top: 20px; overflow-x: auto;">
                        <div style="font-weight: bold; margin-bottom: 10px; color: #333;">📊 Full Debug Data:</div>
                        <pre style="margin: 0;">' . htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT)) . '</pre>
                    </div>';
                } else {
                    // Show simplified debug even without ?debug=1
                    $success .= $debug_output;
                }
                
                echo '<script>
                    console.log("========================================");
                    console.log("✅ REGISTRATION COMPLETE");
                    console.log("========================================");
                    console.log("Registration Number: ' . htmlspecialchars($regnum) . '");
                    console.log("Username: ' . htmlspecialchars($username) . '");
                    console.log("Email: ' . htmlspecialchars($email) . '");
                    console.log("User ID: ' . ($result["user_id"] ?? "N/A") . '");
                    console.log("Session Status: AUTO-LOGGED IN ✓");
                    console.log("Timestamp: ' . date('Y-m-d H:i:s') . '");
                    console.log("----------------------------------------");
                    console.log("🔄 Redirecting to: student/dashboard.php");
                    console.log("========================================");
                    
                    setTimeout(function() {
                        window.location.href = "student/dashboard.php";
                    }, 3000);
                </script>';
            } else {
                $error = $result['message'];
                $debug_info['steps']['register_user_call'] = 'FAILED ✗';
                $debug_info['steps']['error_message'] = $result['message'];
                $debug_info['steps']['result_data'] = $result;
                $debug_info['db_error'] = $conn->error ?? 'No error';
                
                // Always show debug when there's an error
                $error .= '<br><br><strong>🔧 DETAILED ERROR DEBUG:</strong><br>';
                $error .= '<strong>Error Message:</strong> ' . htmlspecialchars($result['message'] ?? 'Unknown') . '<br>';
                $error .= '<strong>Database Error:</strong> ' . htmlspecialchars($conn->error ?? 'No DB error') . '<br>';
                $error .= '<strong>Full Result:</strong><br>';
                $error .= '<pre style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; overflow-x: auto; max-height: 400px;">';
                $error .= htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
                $error .= '</pre>';
                
                // Show registration data that was submitted
                $error .= '<strong>Submitted Data:</strong><br>';
                $error .= 'Registration #: ' . htmlspecialchars($reg_no) . '<br>';
                $error .= 'Username: ' . htmlspecialchars($username) . '<br>';
                $error .= 'Email: ' . htmlspecialchars($email) . '<br>';
                $error .= 'Dept ID: ' . intval($department) . '<br>';
                $error .= 'Semester: ' . intval($semester) . '<br>';
                $error .= 'Mobile: ' . htmlspecialchars($mobile) . '<br>';
                $error .= 'Aadhar: ' . htmlspecialchars($aadhar) . '<br>';
                $error .= 'Gender: ' . htmlspecialchars($gender) . '<br>';
                
                $error .= '<br><strong>All Debug Info:</strong><br>';
                $error .= '<pre style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; overflow-x: auto; max-height: 400px;">';
                $error .= htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT));
                $error .= '</pre>';
            }
        }
    }
}

// Get data for dropdowns
$depts = $conn->query("SELECT * FROM departments");
$sports = $conn->query("SELECT * FROM sports ORDER BY sport_name");
$activities = $conn->query("SELECT * FROM activities ORDER BY activity_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKMSHOME-RKMPTC</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
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

        .bg-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        .bg-shape:nth-child(1) {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
            animation-delay: 0s;
        }

        .bg-shape:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        .bg-shape:nth-child(3) {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(100px, 100px) rotate(90deg); }
            50% { transform: translate(0, 200px) rotate(180deg); }
            75% { transform: translate(-100px, 100px) rotate(270deg); }
        }

        .wizard-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(255, 107, 53, 0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wizard-header {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .wizard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .wizard-header h1 { 
            font-size: 32px; 
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .wizard-header p { 
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Enhanced Progress Bar */
        .progress-bar {
            display: flex;
            justify-content: space-between;
            padding: 30px;
            background: #fff8f0;
            border-bottom: 1px solid #ffe0cc;
            position: relative;
        }
        
        .progress-bar::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 60px;
            right: 60px;
            height: 4px;
            background: linear-gradient(90deg, #ffd1b3, #ffb380);
            z-index: 0;
            border-radius: 2px;
        }
        
        .progress-step {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            border: 3px solid #ffd1b3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 2;
            position: relative;
            box-shadow: 0 4px 10px rgba(255, 107, 53, 0.1);
        }
        
        .progress-step::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            color: #666;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .progress-step:hover::after {
            opacity: 1;
        }
        
        .progress-step.active {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            color: white;
            border-color: #ff6b35;
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.4);
            transform: scale(1.1);
        }
        
        .progress-step.completed {
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }
        
        .progress-step i {
            font-size: 24px;
        }
        
        .wizard-content {
            padding: 40px;
            min-height: 500px;
        }
        
        .step { display: none; }
        .step.active { display: block; animation: fadeIn 0.3s; }
        
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        .step-title {
            font-size: 24px;
            color: #ff6b35;
            margin-bottom: 30px;
            border-bottom: 3px solid #ff6b35;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .step-title i {
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 25px;
            animation: slideInLeft 0.3s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }

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
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        label i {
            color: #ff6b35;
            margin-right: 5px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #ffe0cc;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
        }
        
        /* Checkbox and Radio Groups */
        .checkbox-group,
        .radio-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 20px;
            background: #fff8f0;
            border-radius: 12px;
            border: 2px solid #ffe0cc;
        }
        
        .checkbox-item,
        .radio-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .checkbox-item:hover,
        .radio-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.1);
        }
        
        input[type="checkbox"],
        input[type="radio"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: #ff6b35;
        }
        
        /* Button Group */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }
        
        button {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-next, .btn-submit {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .btn-next::before, .btn-submit::before {
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
        
        .btn-next:hover::before, .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-next:hover, .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.4);
        }
        
        .btn-prev {
            background: #fff8f0;
            color: #ff6b35;
            border: 2px solid #ff6b35;
        }
        
        .btn-prev:hover {
            background: #ffe0cc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.2);
        }
        
        /* Alert Messages */
        .error-message {
            background: linear-gradient(135deg, #fee 0%, #fdd 100%);
            color: #c33;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #c33;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-out;
        }

        .success-message {
            background: linear-gradient(135deg, #efe 0%, #dfd 100%);
            color: #3c3;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #3c3;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .help-text {
            font-size: 12px;
            color: #ff8c42;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .help-text i {
            color: #ff6b35;
        }
        
        /* Review Summary Table */
        .review-summary {
            background: #fff8f0;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #ffe0cc;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .review-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .review-table tr {
            border-bottom: 1px solid #ffe0cc;
        }
        
        .review-table tr:last-child {
            border-bottom: none;
        }
        
        .review-table td {
            padding: 12px;
        }
        
        .review-table td:first-child {
            font-weight: 600;
            color: #ff6b35;
            width: 40%;
        }
        
        .review-table tr:hover {
            background: rgba(255, 107, 53, 0.05);
        }
        
        /* Note Box */
        .note-box {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0cc 100%);
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #ff8c42;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .note-box i {
            color: #ff8c42;
            font-size: 24px;
        }

        .note-box strong {
            color: #ff6b35;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
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
            border: 5px solid #ffe0cc;
            border-top: 5px solid #ff6b35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background: #333;
            color: white;
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .wizard-container {
                margin: 10px;
            }

            .progress-bar {
                padding: 20px 10px;
            }

            .progress-step {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }

            .progress-step::after {
                display: none;
            }

            .wizard-content {
                padding: 20px;
            }

            .checkbox-group,
            .radio-group {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
        <div class="bg-shape"></div>
    </div>

    <!-- Loading Animation -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p style="color: #ff6b35; font-weight: 600;">Processing your registration...</p>
    </div>

    <div class="wizard-container">
        <div class="wizard-header">
            <h1><i class="fas fa-magic"></i> RKMSHOME</h1>
            <p>Complete your registration in 7 simple steps | Year <?php echo date('Y'); ?></p>
        </div>
        
        <!-- Progress Bar with Icons -->
        <div class="progress-bar">
            <div class="progress-step" data-step="1" data-tooltip="Account Info"><i class="fas fa-user"></i></div>
            <div class="progress-step" data-step="2" data-tooltip="Personal Info"><i class="fas fa-id-card"></i></div>
            <div class="progress-step" data-step="3" data-tooltip="Family Info"><i class="fas fa-users"></i></div>
            <div class="progress-step" data-step="4" data-tooltip="Department"><i class="fas fa-building"></i></div>
            <div class="progress-step" data-step="5" data-tooltip="Sports & Activities"><i class="fas fa-futbol"></i></div>
            <div class="progress-step" data-step="6" data-tooltip="Video Upload"><i class="fas fa-video"></i></div>
            <div class="progress-step" data-step="7" data-tooltip="Review"><i class="fas fa-check-circle"></i></div>
        </div>
        
        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1' && !empty($debug_info)): ?>
            <div style="padding: 20px 40px;">
                <div style="background-color: #f0f0f0; border: 2px solid #333; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <h4 style="color: #333; margin-top: 0;">🔧 DEBUG MODE</h4>
                    <pre style="background-color: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">
<?php echo htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT)); ?>
                    </pre>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="padding: 20px 40px;">
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="padding: 20px 40px;">
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="wizard-content">
            <form id="wizardForm" method="POST" enctype="multipart/form-data">
                <!-- Step 1: Account Information -->
                <div class="step active" data-step="1">
                    <h2 class="step-title"><i class="fas fa-user-circle"></i> Step 1: Account Information</h2>
                    <div class="form-group">
                        <label><i class="fas fa-qrcode"></i> Registration Number *</label>
                        <input type="text" id="reg_no" name="reg_no" required placeholder="Enter your registration number" class="tooltip">
                        <div class="help-text"><i class="fas fa-info-circle"></i> Your unique registration number provided by the college</div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Username *</label>
                        <input type="text" id="username" name="username" required placeholder="Choose a username">
                        <div class="help-text"><i class="fas fa-info-circle"></i> This will be your login identifier</div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-at"></i> Email *</label>
                        <input type="email" id="email" name="email" required placeholder="your.email@example.com">
                        <div class="help-text"><i class="fas fa-info-circle"></i> We'll send important notifications to this email</div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Min 6 characters">
                        <div class="help-text"><i class="fas fa-shield-alt"></i> Password must be at least 6 characters</div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">
                    </div>
                    <div id="step1-errors"></div>
                </div>
                
                <!-- Step 2: Personal Information -->
                <div class="step" data-step="2">
                    <h2 class="step-title"><i class="fas fa-id-card"></i> Step 2: Personal Information</h2>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Mobile Number *</label>
                        <input type="text" id="mobile" name="mobile" required placeholder="10 digits" maxlength="10" pattern="[0-9]{10}">
                        <div class="help-text"><i class="fas fa-info-circle"></i> Enter 10-digit mobile number</div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Date of Birth *</label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i> Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">-- Select Gender --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Aadhar Number *</label>
                        <input type="text" id="aadhar" name="aadhar" required placeholder="12 digits" maxlength="12" pattern="[0-9]{12}">
                        <div class="help-text"><i class="fas fa-info-circle"></i> Enter 12-digit Aadhar number</div>
                    </div>
                    <div id="step2-errors"></div>
                </div>
                
                <!-- Step 3: Family Information -->
                <div class="step" data-step="3">
                    <h2 class="step-title"><i class="fas fa-users"></i> Step 3: Family Information</h2>
                    <div class="form-group">
                        <label><i class="fas fa-male"></i> Father's Name *</label>
                        <input type="text" id="father_name" name="father_name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-female"></i> Mother's Name *</label>
                        <input type="text" id="mother_name" name="mother_name" required>
                    </div>
                    <div id="step3-errors"></div>
                </div>
                
                <!-- Step 4: Department Selection -->
                <div class="step" data-step="4">
                    <h2 class="step-title"><i class="fas fa-building"></i> Step 4: Department Selection</h2>
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Select Department *</label>
                        <select id="department" name="department" required>
                            <option value="">-- Choose a department --</option>
                            <?php while ($dept = $depts->fetch_assoc()): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div id="step4-errors"></div>
                </div>
                
                <!-- Step 5: Sports & Activities -->
                <div class="step" data-step="5">
                    <h2 class="step-title"><i class="fas fa-futbol"></i> Step 5: Sports & Activities</h2>
                    
                    <label><i class="fas fa-basketball-ball"></i> Select Sports (Optional)</label>
                    <div class="checkbox-group">
                        <?php while ($sport = $sports->fetch_assoc()): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="sport_<?php echo $sport['id']; ?>" name="sports[]" value="<?php echo $sport['id']; ?>">
                                <label for="sport_<?php echo $sport['id']; ?>" style="margin-bottom: 0;"><?php echo htmlspecialchars($sport['sport_name']); ?></label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <label style="margin-top: 20px;"><i class="fas fa-paint-brush"></i> Select Activities (Optional)</label>
                    <div class="checkbox-group">
                        <?php while ($activity = $activities->fetch_assoc()): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="activity_<?php echo $activity['id']; ?>" name="activities[]" value="<?php echo $activity['id']; ?>">
                                <label for="activity_<?php echo $activity['id']; ?>" style="margin-bottom: 0;"><?php echo htmlspecialchars($activity['activity_name']); ?></label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Step 6: Video Upload -->
                <div class="step" data-step="6">
                    <h2 class="step-title"><i class="fas fa-video"></i> Step 6: Video Upload (Optional)</h2>
                    <div class="form-group">
                        <label><i class="fas fa-upload"></i> Upload Your Video</label>
                        <input type="file" id="video" name="video" accept="video/*">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i> 
                            Supported formats: MP4, AVI, MOV, WebM (Max 50MB)
                        </div>
                    </div>
                </div>
                
                <!-- Step 7: Review & Confirmation -->
                <div class="step" data-step="7">
                    <h2 class="step-title"><i class="fas fa-check-circle"></i> Step 7: Review & Confirm</h2>
                    <div class="review-summary">
                        <p style="margin-bottom: 15px; color: #666;">
                            <i class="fas fa-search"></i> Please review your information before submitting:
                        </p>
                        <div id="review-summary"></div>
                    </div>
                    
                    <div class="note-box">
                        <i class="fas fa-lightbulb"></i>
                        <div>
                            <strong>📌 Important Note:</strong> Make sure all information is correct. 
                            You can go back to edit any step if needed. Double-check your email and mobile number.
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn-prev" id="prevBtn" style="display:none;" onclick="previousStep()">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn-next" id="nextBtn" onclick="nextStep()">
                        Next Step <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn-submit" id="submitBtn" style="display:none;" name="action" value="complete_registration" onclick="showLoading()">
                        <i class="fas fa-check-circle"></i> Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 7;

        // Show loading animation
        function showLoading() {
            document.getElementById('loading').classList.add('active');
        }

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.progress-step').forEach(el => {
                el.classList.remove('active');
                el.classList.remove('completed');
            });
            
            // Show current step
            document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
            document.querySelector(`.progress-step[data-step="${step}"]`).classList.add('active');
            
            // Mark past steps as completed
            for (let i = 1; i < step; i++) {
                document.querySelector(`.progress-step[data-step="${i}"]`).classList.add('completed');
            }
            
            // Update buttons
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'block';
            document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'block';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'block' : 'none';
            
            // Generate review summary if on last step
            if (step === 7) generateReviewSummary();
            
            currentStep = step;
        }

        async function nextStep() {
            // Skip server validation for optional steps 5 & 6
            if (currentStep >= 5 && currentStep <= 6) {
                showStep(currentStep + 1);
                return;
            }

            const form = document.getElementById('wizardForm');
            const formData = new FormData(form);
            formData.append('action', 'validate_step');
            formData.append('step', currentStep);
            
            // Show loading
            showLoading();
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                // Hide loading
                document.getElementById('loading').classList.remove('active');
                
                const errorDiv = document.getElementById(`step${currentStep}-errors`);
                errorDiv.innerHTML = '';
                
                if (!data.valid) {
                    data.errors.forEach(error => {
                        errorDiv.innerHTML += `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${error}</div>`;
                    });
                    
                    // Scroll to error
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    showStep(currentStep + 1);
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loading').classList.remove('active');
            }
        }

        function previousStep() {
            showStep(currentStep - 1);
        }

        function generateReviewSummary() {
            const form = document.getElementById('wizardForm');
            const data = new FormData(form);
            
            // Get department name
            const deptSelect = document.getElementById('department');
            const deptName = deptSelect.options[deptSelect.selectedIndex]?.text || 'Not selected';
            
            // Get selected sports
            const selectedSports = [];
            document.querySelectorAll('input[name="sports[]"]:checked').forEach(cb => {
                selectedSports.push(cb.nextElementSibling.textContent);
            });
            
            // Get selected activities
            const selectedActivities = [];
            document.querySelectorAll('input[name="activities[]"]:checked').forEach(cb => {
                selectedActivities.push(cb.nextElementSibling.textContent);
            });
            
            let summary = `
                <table class="review-table">
                    <tr>
                        <td><i class="fas fa-qrcode"></i> Registration No</td>
                        <td>${data.get('reg_no') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-user-tag"></i> Username</td>
                        <td>${data.get('username') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-envelope"></i> Email</td>
                        <td>${data.get('email') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-user"></i> Full Name</td>
                        <td>${data.get('first_name') || ''} ${data.get('last_name') || ''}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-building"></i> Department</td>
                        <td>${deptName}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-phone"></i> Mobile</td>
                        <td>${data.get('mobile') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-id-card"></i> Aadhar</td>
                        <td>${data.get('aadhar') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-calendar-alt"></i> DOB</td>
                        <td>${data.get('dob') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-male"></i> Father's Name</td>
                        <td>${data.get('father_name') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-female"></i> Mother's Name</td>
                        <td>${data.get('mother_name') || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-futbol"></i> Sports</td>
                        <td>${selectedSports.length ? selectedSports.join(', ') : 'None selected'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-paint-brush"></i> Activities</td>
                        <td>${selectedActivities.length ? selectedActivities.join(', ') : 'None selected'}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-video"></i> Video</td>
                        <td>${data.get('video')?.name ? 'File selected: ' + data.get('video').name : 'No video uploaded'}</td>
                    </tr>
                </table>
            `;
            
            document.getElementById('review-summary').innerHTML = summary;
        }

        // Real-time validation for passwords
        document.getElementById('password').addEventListener('input', function() {
            const confirm = document.getElementById('confirm_password');
            if (confirm.value && this.value !== confirm.value) {
                confirm.setCustomValidity('Passwords do not match');
            } else {
                confirm.setCustomValidity('');
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password');
            if (password.value !== this.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Mobile number validation
        document.getElementById('mobile').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        // Aadhar number validation
        document.getElementById('aadhar').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);
        });

        // File size validation
        document.getElementById('video').addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > 50 * 1024 * 1024) {
                alert('File size must be less than 50MB');
                this.value = '';
            }
        });

        // Initialize
        showStep(1);

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'ArrowRight') {
                e.preventDefault();
                if (document.getElementById('nextBtn').style.display !== 'none') {
                    nextStep();
                }
            }
            if (e.ctrlKey && e.key === 'ArrowLeft') {
                e.preventDefault();
                if (document.getElementById('prevBtn').style.display !== 'none') {
                    previousStep();
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.error-message, .success-message').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>