<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin();

$student_id = intval($_GET['id']);
$student = $conn->query("SELECT s.*, d.dept_name, u.username FROM students s 
                        LEFT JOIN departments d ON s.department_id = d.id 
                        LEFT JOIN users u ON s.user_id = u.id 
                        WHERE s.id = $student_id")->fetch_assoc();

if (!$student) {
    header("Location: students.php");
    exit;
}

// Get sports and activities
$sports = $conn->query("SELECT s.* FROM sports s 
                       JOIN student_sports ss ON s.id = ss.sport_id 
                       WHERE ss.student_id = $student_id");

$activities = $conn->query("SELECT a.* FROM activities a 
                          JOIN student_activities sa ON a.id = sa.activity_id 
                          WHERE sa.student_id = $student_id");

$certificates = $conn->query("SELECT * FROM certificates WHERE student_id = $student_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - RKS Student System</title>
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
            <li><a href="students.php" class="active">👨‍🎓 Students</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="departments.php">🏢 Departments</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="export.php">📥 Export Data</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </sidebar>

    <main class="main-content">
        <div class="topbar">
            <h1>Student Details</h1>
            <div class="topbar-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Full Student Information</h3>
            </div>
            <div class="card-body">
                <div class="profile-header">
                    <div class="profile-pic">
                        <?php if ($student['profile_pic']): ?>
                            <img src="../uploads/profile_pic/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile">
                        <?php else: ?>
                            <div style="text-align: center; color: #999; line-height: 150px;">📷</div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <p><strong>Registration No:</strong> <?php echo htmlspecialchars($student['registration_number']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></p>
                        <p><strong>Semester:</strong> <?php echo $student['semester'] ?? 'N/A'; ?></p>
                    </div>
                </div>

                <hr style="margin: 20px 0;">

                <div class="form-section">
                    <h3>👤 Personal Information</h3>
                    <table>
                        <tr>
                            <td><strong>First Name:</strong></td>
                            <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Last Name:</strong></td>
                            <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mobile:</strong></td>
                            <td><?php echo htmlspecialchars($student['mobile'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Aadhar Number:</strong></td>
                            <td><?php echo htmlspecialchars($student['aadhar_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Date of Birth:</strong></td>
                            <td><?php echo htmlspecialchars($student['date_of_birth'] ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>

                <hr style="margin: 20px 0;">

                <div class="form-section">
                    <h3>👨‍👩‍👦 Family Information</h3>
                    <table>
                        <tr>
                            <td><strong>Father's Name:</strong></td>
                            <td><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mother's Name:</strong></td>
                            <td><?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>

                <hr style="margin: 20px 0;">

                <div class="form-section">
                    <h3>⚽ Sports</h3>
                    <?php if ($sports->num_rows > 0): ?>
                        <ul>
                            <?php while ($sport = $sports->fetch_assoc()): ?>
                                <li>• <?php echo htmlspecialchars($sport['sport_name']); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No sports selected</p>
                    <?php endif; ?>
                </div>

                <hr style="margin: 20px 0;">

                <div class="form-section">
                    <h3>🎭 Activities & Clubs</h3>
                    <?php if ($activities->num_rows > 0): ?>
                        <ul>
                            <?php while ($activity = $activities->fetch_assoc()): ?>
                                <li>• <?php echo htmlspecialchars($activity['activity_name']); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No activities selected</p>
                    <?php endif; ?>
                </div>

                <hr style="margin: 20px 0;">

                <div class="form-section">
                    <h3>📜 Certificates</h3>
                    <?php if ($certificates->num_rows > 0): ?>
                        <ul>
                            <?php while ($cert = $certificates->fetch_assoc()): ?>
                                <li>• <?php echo htmlspecialchars($cert['certificate_title']); ?>
                                    <a href="../uploads/certificates/<?php echo htmlspecialchars($cert['certificate_file']); ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No certificates uploaded</p>
                    <?php endif; ?>
                </div>

                <hr style="margin: 20px 0;">

                <div style="margin-top: 20px;">
                    <a href="students.php" class="btn btn-secondary">Back to Students</a>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
