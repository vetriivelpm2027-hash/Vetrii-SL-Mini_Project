<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is HOD
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT u.*, d.id as dept_id, d.full_name as dept_name 
                            FROM users u 
                            LEFT JOIN departments d ON d.hod_id = u.id
                            WHERE u.id = ?");
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

// Get view date
$view_date = $_GET['date'] ?? date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($view_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($view_date)));

// Get classes/schedules for the department (if a schedule table exists)
$classes = [];
$schedule_check = $conn->query("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA='rks_student_system' AND TABLE_NAME='class_schedules'");
if ($schedule_check && $schedule_check->num_rows > 0) {
    $schedule_stmt = $conn->prepare("SELECT * FROM class_schedules 
                                      WHERE department_id = ? 
                                      AND schedule_date BETWEEN ? AND ?
                                      ORDER BY schedule_date, start_time");
    if ($schedule_stmt) {
        $schedule_stmt->bind_param("iss", $user['dept_id'], $week_start, $week_end);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();
        while ($row = $schedule_result->fetch_assoc()) {
            $classes[] = $row;
        }
        $schedule_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKS HOD · Timetable | rkmshome orange advanced</title>
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts: Inter & Space Grotesk (modern, clean) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;1,14..32,400&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fef0e6;  /* soft orange background */
            color: #2c3e4f;
            line-height: 1.5;
        }

        /* === advanced dashboard container with orange atmosphere === */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: radial-gradient(circle at 80% 10%, #ffe1cd, #fce4d5 80%);
        }

        /* ========== SIDEBAR — dark orange glass, modern ========== */
        .sidebar {
            width: 280px;
            background: rgba(38, 24, 15, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid rgba(249, 115, 22, 0.45);
            box-shadow: 15px 0 40px -20px #f97316;
            display: flex;
            flex-direction: column;
            color: #fbe9d7;
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-bottom: 1px solid #f9731660;
        }

        .sidebar-logo {
            font-size: 2.6rem;
            filter: drop-shadow(0 0 8px #f78f4b);
        }

        .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            font-size: 1.4rem;
            background: linear-gradient(130deg, #fec79b, #ffb481);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            list-style: none;
            padding: 2rem 0.8rem;
            flex: 1;
        }

        .sidebar-menu li {
            margin: 0.4rem 0.2rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            padding: 0.9rem 1.2rem;
            border-radius: 40px;
            font-weight: 500;
            color: #fcdbbd;
            text-decoration: none;
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .sidebar-menu a i {
            font-size: 1.3rem;
            width: 28px;
            color: #ffa75e;
        }

        .sidebar-menu a:hover {
            background: rgba(249, 126, 39, 0.25);
            border-color: #f97316;
            color: #fff1e0;
            transform: translateX(6px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(105deg, #fa610f, #f99745);
            color: #1e130b;
            font-weight: 600;
            box-shadow: 0 10px 18px -8px #f97316, 0 0 0 2px #ffc293 inset;
        }

        .sidebar-menu a.active i {
            color: #2f1d0f;
        }

        /* small indicator */
        .badge-orange {
            background: #f97316;
            color: #221507;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.2rem 0.8rem;
            border-radius: 40px;
            margin-left: auto;
            border: 1px solid #ffb062;
        }

        /* ========== MAIN PANEL ========== */
        .main-content {
            flex: 1;
            padding: 2rem 2.5rem;
            overflow-y: auto;
        }

        /* top bar with glass + orange */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 245, 235, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 80px;
            padding: 0.7rem 1.7rem 0.7rem 2.2rem;
            margin-bottom: 2.2rem;
            border: 1px solid #ffc69b;
            box-shadow: 0 15px 25px -15px #f99154;
        }

        .topbar h1 {
            font-size: 2.1rem;
            font-weight: 700;
            background: linear-gradient(150deg, #c5540a, #e57319);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar h1 i {
            background: none;
            -webkit-text-fill-color: #f8661e;
            font-size: 2rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: #fde8d8;
            padding: 0.3rem 0.3rem 0.3rem 1.5rem;
            border-radius: 60px;
            border: 1px solid #faaa6e;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f7681a, #f79453);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.4rem;
            color: #2f1d0e;
            box-shadow: 0 8px 12px #f9731660;
        }

        /* ===== ADVANCED CARD – orange glow, glass ===== */
        .card {
            background: rgba(255, 249, 242, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 48px;
            padding: 2rem 2rem 2.2rem 2rem;
            border: 1px solid #fec29a;
            box-shadow: 0 35px 60px -25px #f47b34, 0 6px 0 0 #ffc49b inset, 0 -3px 0 0 #ffdbbd inset;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 0.5rem;
        }

        .card-title {
            font-size: 2rem;
            font-weight: 600;
            color: #3d260f;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card-title i {
            color: #ea5e0e;
            font-size: 2.4rem;
            filter: drop-shadow(0 2px 4px #f9924b);
        }

        .dept-badge {
            background: #f97316;
            color: #1e140c;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.3rem 1.5rem;
            border-radius: 50px;
            border: 1px solid #ffc896;
        }

        /* ----- filters (orange) ----- */
        .filters {
            background: #fef0e3;
            padding: 1.4rem 1.8rem;
            border-radius: 80px;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
            border: 1px solid #feb27b;
            box-shadow: 0 8px 0 -3px #fec197, 0 10px 25px -10px #f97316;
        }

        .filter-input {
            padding: 0.8rem 1.5rem;
            border: 2px solid #fed3b2;
            border-radius: 60px;
            font-size: 1rem;
            background: white;
            color: #3b2f21;
            font-weight: 500;
            outline: none;
            transition: 0.2s;
        }
        .filter-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 4px #fcd5b6;
        }

        .btn {
            background: #f97316;
            border: none;
            color: #1f150c;
            font-weight: 700;
            padding: 0.8rem 2rem;
            border-radius: 60px;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 6px 0 0 #a94809, 0 6px 15px -5px #f97316;
            transition: 0.1s ease;
            border: 1px solid #ffb166;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 0 0 #a94809, 0 12px 25px -8px #f97316;
            background: #fa8329;
        }
        .btn-secondary {
            background: #ffdcc2;
            color: #9e5019;
            box-shadow: 0 6px 0 0 #ce7b39, 0 6px 15px -5px #f97316;
        }

        /* ---------- ADVANCED TIMETABLE (orange essence) ---------- */
        .table-wrapper {
            border-radius: 40px;
            background: #fceae0;
            padding: 3px;
            border: 2px solid #fec89e;
            box-shadow: 0 20px 0 -5px #febd8f, 0 30px 40px -20px #f97316;
            overflow-x: auto;
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            border-radius: 38px;
            overflow: hidden;
            background: #fffcf5;
        }

        .timetable th {
            padding: 1.4rem 0.8rem 1rem 1.5rem;
            background: #fde3d2;
            font-weight: 700;
            color: #a04f12;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 0.8rem;
            border-bottom: 3px solid #fda75f;
            text-align: left;
        }

        .timetable td {
            padding: 1.2rem 0.8rem 1.2rem 1.5rem;
            border-bottom: 1px solid #fec69e;
            color: #462f18;
            font-weight: 500;
            vertical-align: top;
        }

        .day-header th {
            background: #fad6bc;
            font-size: 0.9rem;
            color: #7e4012;
        }

        .time-slot {
            background: #ffe7d9;
            font-weight: 600;
            color: #b3530d;
            width: 70px;
            white-space: nowrap;
        }

        .class-cell {
            background: #ffefe2;
            border-left: 6px solid #f97316;
            border-radius: 16px 0 0 16px;
            font-weight: 500;
            box-shadow: inset 0 -1px 0 #fec197;
        }

        .class-cell strong {
            color: #ad4707;
            font-size: 0.95rem;
        }

        .class-cell small {
            color: #aa5e2b;
            font-size: 0.7rem;
            display: block;
            line-height: 1.3;
        }

        /* empty week placeholder */
        .empty-week {
            margin-top: 2rem;
            padding: 3rem;
            background: #fef0e6;
            border-radius: 80px;
            text-align: center;
            border: 2px dashed #f97316;
            color: #9f581f;
            font-weight: 500;
        }

        /* footer orange */
        .footer-note {
            margin-top: 2rem;
            text-align: center;
            font-weight: 500;
            color: #b26122;
        }
        .footer-note i {
            color: #f8661e;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- SIDEBAR - rkmshome orange advanced -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🏆</div>
            <div class="sidebar-brand">RKS·HOD</div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php" class="active"><i class="fas fa-calendar-alt"></i> View Schedule</a></li>
            <li><a href="manage_schedule.php"><i class="fas fa-plus-circle"></i> Create Schedule</a></li>
            <li><a href="messages.php"><i class="fas fa-comment-dots"></i> Messages</a></li>
            <li><a href="reports.php"><i class="fas fa-file-lines"></i> Reports</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <div style="margin: 0 1.8rem 2rem; border-top: 2px dotted #f97316; padding-top: 1rem; color: #f9b889;">
            <i class="fas fa-circle" style="color:#f77f33; font-size:0.5rem;"></i> rkmshome · orange
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="topbar">
            <h1><i class="fas fa-table"></i> Weekly Timetable</h1>
            <div class="user-profile">
                <span style="font-weight:600;">Dr. <?php echo htmlspecialchars($_SESSION['username'] ?? 'Sharma'); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'S', 0, 1)); ?></div>
            </div>
        </div>

        <!-- main card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-week"></i> 
                    Department Schedule 
                    <span class="dept-badge"><i class="fas fa-building"></i> <?php echo htmlspecialchars($user['dept_name'] ?? 'Computer Science'); ?></span>
                </h3>
            </div>

            <div class="filters">
                <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
                    <label for="date" style="font-weight:600; color:#9e4f15;"><i class="far fa-calendar-alt"></i> Week of:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($view_date); ?>" class="filter-input" />
                    <button type="submit" class="btn"><i class="fas fa-search"></i> Show week</button>
                    <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Current week</a>
                </form>
                <div style="margin-left:auto; display:flex; gap:5px;">
                    <span style="background:#f97316; width:12px; height:12px; border-radius:4px;"></span> <span style="font-size:0.8rem;">class</span>
                </div>
            </div>

            <!-- timetable wrapper -->
            <div class="table-wrapper">
                <table class="timetable">
                    <thead>
                        <tr class="day-header">
                            <th>⏰ Time</th>
                            <th>📆 Monday</th>
                            <th>📆 Tuesday</th>
                            <th>📆 Wednesday</th>
                            <th>📆 Thursday</th>
                            <th>📆 Friday</th>
                            <th>📆 Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // dynamic time slots (as per original)
                        $time_slots = [
                            '08:00', '09:00', '10:00', '11:00', '12:00',
                            '13:00', '14:00', '15:00', '16:00', '17:00'
                        ];

                        // week days from monday (0) to saturday (5)
                        $weekDays = [];
                        for ($i = 0; $i < 6; $i++) {
                            $weekDays[] = date('Y-m-d', strtotime($week_start . ' +' . $i . ' days'));
                        }

                        foreach ($time_slots as $slot):
                            echo '<tr>';
                            echo '<td class="time-slot"><i class="far fa-clock" style="margin-right:6px;"></i>' . $slot . '</td>';
                            
                            foreach ($weekDays as $day):
                                $found_class = null;
                                foreach ($classes as $class) {
                                    if ($class['schedule_date'] === $day && strpos($class['start_time'] ?? '', $slot) === 0) {
                                        $found_class = $class;
                                        break;
                                    }
                                }
                                if ($found_class):
                                    echo '<td class="class-cell">';
                                    echo '<strong>' . htmlspecialchars($found_class['subject'] ?? 'Lecture') . '</strong>';
                                    echo '<small><i class="fas fa-chalkboard-teacher"></i> ' . htmlspecialchars($found_class['teacher_name'] ?? 'Staff') . '</small>';
                                    echo '<small><i class="fas fa-door-open"></i> ' . htmlspecialchars($found_class['room'] ?? 'Hall') . '</small>';
                                    echo '</td>';
                                else:
                                    echo '<td></td>';
                                endif;
                            endforeach;
                            echo '</tr>';
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($classes)): ?>
                <div class="empty-week">
                    <i class="fas fa-calendar-times" style="font-size:3rem; color:#f97316;"></i>
                    <p style="margin:1rem 0 0; font-size:1.3rem;">📅 No classes scheduled for this week</p>
                    <p style="color:#ad642b;">Administrators can manage schedules · rkmshome orange</p>
                </div>
            <?php else: ?>
                <div style="margin-top: 1.8rem; background: #fceae0; border-radius: 60px; padding: 0.8rem 2rem; display: flex; gap: 25px;">
                    <span><i class="fas fa-chalkboard" style="color:#f8661e;"></i> <?php echo count($classes); ?> sessions this week</span>
                    <span><i class="fas fa-clock"></i> week: <?php echo date('d M', strtotime($week_start)); ?> – <?php echo date('d M', strtotime($week_end)); ?></span>
                </div>
            <?php endif; ?>

        </div> <!-- end card -->

        <!-- footer with orange homage -->
        <div class="footer-note">
            <span style="background:#f9731615; padding:0.3rem 2.2rem; border-radius:60px; border:1px solid #faa86b;">
                <i class="fas fa-palette"></i> rkmshome · advanced orange · timetable
            </span>
        </div>
    </main>
</div>

<!-- simple script to keep dynamic classes (just for interactive feel) -->
<script>
    (function() {
        // small orange effect: highlight current day if exists (just ui)
        const today = new Date().toISOString().slice(0,10);
        const cells = document.querySelectorAll('.class-cell');
        // nothing else needed, all server side rendered, but we keep it smooth
        console.log('rkmshome orange timetable · advanced');
    })();
</script>
</body>
</html>