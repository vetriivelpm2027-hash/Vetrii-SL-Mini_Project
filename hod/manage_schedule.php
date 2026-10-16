<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is HOD
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT u.* FROM users u WHERE u.id = ?");
if (!$user_stmt) {
    echo '<div style="padding:20px; background:#ffebee; border:2px solid #d32f2f;">❌ Database error: ' . htmlspecialchars($conn->error) . '</div>';
    exit;
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user || $user['role'] != 'hod') {
    header("Location: ../unauthorized.php");
    exit;
}

// Get the department for this HOD
$dept_stmt = $conn->prepare("SELECT id, full_name FROM departments WHERE hod_id = ?");
if (!$dept_stmt) {
    echo '<div style="padding:20px; background:#ffebee; border:2px solid #d32f2f;">❌ Database error: ' . htmlspecialchars($conn->error) . '</div>';
    exit;
}
$dept_stmt->bind_param("i", $user_id);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$dept_row = $dept_result->fetch_assoc();
$dept_stmt->close();

if (!$dept_row) {
    echo '<div style="padding:20px; background:#ffebee; border:2px solid #d32f2f;">❌ Error: You are not assigned as HOD of any department. Please contact admin.</div>';
    exit;
}

$user['dept_id'] = $dept_row['id'];
$user['dept_name'] = $dept_row['full_name'];

$success = '';
$error = '';

// Create class_schedules table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS class_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    subject VARCHAR(100),
    teacher_name VARCHAR(100),
    room VARCHAR(50),
    semester INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
)";
$conn->query($create_table);

// Handle form submission for creating schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $schedule_date = $_POST['schedule_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $semester = $_POST['semester'] ?? 0;

    $errors = [];
    if (empty($schedule_date)) $errors[] = "Schedule date is required";
    if (empty($start_time)) $errors[] = "Start time is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($teacher_name)) $errors[] = "Teacher name is required";
    if (empty($room)) $errors[] = "Room/venue is required";

    if (empty($errors)) {
        $insert_stmt = $conn->prepare("INSERT INTO class_schedules 
            (department_id, schedule_date, start_time, end_time, subject, teacher_name, room, semester) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insert_stmt) {
            $semester_int = intval($semester);
            $insert_stmt->bind_param("issssssi", 
                $user['dept_id'], $schedule_date, $start_time, $end_time, 
                $subject, $teacher_name, $room, $semester_int);
            if ($insert_stmt->execute()) {
                $success = "✅ Schedule added successfully!";
            } else {
                $error = "❌ Error adding schedule: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
    } else {
        $error = "❌ " . implode(", ", $errors);
    }
}

// Handle delete schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $delete_stmt = $conn->prepare("DELETE FROM class_schedules WHERE id = ? AND department_id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("ii", $schedule_id, $user['dept_id']);
        if ($delete_stmt->execute()) {
            $success = "✅ Schedule deleted successfully!";
        } else {
            $error = "❌ Error deleting schedule";
        }
        $delete_stmt->close();
    }
}

