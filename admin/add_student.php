<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$error = '';
$success = '';

// Initialize variables to prevent undefined variable warnings
$username = '';
$email = '';
$password = '';
$first_name = '';
$last_name = '';
$registration_number = '';
$department_id = '';
$mobile = '';
$aadhar_number = '';
$dob = '';
$gender = '';
$father_name = '';
$mother_name = '';
$semester = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $registration_number = isset($_POST['registration_number']) ? trim($_POST['registration_number']) : '';
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $aadhar_number = isset($_POST['aadhar_number']) ? trim($_POST['aadhar_number']) : '';
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $father_name = isset($_POST['father_name']) ? trim($_POST['father_name']) : '';
    $mother_name = isset($_POST['mother_name']) ? trim($_POST['mother_name']) : '';
    $semester = isset($_POST['semester']) ? intval($_POST['semester']) : 1;

    // Validation
    if (!$username || !$email || !$password) {
        $error = 'Username, email, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!$first_name || !$last_name) {
        $error = 'First name and last name are required.';
    } elseif (!$registration_number) {
        $error = 'Registration number is required.';
    } elseif (!$department_id) {
        $error = 'Department is required.';
    } elseif (strlen($aadhar_number) != 12) {
        $error = 'Aadhar number must be 12 digits.';
    } elseif (strlen($mobile) != 10) {
        $error = 'Mobile number must be 10 digits.';
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        if ($check) {
            $check->bind_param('ss', $username, $email);
            $check->execute();
            $res = $check->get_result();
            if ($res->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Check if registration number already exists
                $check_reg = $conn->prepare("SELECT id FROM students WHERE registration_number=?");
                if ($check_reg) {
                    $check_reg->bind_param('s', $registration_number);
                    $check_reg->execute();
                    if ($check_reg->get_result()->num_rows > 0) {
                        $error = 'Registration number already exists.';
                    } else {
                        // Check if aadhar already exists
                        $check_aadhar = $conn->prepare("SELECT id FROM students WHERE aadhar_number=?");
                        if ($check_aadhar) {
                            $check_aadhar->bind_param('s', $aadhar_number);
                            $check_aadhar->execute();
                            if ($check_aadhar->get_result()->num_rows > 0) {
                                $error = 'Aadhar number already registered.';
                            } else {
                                // Create user account
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $user_stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'student', NOW())");
                                if ($user_stmt) {
                                    $role = 'student';
                                    $user_stmt->bind_param('sss', $username, $email, $hashed);
                                    if ($user_stmt->execute()) {
                                        $user_id = $conn->insert_id;
                                        
                                        // Create student record
                                        // gender column removed to match actual schema
                                        $student_stmt = $conn->prepare("INSERT INTO students (
                                            user_id, first_name, last_name, registration_number, department_id, 
                                            mobile, aadhar_number, date_of_birth, father_name, 
                                            mother_name, semester, created_at
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                                        
                                        if ($student_stmt) {
                                            // Adjusted to match actual students table (gender removed)
                                            $student_stmt->bind_param(
                                                'isssisssssi',
                                                $user_id, $first_name, $last_name, $registration_number, $department_id,
                                                $mobile, $aadhar_number, $dob, $father_name,
                                                $mother_name, $semester
                                            );
                                            
                                            if ($student_stmt->execute()) {
                                                $success = 'Student created successfully! Registration# ' . htmlspecialchars($registration_number);
                                                // Clear form
                                                $username = $email = $password = $first_name = $last_name = '';
                                                $registration_number = $mobile = $aadhar_number = $dob = '';
                                                $gender = $father_name = $mother_name = '';
                                                $department_id = '';
                                                $semester = 1;
                                            } else {
                                                $error = 'Failed to create student record: ' . $student_stmt->error;
                                                // Delete the user we just created
                                                $conn->query("DELETE FROM users WHERE id = $user_id");
                                            }
                                            $student_stmt->close();
                                        } else {
                                            $error = 'Prepare failed: ' . $conn->error;
                                            $conn->query("DELETE FROM users WHERE id = $user_id");
                                        }
                                    } else {
                                        $error = 'Failed to create user account: ' . $user_stmt->error;
                                    }
                                    $user_stmt->close();
                                } else {
                                    $error = 'Prepare failed: ' . $conn->error;
                                }
                            }
                            $check_aadhar->close();
                        }
                    }
                    $check_reg->close();
                }
            }
            $check->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

// Fetch departments
$departments = [];
if (isset($conn) && $conn) {
    $res = $conn->query("SELECT id, full_name FROM departments ORDER BY full_name");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $departments[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Student - RKMS Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body { background:linear-gradient(135deg,#FF9933,#800000); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
        .container { max-width:900px; width:100%; background:white; padding:2rem; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
        h1 { color:#800000; margin-bottom:1.5rem; font-size:1.75rem; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem; }
        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
        .form-group { margin-bottom:1.25rem; }
        label { display:block; margin-bottom:0.5rem; color:#333; font-weight:600; font-size:0.9rem; }
        input, select, textarea { width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:6px; font-size:0.95rem; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:#FF9933; box-shadow:0 0 0 3px rgba(255,153,51,0.1); }
        .btn { display:inline-block; padding:0.75rem 1.5rem; background:#FF9933; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.95rem; }
        .btn:hover { background:#e68a00; }
        .btn-secondary { background:#6c757d; }
        .btn-secondary:hover { background:#5a6268; }
        .alert { padding:1rem; margin-bottom:1.5rem; border-radius:6px; font-weight:500; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .section-title { color:#800000; font-size:1.1rem; font-weight:700; margin-top:2rem; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #FF9933; }
        .button-group { display:flex; gap:1rem; margin-top:2rem; }
        .links { display:flex; gap:1rem; margin-top:1.5rem; }
        .links a { text-decoration:none; color:#FF9933; font-weight:600; }
        .links a:hover { text-decoration:underline; }
        .required { color:#e74c3c; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎓 Add New Student</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <!-- Account Information -->
        <div class="section-title">Account Information</div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="registration_number">Registration Number <span class="required">*</span></label>
                <input type="text" id="registration_number" name="registration_number" value="<?php echo htmlspecialchars($registration_number ?? ''); ?>" required>
            </div>
        </div>
        
        <!-- Personal Information -->
        <div class="section-title">Personal Information</div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="dob">Date of Birth <span class="required">*</span></label>
                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender <span class="required">*</span></label>
                <select id="gender" name="gender" required>
                    <option value="">-- Select Gender --</option>
                    <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($gender === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="mobile">Mobile Number <span class="required">*</span></label>
                <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($mobile ?? ''); ?>" placeholder="10 digits" required>
            </div>
            <div class="form-group">
                <label for="aadhar_number">Aadhar Number <span class="required">*</span></label>
                <input type="text" id="aadhar_number" name="aadhar_number" pattern="[0-9]{12}" value="<?php echo htmlspecialchars($aadhar_number ?? ''); ?>" placeholder="12 digits" required>
            </div>
        </div>
        
        <!-- Family Information -->
        <div class="section-title">Family Information</div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="father_name">Father's Name <span class="required">*</span></label>
                <input type="text" id="father_name" name="father_name" value="<?php echo htmlspecialchars($father_name ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="mother_name">Mother's Name <span class="required">*</span></label>
                <input type="text" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($mother_name ?? ''); ?>" required>
            </div>
        </div>
        
        <!-- Academic Information -->
        <div class="section-title">Academic Information</div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="department_id">Department <span class="required">*</span></label>
                <select id="department_id" name="department_id" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($department_id == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="semester">Semester <span class="required">*</span></label>
                <select id="semester" name="semester" required>
                    <option value="1" <?php echo ($semester == 1) ? 'selected' : ''; ?>>Semester I</option>
                    <option value="2" <?php echo ($semester == 2) ? 'selected' : ''; ?>>Semester II</option>
                    <option value="3" <?php echo ($semester == 3) ? 'selected' : ''; ?>>Semester III</option>
                    <option value="4" <?php echo ($semester == 4) ? 'selected' : ''; ?>>Semester IV</option>
                    <option value="5" <?php echo ($semester == 5) ? 'selected' : ''; ?>>Semester V</option>
                    <option value="6" <?php echo ($semester == 6) ? 'selected' : ''; ?>>Semester VI</option>
                </select>
            </div>
        </div>
        
        <div class="button-group">
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Create Student
            </button>
            <a href="students.php" class="btn btn-secondary" style="text-decoration:none;display:inline-block;">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
    </form>
    
    <div class="links">
        <a href="students.php">← Back to Students List</a>
        <a href="dashboard.php">← Admin Dashboard</a>
    </div>
</div>
</body>
</html>
