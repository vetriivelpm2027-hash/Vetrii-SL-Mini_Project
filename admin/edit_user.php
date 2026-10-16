<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin();

$user_id = intval($_GET['id']);
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $department = $conn->real_escape_string($_POST['department'] ?? '');
    
    $query = "UPDATE users SET username = '$username', email = '$email', role = '$role', department = '$department' WHERE id = $user_id";
    
    if ($conn->query($query)) {
        $success = "User updated successfully!";
        $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
    } else {
        $error = "Error updating user!";
    }
}

$depts = $conn->query("SELECT * FROM departments");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - RKS Student System</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">⚙️</div>
            <div class="sidebar-brand">ADMIN PANEL</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="students.php">👨‍🎓 Students</a></li>
            <li><a href="users.php" class="active">👥 Users</a></li>
            <li><a href="departments.php">🏢 Departments</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="export.php">📥 Export Data</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1>Edit User</h1>
            <div class="topbar-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">✏️ Edit User Information</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="secretary" <?php echo $user['role'] == 'secretary' ? 'selected' : ''; ?>>Secretary</option>
                                <option value="principal" <?php echo $user['role'] == 'principal' ? 'selected' : ''; ?>>Principal</option>
                                <option value="hod" <?php echo $user['role'] == 'hod' ? 'selected' : ''; ?>>HOD</option>
                                <option value="class_teacher" <?php echo $user['role'] == 'class_teacher' ? 'selected' : ''; ?>>Class Teacher</option>
                                <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">Select Department</option>
                                <?php while ($row = $depts->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['dept_name']); ?>" <?php echo $user['department'] == $row['dept_name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="users.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
