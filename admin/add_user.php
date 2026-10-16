<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'student';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $class_year = isset($_POST['class_year']) ? trim($_POST['class_year']) : '';

    if (!$username || !$email || !$password) {
        $error = 'Username, email, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        if ($check) {
            $check->bind_param('ss', $username, $email);
            $check->execute();
            $res = $check->get_result();
            if ($res->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // insert new user
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                // For class teachers, store class_year in department field
                $dept_value = ($role === 'class_teacher') ? $class_year : $department;
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, department, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                if ($stmt) {
                    $stmt->bind_param('sssss', $username, $email, $hashed, $role, $dept_value);
                    if ($stmt->execute()) {
                        $success = 'User created successfully!';
                        $username = $email = $password = $department = $class_year = '';
                        $role = 'student';
                    } else {
                        $error = 'Failed to create user: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Prepare failed: ' . $conn->error;
                }
            }
            $check->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

// fetch departments
$departments = [];
if (isset($conn) && $conn) {
    $res = $conn->query("SELECT id, dept_name FROM departments ORDER BY dept_name");
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
<title>Add User - RKMS Home</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { background:linear-gradient(135deg,#FF9933,#800000); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
.container { max-width:600px; width:100%; background:white; padding:2rem; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
h1 { color:#800000; margin-bottom:1.5rem; font-size:1.75rem; }
.form-group { margin-bottom:1.25rem; }
label { display:block; margin-bottom:0.5rem; color:#333; font-weight:600; }
input,select { width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:6px; font-size:1rem; }
input:focus, select:focus { outline:none; border-color:#FF9933; box-shadow:0 0 0 3px rgba(255,153,51,0.1); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
@media (max-width:600px) { .form-row { grid-template-columns:1fr; } }
.btn { display:inline-block; padding:0.75rem 1.5rem; background:#FF9933; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:1rem; }
.btn:hover { background:#e68a00; }
.btn-secondary { background:#6c757d; }
.btn-secondary:hover { background:#5a6268; }
.alert { padding:1rem; margin-bottom:1.5rem; border-radius:6px; font-weight:500; }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.links { display:flex; gap:1rem; margin-top:1.5rem; }
.links a { text-decoration:none; color:#FF9933; font-weight:600; }
.links a:hover { text-decoration:underline; }
.teacher-field { display:none; }
</style>
</head>
<body>
<div class="container">
    <h1>🆕 Add New User</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="student">Student</option>
                    <option value="class_teacher">Class Teacher</option>
                    <option value="hod">HOD</option>
                    <option value="principal">Principal</option>
                    <option value="secretary">Secretary</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="department">Department</label>
            <select id="department" name="department">
                <option value="">-- Select (Optional) --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo htmlspecialchars($d['dept_name']); ?>"><?php echo htmlspecialchars($d['dept_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group teacher-field" id="class-year-field">
            <label for="class_year">Year / Class (for Class Teacher) *</label>
            <select id="class_year" name="class_year">
                <option value="">-- Select Class --</option>
                <option value="I YEAR/DME">I YEAR/DME</option>
                <option value="I YEAR/DAE">I YEAR/DAE</option>
                <option value="I YEAR/DCE">I YEAR/DCE</option>
                <option value="II YEAR/DME">II YEAR/DME</option>
                <option value="II YEAR/DAE">II YEAR/DAE</option>
                <option value="II YEAR/DCE">II YEAR/DCE</option>
                <option value="III YEAR/DME">III YEAR/DME</option>
                <option value="III YEAR/DAE">III YEAR/DAE</option>
                <option value="III YEAR/DCE">III YEAR/DCE</option>
            </select>
        </div>
        
        <div style="display:flex; gap:1rem;">
            <button type="submit" class="btn">Create User</button>
            <a href="users.php" class="btn btn-secondary" style="text-decoration:none;display:inline-block;">Back to Users</a>
        </div>
    </form>
    
    <div class="links">
        <a href="users.php">← Back to Users List</a>
    </div>
</div>

<script>
document.getElementById('role').addEventListener('change', function() {
    const classYearField = document.getElementById('class-year-field');
    if (this.value === 'class_teacher') {
        classYearField.style.display = 'block';
        document.getElementById('class_year').required = true;
    } else {
        classYearField.style.display = 'none';
        document.getElementById('class_year').required = false;
    }
});

// Check on page load in case role was already selected
window.addEventListener('load', function() {
    const role = document.getElementById('role');
    if (role.value === 'class_teacher') {
        document.getElementById('class-year-field').style.display = 'block';
    }
});
</script>
</body>
</html>
