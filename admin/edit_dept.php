<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
requireLogin();
requireAdmin();

$error = '';
$success = '';

// Get department ID from URL
$dept_id = intval($_GET['id'] ?? 0);

if ($dept_id == 0) {
    header("Location: departments.php");
    exit();
}

// Get department details
$dept_query = $conn->query("SELECT * FROM departments WHERE id = $dept_id");
if (!$dept_query || $dept_query->num_rows == 0) {
    header("Location: departments.php?error=Department not found");
    exit();
}

$department = $dept_query->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dept_code = $conn->real_escape_string($_POST['dept_code'] ?? '');
    $dept_name = $conn->real_escape_string($_POST['dept_name'] ?? '');
    $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $hod_id = intval($_POST['hod_id'] ?? 0);

    if (empty($dept_code) || empty($dept_name) || empty($full_name)) {
        $error = '❌ Please fill all required fields';
    } else {
        // Check if department code already exists (excluding current dept)
        $check = $conn->query("SELECT id FROM departments WHERE dept_code = '$dept_code' AND id != $dept_id");
        if ($check && $check->num_rows > 0) {
            $error = '❌ Department code already exists';
        } else {
            $hod_part = $hod_id > 0 ? ", hod_id = $hod_id" : '';
            $update_query = "UPDATE departments SET 
                            dept_code = '$dept_code',
                            dept_name = '$dept_name',
                            full_name = '$full_name'
                            $hod_part
                            WHERE id = $dept_id";

            if ($conn->query($update_query)) {
                $success = '✅ Department updated successfully!';
                $department = $conn->query("SELECT * FROM departments WHERE id = $dept_id")->fetch_assoc();
            } else {
                $error = '❌ Error updating department: ' . $conn->error;
            }
        }
    }
}

// Get all HODs
$hods_query = $conn->query("SELECT u.id, u.username, s.first_name, s.last_name 
                            FROM users u 
                            LEFT JOIN students s ON u.id = s.user_id 
                            WHERE u.role = 'hod'
                            ORDER BY s.first_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department - RKS Student System</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f5f5f5;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
        }

        .sidebar h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin-bottom: 10px;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar ul li a:hover {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #f0fff4;
            color: #065f46;
            border-color: #22c55e;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: #ef4444;
        }

        .alert-info {
            background: #f0f8ff;
            color: #0c5460;
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h3>👨‍💼 Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="students.php">🎓 Students</a></li>
            <li><a href="departments.php">🏢 Departments</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="export.php">📥 Export Data</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>✏️ Edit Department</h1>
            <a href="departments.php" class="btn btn-secondary">← Back to Departments</a>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Edit Department Form -->
        <div class="card">
            <h3 class="card-title">📋 Department Information</h3>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="dept_code">Department Code *</label>
                        <input type="text" id="dept_code" name="dept_code" value="<?php echo htmlspecialchars($department['dept_code']); ?>" required placeholder="e.g., DME">
                    </div>
                    <div class="form-group">
                        <label for="dept_name">Department Name (Short) *</label>
                        <input type="text" id="dept_name" name="dept_name" value="<?php echo htmlspecialchars($department['dept_name']); ?>" required placeholder="e.g., Mechanical Engineering">
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Department Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($department['full_name']); ?>" required placeholder="e.g., Diploma in Mechanical Engineering">
                </div>

                <div class="form-group">
                    <label for="hod_id">Department HOD (Head of Department)</label>
                    <select id="hod_id" name="hod_id">
                        <option value="">-- Assign HOD --</option>
                        <?php while ($hod = $hods_query->fetch_assoc()): ?>
                            <option value="<?php echo $hod['id']; ?>" <?php echo ($department['hod_id'] == $hod['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hod['first_name'] . ' ' . $hod['last_name'] . ' (' . $hod['username'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="alert alert-info">
                    <strong>ℹ️ Note:</strong> Department code should be unique and typically 3-4 characters (e.g., DME, DAE, DCE)
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Update Department</button>
                    <a href="departments.php" class="btn btn-secondary">❌ Cancel</a>
                </div>
            </form>
        </div>

        <!-- Department Statistics -->
        <div class="card">
            <h3 class="card-title">📊 Department Statistics</h3>
            
            <?php
            $stats_query = $conn->query("SELECT 
                COUNT(s.id) as student_count,
                COUNT(u.id) as teacher_count
                FROM departments d
                LEFT JOIN students s ON d.id = s.department_id
                LEFT JOIN users u ON d.id = u.department AND u.role = 'class_teacher'
                WHERE d.id = $dept_id");
            
            $stats = $stats_query->fetch_assoc();
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div style="background: #f0f8ff; padding: 20px; border-radius: 5px; border-left: 4px solid #667eea;">
                    <div style="font-size: 24px; font-weight: bold; color: #667eea;"><?php echo $stats['student_count']; ?></div>
                    <div style="color: #666; margin-top: 5px;">Total Students</div>
                </div>
                <div style="background: #f0f8ff; padding: 20px; border-radius: 5px; border-left: 4px solid #667eea;">
                    <div style="font-size: 24px; font-weight: bold; color: #667eea;"><?php echo $stats['teacher_count']; ?></div>
                    <div style="color: #666; margin-top: 5px;">Teachers Assigned</div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