// Get all schedules for this department
$schedules = [];
$schedule_list_stmt = $conn->prepare("SELECT * FROM class_schedules 
                                      WHERE department_id = ? 
                                      ORDER BY schedule_date DESC, start_time DESC
                                      LIMIT 50");
if ($schedule_list_stmt) {
    $schedule_list_stmt->bind_param("i", $user['dept_id']);
    $schedule_list_stmt->execute();
    $schedule_result = $schedule_list_stmt->get_result();
    while ($row = $schedule_result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $schedule_list_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - HOD Portal</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #fef0e6; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(38, 24, 15, 0.95); color: #fbe9d7; padding: 2rem 0; }
        .sidebar-menu { list-style: none; padding: 1rem; }
        .sidebar-menu li { margin: 0.5rem 0; }
        .sidebar-menu a { display: block; padding: 0.8rem 1.2rem; color: #fcdbbd; text-decoration: none; border-radius: 8px; }
        .sidebar-menu a:hover { background: rgba(249, 115, 22, 0.25); }
        .sidebar-menu a.active { background: #f97316; color: white; font-weight: bold; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .topbar h1 { font-size: 1.8rem; margin: 0; }
        .user-profile { display: flex; align-items: center; gap: 0.8rem; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #f7681a, #f79453); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .card { background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header { margin-bottom: 1.5rem; border-bottom: 2px solid #f97316; padding-bottom: 1rem; }
        .card-title { font-size: 1.3rem; margin: 0; color: #2c3e4f; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #f97316; box-shadow: 0 0 5px rgba(249, 115, 22, 0.3); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #f97316; color: white; }
        .btn-primary:hover { background: #ea6e0c; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-danger:hover { background: #b71c1c; }
        .alert-success { background: #e8f5e9; border: 2px solid #4caf50; color: #2e7d32; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-error { background: #ffebee; border: 2px solid #d32f2f; color: #c62828; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .schedules-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .schedules-table th { background: #f5f5f5; padding: 1rem; text-align: left; font-weight: bold; border-bottom: 2px solid #ddd; }
        .schedules-table td { padding: 1rem; border-bottom: 1px solid #eee; }
        .schedules-table tr:hover { background: #fef0e6; }
        .action-buttons { display: flex; gap: 0.5rem; }
        .badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-info { background: #e3f2fd; color: #1976d2; }
        .badge-warning { background: #fff3e0; color: #f57c00; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div style="padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,140,70,0.3); margin-bottom: 1.5rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏆</div>
            <div style="font-weight: 600; font-size: 1.2rem;">HOD PORTAL</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="students.php">👨‍🎓 Students</a></li>
            <li><a href="teachers.php">👨‍🏫 Teachers</a></li>
            <li><a href="attendance.php">📋 Attendance</a></li>
            <li><a href="timetable.php">📅 View Schedule</a></li>
            <li><a href="manage_schedule.php" class="active">➕ Create Schedule</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <h1>📅 Manage Class Schedule</h1>
            <div class="user-profile">
                <span style="font-weight:600;">Dr. <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">➕ Add New Class Schedule</h3>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="schedule_date">Date *</label>
                        <input type="date" id="schedule_date" name="schedule_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester">
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject/Course *</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g., Data Structures" required>
                    </div>
                    <div class="form-group">
                        <label for="teacher_name">Teacher Name *</label>
                        <input type="text" id="teacher_name" name="teacher_name" placeholder="e.g., Dr. Sharma" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="room">Room/Venue *</label>
                    <input type="text" id="room" name="room" placeholder="e.g., Lab-101" required>
                </div>

                <button type="submit" name="add_schedule" class="btn btn-primary">➕ Add Schedule</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Recent Schedules (<?php echo count($schedules); ?> total)</h3>
            </div>

            <?php if (!empty($schedules)): ?>
                <table class="schedules-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Room</th>
                            <th>Semester</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo date('M d, Y', strtotime($schedule['schedule_date'])); ?></span></td>
                                <td><?php echo substr($schedule['start_time'], 0, 5); ?> <?php echo $schedule['end_time'] ? '- ' . substr($schedule['end_time'], 0, 5) : ''; ?></td>
                                <td><strong><?php echo htmlspecialchars($schedule['subject']); ?></strong></td>
                                <td><?php echo htmlspecialchars($schedule['teacher_name']); ?></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($schedule['room']); ?></span></td>
                                <td>Sem <?php echo $schedule['semester']; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" name="delete_schedule" class="btn btn-danger" onclick="return confirm('Delete this schedule?');" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; background: #fafafa; border-radius: 4px;">
                    <p style="color: #666; font-size: 1.05rem;">No schedules created yet.</p>
                    <p style="color: #999; font-size: 0.9rem;">Add your first class schedule using the form above!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>