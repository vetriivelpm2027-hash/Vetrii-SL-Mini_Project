<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure the user is logged in and is an admin
requireLogin();
requireAdmin();

// Handle actual export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_role']) && isset($_POST['export_type'])) {
    $export_role = $_POST['export_role'];
    $export_type = $_POST['export_type'];
    
    // Fetch data based on role
    $data = [];
    $headers = [];
    
    switch($export_role) {
        case 'students':
            $query = "SELECT u.username, s.first_name, s.last_name, s.registration_number, 
                            s.email, s.mobile, s.semester, d.full_name as department,
                            s.father_name, s.mother_name, s.aadhar_number, s.created_at
                     FROM students s
                     LEFT JOIN users u ON s.user_id = u.id
                     LEFT JOIN departments d ON s.department_id = d.id
                     ORDER BY s.semester, s.first_name";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            $headers = ['Username', 'First Name', 'Last Name', 'Registration No', 'Email', 
                       'Mobile', 'Semester', 'Department', 'Father Name', 'Mother Name', 
                       'Aadhar Number', 'Registered Date'];
            $filename = "students_export_" . date('Y-m-d');
            break;
            
        case 'teachers':
            $query = "SELECT u.username, t.first_name, t.last_name, t.qualification,
                            t.experience, d.full_name as department, t.created_at
                     FROM teachers t
                     LEFT JOIN users u ON t.user_id = u.id
                     LEFT JOIN departments d ON t.department_id = d.id
                     ORDER BY t.first_name";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            $headers = ['Username', 'First Name', 'Last Name', 'Qualification', 
                       'Experience (Years)', 'Department', 'Registered Date'];
            $filename = "teachers_export_" . date('Y-m-d');
            break;
            
        case 'hods':
            $query = "SELECT u.username, h.first_name, h.last_name, h.qualification,
                            d.full_name as department, h.created_at
                     FROM hods h
                     LEFT JOIN users u ON h.user_id = u.id
                     LEFT JOIN departments d ON h.department_id = d.id
                     ORDER BY h.first_name";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            $headers = ['Username', 'First Name', 'Last Name', 'Qualification', 
                       'Department', 'Registered Date'];
            $filename = "hods_export_" . date('Y-m-d');
            break;
            
        case 'all':
        default:
            // Students data
            $students_query = "SELECT u.username, s.first_name, s.last_name, s.registration_number, 
                                    s.email, s.mobile, s.semester, d.full_name as department,
                                    s.created_at, 'Student' as user_type
                             FROM students s
                             LEFT JOIN users u ON s.user_id = u.id
                             LEFT JOIN departments d ON s.department_id = d.id
                             ORDER BY s.first_name ASC";
            $students_result = $conn->query($students_query);
            if ($students_result) {
                while ($row = $students_result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            
            // Teachers data
            $teachers_query = "SELECT u.username, t.first_name, t.last_name, t.qualification,
                                    t.experience, d.full_name as department, t.created_at, 
                                    'Teacher' as user_type
                             FROM teachers t
                             LEFT JOIN users u ON t.user_id = u.id
                             LEFT JOIN departments d ON t.department_id = d.id
                             ORDER BY t.first_name ASC";
            $teachers_result = $conn->query($teachers_query);
            if ($teachers_result) {
                while ($row = $teachers_result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            
            // HODs data
            $hods_query = "SELECT u.username, h.first_name, h.last_name, h.qualification,
                                d.full_name as department, h.created_at, 'HOD' as user_type
                         FROM hods h
                         LEFT JOIN users u ON h.user_id = u.id
                         LEFT JOIN departments d ON h.department_id = d.id
                         ORDER BY h.first_name ASC";
            $hods_result = $conn->query($hods_query);
            if ($hods_result) {
                while ($row = $hods_result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            
            $headers = ['User Type', 'Username', 'First Name', 'Last Name', 
                       'Department', 'Registration No/Specialization', 'Email', 
                       'Mobile/Qualification', 'Created Date'];
            $filename = "complete_report_" . date('Y-m-d');
            break;
    }
    
    // Export based on type
    if ($export_type === 'excel') {
        exportToExcel($data, $headers, $filename);
    } elseif ($export_type === 'pdf') {
        exportToPDF($data, $headers, $filename);
    } elseif ($export_type === 'word') {
        exportToWord($data, $headers, $filename);
    }
    exit;
}

// Handle print preview
$print_preview = isset($_GET['print']) && $_GET['print'] == 'preview';
$print_data = isset($_GET['data']) ? $_GET['data'] : 'all';
$print_format = isset($_GET['format']) ? $_GET['format'] : 'print';

if ($print_preview) {
    // Get data based on selection
    $print_results = [];
    if ($print_data == 'all' || $print_data == 'students') {
        $students_print = $conn->query("SELECT u.username, s.first_name, s.last_name, s.registration_number, 
                                        s.email, s.mobile, s.semester, d.full_name as department,
                                        s.father_name, s.mother_name, s.aadhar_number, s.created_at
                                        FROM students s
                                        LEFT JOIN users u ON s.user_id = u.id
                                        LEFT JOIN departments d ON s.department_id = d.id
                                        ORDER BY s.semester, s.first_name");
        if ($students_print) {
            while ($row = $students_print->fetch_assoc()) {
                $print_results['students'][] = $row;
            }
        }
    }
    if ($print_data == 'all' || $print_data == 'teachers') {
        $teachers_print = $conn->query("SELECT u.username, t.first_name, t.last_name, t.qualification,
                                        t.experience, d.full_name as department, t.created_at
                                        FROM teachers t
                                        LEFT JOIN users u ON t.user_id = u.id
                                        LEFT JOIN departments d ON t.department_id = d.id
                                        ORDER BY t.first_name");
        if ($teachers_print) {
            while ($row = $teachers_print->fetch_assoc()) {
                $print_results['teachers'][] = $row;
            }
        }
    }
    if ($print_data == 'all' || $print_data == 'hods') {
        $hods_print = $conn->query("SELECT u.username, h.first_name, h.last_name, h.qualification,
                                    d.full_name as department, h.created_at
                                    FROM hods h
                                    LEFT JOIN users u ON h.user_id = u.id
                                    LEFT JOIN departments d ON h.department_id = d.id
                                    ORDER BY h.first_name");
        if ($hods_print) {
            while ($row = $hods_print->fetch_assoc()) {
                $print_results['hods'][] = $row;
            }
        }
    }
    include 'export_print_preview.php';
    exit;
}

// Export Functions
function exportToExcel($data, $headers, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, $headers, "\t");
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, array_values($row), "\t");
    }
    
    fclose($output);
    exit;
}

function exportToPDF($data, $headers, $filename) {
    // Simple HTML to PDF using browser printing
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $filename; ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            h1 {
                color: #FF8C42;
                text-align: center;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #FF8C42;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #666;
            }
            @media print {
                button { display: none; }
            }
        </style>
    </head>
    <body>
        <h1><?php echo str_replace('_', ' ', $filename); ?></h1>
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?php echo htmlspecialchars($cell); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Total Records: <?php echo count($data); ?></p>
            <p>RKMS HOME - Export Report</p>
        </div>
        
        <button onclick="window.print();" style="position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #FF8C42; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print / Save as PDF
        </button>
    </body>
    </html>
    <?php
    exit;
}

function exportToWord($data, $headers, $filename) {
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '.doc"');
    header('Cache-Control: max-age=0');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $filename; ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            h1 {
                color: #FF8C42;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #FF8C42;
                color: white;
            }
        </style>
    </head>
    <body>
        <h1><?php echo str_replace('_', ' ', $filename); ?></h1>
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?php echo htmlspecialchars($cell); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>Total Records: <?php echo count($data); ?></p>
    </body>
    </html>
    <?php
    exit;
}

// Initialize alert variables
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get statistics with error handling
$students_count = 0;
$teachers_count = 0;
$hods_count = 0;
$depts_count = 0;
$sports_count = 0;
$activities_count = 0;
$messages_count = 0;

if (isset($conn) && $conn) {
    $students_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $students_count = $students_result ? $students_result->fetch_assoc()['count'] : 0;
    
    $teachers_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'class_teacher'");
    $teachers_count = $teachers_result ? $teachers_result->fetch_assoc()['count'] : 0;
    
    $hods_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hod'");
    $hods_count = $hods_result ? $hods_result->fetch_assoc()['count'] : 0;
    
    $depts_result = $conn->query("SELECT COUNT(*) as count FROM departments");
    $depts_count = $depts_result ? $depts_result->fetch_assoc()['count'] : 0;
    
    $sports_result = $conn->query("SELECT COUNT(*) as count FROM sports");
    $sports_count = $sports_result ? $sports_result->fetch_assoc()['count'] : 0;
    
    $activities_result = $conn->query("SELECT COUNT(*) as count FROM activities");
    $activities_count = $activities_result ? $activities_result->fetch_assoc()['count'] : 0;
    
    $messages_result = $conn->query("SELECT COUNT(*) as count FROM messages");
    $messages_count = $messages_result ? $messages_result->fetch_assoc()['count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - RKMS HOME Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Keep all your existing CSS styles here */
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
            --gray-50: #f9fafb;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-soft: linear-gradient(135deg, var(--primary-soft) 0%, #fff 100%);
            --shadow-sm: 0 2px 4px rgba(255, 140, 66, 0.1);
            --shadow-md: 0 4px 6px rgba(255, 140, 66, 0.1);
            --shadow-lg: 0 10px 15px rgba(255, 140, 66, 0.1);
            --shadow-xl: 0 20px 25px rgba(255, 140, 66, 0.15);
            --shadow-2xl: 0 25px 50px -12px rgba(255, 140, 66, 0.25);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
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

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            height: 4px;
            background: var(--gradient);
            z-index: 10000;
            transition: width 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background: linear-gradient(180deg, var(--gray-900) 0%, #1a1a2e 100%);
            color: white;
            box-shadow: var(--shadow-2xl);
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
            background: rgba(255,255,255,0.1);
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
            content: '📊';
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 100px;
            opacity: 0.1;
            transform: rotate(-15deg);
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
        .sidebar-menu li:nth-child(7) { animation-delay: 0.7s; }
        .sidebar-menu li:nth-child(8) { animation-delay: 0.8s; }

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
            color: var(--gray-300);
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
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--gradient);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .sidebar-menu a:hover::before,
        .sidebar-menu a.active::before {
            transform: scaleY(1);
        }

        .sidebar-menu a i {
            width: 24px;
            font-size: 18px;
            color: var(--primary);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 140, 66, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, rgba(255, 140, 66, 0.2), transparent);
            box-shadow: var(--shadow-md);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 20px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-info i {
            color: var(--primary);
            font-size: 20px;
        }

        .admin-info-details h4 {
            color: white;
            font-size: 14px;
        }

        .admin-info-details p {
            color: var(--gray-400);
            font-size: 11px;
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

        .page-title h1 i {
            font-size: 28px;
        }

        .stats-badge {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: var(--primary-soft);
        }

        .user-profile:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .user-profile:hover span {
            color: white;
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
            position: relative;
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

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
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
            font-size: 20px;
        }

        .card-body {
            padding: 25px;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3));
            transform: skewX(-15deg) translateX(100%);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            to { transform: skewX(-15deg) translateX(-200%); }
        }

        .alert-success {
            background: #f0fff4;
            border-left-color: var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: #fff5f5;
            border-left-color: var(--danger);
            color: var(--danger);
        }

        .alert-info {
            background: #f0f8ff;
            border-left-color: var(--info);
            color: var(--info);
        }

        .alert i {
            font-size: 20px;
        }

        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23FF8C42' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
        }

        /* Button Styles */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 30px;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 10px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            flex: 1;
            min-width: 200px;
        }

        .btn::before {
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
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .btn i {
            font-size: 16px;
        }

        /* Export Preview Cards */
        .export-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .preview-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid var(--primary-soft);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        .preview-card:nth-child(1) { animation-delay: 0.1s; }
        .preview-card:nth-child(2) { animation-delay: 0.2s; }
        .preview-card:nth-child(3) { animation-delay: 0.3s; }
        .preview-card:nth-child(4) { animation-delay: 0.4s; }
        .preview-card:nth-child(5) { animation-delay: 0.5s; }
        .preview-card:nth-child(6) { animation-delay: 0.6s; }
        .preview-card:nth-child(7) { animation-delay: 0.7s; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .preview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient);
        }

        .preview-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .preview-card h4 {
            color: var(--gray-600);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-card h4 i {
            color: var(--primary);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-500);
            font-size: 12px;
        }

        /* Guide Cards */
        .guide-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .guide-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--primary-soft);
            transition: all 0.3s ease;
        }

        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .guide-card h4 {
            color: var(--primary);
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guide-card h4 i {
            font-size: 20px;
        }

        .guide-card ul {
            list-style: none;
        }

        .guide-card ul li {
            padding: 8px 0;
            color: var(--gray-600);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px dashed var(--gray-200);
        }

        .guide-card ul li:last-child {
            border-bottom: none;
        }

        .guide-card ul li i {
            color: var(--success);
            font-size: 12px;
        }

        /* Info List */
        .info-list {
            list-style: none;
            margin: 10px 0 0 0;
        }

        .info-list li {
            padding: 10px 0;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list li i {
            color: var(--primary);
            font-size: 16px;
        }

        .info-list li strong {
            color: var(--dark);
            margin-right: 5px;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
            gap: 20px;
        }

        .loading-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid var(--primary-soft);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: var(--primary);
            font-weight: 600;
            font-size: 16px;
        }

        /* Print-Specific Styles */
        @media print {
            .sidebar, .top-bar, .btn, .alert, .guide-grid, .button-group, .menu-toggle, .user-profile {
                display: none !important;
            }
            
            .main-content {
                margin: 0;
                padding: 20px;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            
            .card::before {
                display: none;
            }
            
            .export-preview {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .preview-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .stat-number {
                color: #000;
            }
            
            h1, h2, h3, h4 {
                color: #000 !important;
            }
            
            @page {
                size: A4;
                margin: 2cm;
            }
        }

        /* Print Preview Button */
        .print-preview-btn {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .print-preview-btn:hover {
            background: var(--gradient);
            color: white;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-brand,
            .sidebar-menu a span:not(.fa, .fas, .far) {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar-menu a i {
                margin: 0;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .sidebar:hover {
                width: 300px;
            }
            
            .sidebar:hover .sidebar-brand,
            .sidebar:hover .sidebar-menu a span {
                display: inline;
            }
            
            .sidebar:hover .sidebar-menu a {
                justify-content: flex-start;
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
            }
            
            .page-title {
                width: 100%;
                justify-content: space-between;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .export-preview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .guide-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .export-preview {
                grid-template-columns: 1fr;
            }
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 12px;
            background: var(--dark);
            color: white;
            font-size: 12px;
            border-radius: 8px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: var(--shadow-lg);
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            transform: translateX(-50%) translateY(-5px);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }
    </style>
</head>
<body>
    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Generating Report...</div>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-download"></i>
                </div>
                <div class="sidebar-brand">RKMS HOME</div>
                <div class="sidebar-subtitle">Admin Portal</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                <li><a href="departments.php"><i class="fas fa-building"></i> <span>Departments</span></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
                <li><a href="export.php" class="active"><i class="fas fa-download"></i> <span>Export Data</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="admin-info">
                    <i class="fas fa-shield-alt"></i>
                    <div class="admin-info-details">
                        <h4><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></h4>
                        <p>Administrator</p>
                    </div>
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
                    <h1><i class="fas fa-download"></i> Export Data</h1>
                    <span class="stats-badge">
                        <i class="fas fa-database"></i> System Data
                    </span>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'A'; ?>
                    </div>
                    <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Export Form Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Export Configuration</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="exportForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="export_role">
                                    <i class="fas fa-database"></i>
                                    Select Data to Export
                                </label>
                                <select name="export_role" id="export_role" class="form-control" required>
                                    <option value="all">📋 Complete Report (All Data)</option>
                                    <option value="students">👥 Students Only</option>
                                    <option value="teachers">👨‍🏫 Teachers Only</option>
                                    <option value="hods">🎓 HODs Only</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="export_type">
                                    <i class="fas fa-file-export"></i>
                                    Export Format
                                </label>
                                <select name="export_type" id="export_type" class="form-control" required>
                                    <option value="">-- Select Format --</option>
                                    <option value="excel">📊 Excel Spreadsheet (.xls)</option>
                                    <option value="pdf">📄 PDF Document</option>
                                    <option value="word">📝 Word Document (.docx)</option>
                                    <option value="print">🖨️ Print Preview</option>
                                </select>
                            </div>
                        </div>

                        <!-- Information Alert -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div style="flex: 1;">
                                <strong>📋 Export Information:</strong>
                                <ul class="info-list">
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <strong>Complete Report:</strong> Includes all students, teachers, and HODs data with full details
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <strong>Students Export:</strong> Student information, registration number, sports, activities
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <strong>Teachers Export:</strong> Teacher profiles, assigned departments, qualifications
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <strong>HODs Export:</strong> Head of Department info, managed departments
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Print Preview Option -->
                        <div class="alert alert-info" style="border-left-color: var(--warning);">
                            <i class="fas fa-print"></i>
                            <div style="flex: 1;">
                                <strong>🖨️ Print Preview Options:</strong>
                                <p style="margin-top: 8px; font-size: 13px;">
                                    Choose "Print Preview" to see a formatted version before printing. 
                                    You can also use the quick print buttons below for instant preview.
                                </p>
                                <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                    <button type="button" class="print-preview-btn" onclick="quickPrint('all')">
                                        <i class="fas fa-print"></i> Print All Data
                                    </button>
                                    <button type="button" class="print-preview-btn" onclick="quickPrint('students')">
                                        <i class="fas fa-user-graduate"></i> Print Students
                                    </button>
                                    <button type="button" class="print-preview-btn" onclick="quickPrint('teachers')">
                                        <i class="fas fa-chalkboard-teacher"></i> Print Teachers
                                    </button>
                                    <button type="button" class="print-preview-btn" onclick="quickPrint('hods')">
                                        <i class="fas fa-user-tie"></i> Print HODs
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn btn-success" id="exportBtn">
                                <i class="fas fa-download"></i>
                                Generate & Download Report
                            </button>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print"></i>
                                Print This Page
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Statistics Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Export Preview (Data Statistics)</h3>
                </div>
                <div class="card-body">
                    <div class="export-preview">
                        <div class="preview-card" data-tooltip="Total enrolled students">
                            <h4><i class="fas fa-user-graduate"></i> Students</h4>
                            <div class="stat-number"><?php echo $students_count; ?></div>
                            <div class="stat-label">Total Enrolled</div>
                        </div>

                        <div class="preview-card" data-tooltip="Total class teachers">
                            <h4><i class="fas fa-chalkboard-teacher"></i> Teachers</h4>
                            <div class="stat-number"><?php echo $teachers_count; ?></div>
                            <div class="stat-label">Class Teachers</div>
                        </div>

                        <div class="preview-card" data-tooltip="Department heads">
                            <h4><i class="fas fa-user-tie"></i> HODs</h4>
                            <div class="stat-number"><?php echo $hods_count; ?></div>
                            <div class="stat-label">Department Heads</div>
                        </div>

                        <div class="preview-card" data-tooltip="Academic departments">
                            <h4><i class="fas fa-building"></i> Departments</h4>
                            <div class="stat-number"><?php echo $depts_count; ?></div>
                            <div class="stat-label">Total Departments</div>
                        </div>

                        <div class="preview-card" data-tooltip="Available sports">
                            <h4><i class="fas fa-futbol"></i> Sports</h4>
                            <div class="stat-number"><?php echo $sports_count; ?></div>
                            <div class="stat-label">Available Sports</div>
                        </div>

                        <div class="preview-card" data-tooltip="Clubs and activities">
                            <h4><i class="fas fa-paint-brush"></i> Activities</h4>
                            <div class="stat-number"><?php echo $activities_count; ?></div>
                            <div class="stat-label">Clubs & Activities</div>
                        </div>

                        <div class="preview-card" data-tooltip="Total messages">
                            <h4><i class="fas fa-envelope"></i> Messages</h4>
                            <div class="stat-number"><?php echo $messages_count; ?></div>
                            <div class="stat-label">Total Messages</div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; text-align: center; color: var(--gray-500); font-size: 13px;">
                        <i class="fas fa-clock"></i>
                        Last updated: <?php echo date('F d, Y h:i A'); ?>
                    </div>
                </div>
            </div>

            <!-- Export Format Guide Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-book-open"></i> Export Format Guide</h3>
                </div>
                <div class="card-body">
                    <div class="guide-grid">
                        <div class="guide-card">
                            <h4><i class="fas fa-file-excel" style="color: #10b981;"></i> Excel Format</h4>
                            <ul>
                                <li><i class="fas fa-check"></i> Best for data analysis</li>
                                <li><i class="fas fa-check"></i> Spreadsheet compatible</li>
                                <li><i class="fas fa-check"></i> Can be edited/filtered</li>
                                <li><i class="fas fa-check"></i> Works with Excel, Google Sheets</li>
                                <li><i class="fas fa-check"></i> Formulas and calculations</li>
                            </ul>
                        </div>

                        <div class="guide-card">
                            <h4><i class="fas fa-file-pdf" style="color: #ef4444;"></i> PDF Format</h4>
                            <ul>
                                <li><i class="fas fa-check"></i> Best for printing/sharing</li>
                                <li><i class="fas fa-check"></i> Professional formatting</li>
                                <li><i class="fas fa-check"></i> Read-only document</li>
                                <li><i class="fas fa-check"></i> Consistent appearance</li>
                                <li><i class="fas fa-check"></i> Password protect option</li>
                            </ul>
                        </div>

                        <div class="guide-card">
                            <h4><i class="fas fa-file-word" style="color: #3b82f6;"></i> Word Format</h4>
                            <ul>
                                <li><i class="fas fa-check"></i> Best for document sharing</li>
                                <li><i class="fas fa-check"></i> Fully editable</li>
                                <li><i class="fas fa-check"></i> Add notes/comments</li>
                                <li><i class="fas fa-check"></i> Professional formatting</li>
                                <li><i class="fas fa-check"></i> Customizable layout</li>
                            </ul>
                        </div>

                        <div class="guide-card">
                            <h4><i class="fas fa-print" style="color: #8b5cf6;"></i> Print Preview</h4>
                            <ul>
                                <li><i class="fas fa-check"></i> Best for direct printing</li>
                                <li><i class="fas fa-check"></i> Print-friendly formatting</li>
                                <li><i class="fas fa-check"></i> Save as PDF option</li>
                                <li><i class="fas fa-check"></i> Multiple pages support</li>
                                <li><i class="fas fa-check"></i> Preview before printing</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Progress bar on scroll
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById('progressBar').style.width = scrolled + '%';
        });

        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Quick print function
        function quickPrint(dataType) {
            const formatNames = {
                'all': 'Complete Report',
                'students': 'Students Report',
                'teachers': 'Teachers Report',
                'hods': 'HODs Report'
            };
            
            if (confirm(`🖨️ Prepare ${formatNames[dataType]} for printing?`)) {
                window.location.href = `?print=preview&data=${dataType}&format=print`;
            }
        }

        // Loading overlay on form submit
        document.getElementById('exportForm')?.addEventListener('submit', function(e) {
            const exportType = document.getElementById('export_type').value;
            if (!exportType) {
                e.preventDefault();
                alert('❌ Please select an export format');
                return;
            }
            
            const exportRole = document.getElementById('export_role').value;
            if (!exportRole) {
                e.preventDefault();
                alert('❌ Please select data to export');
                return;
            }
            
            // Show loading for print preview
            if (exportType === 'print') {
                e.preventDefault();
                window.location.href = `?print=preview&data=${exportRole}&format=print`;
            } else {
                document.getElementById('loadingOverlay').classList.add('active');
            }
        });

        // Animate stats cards on hover
        document.querySelectorAll('.preview-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Dynamic form fields based on selection
        document.getElementById('export_role')?.addEventListener('change', function() {
            const role = this.value;
            
            let message = '';
            switch(role) {
                case 'students':
                    message = '📊 Exporting STUDENTS data including personal info, sports, activities, and video status.';
                    break;
                case 'teachers':
                    message = '👨‍🏫 Exporting TEACHERS data including profile, messages, and assigned students.';
                    break;
                case 'hods':
                    message = '🎓 Exporting HODs data including department management and statistics.';
                    break;
                default:
                    message = '📋 Exporting COMPLETE REPORT with all users, departments, and activities.';
            }
            
            // Create temporary info alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info';
            alertDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            alertDiv.style.marginTop = '1rem';
            alertDiv.style.animation = 'slideIn 0.3s ease';
            
            const form = document.getElementById('exportForm');
            const oldAlert = document.querySelector('.alert-info:not(:first-child)');
            if (oldAlert) oldAlert.remove();
            
            form.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transition = 'opacity 0.5s';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        });

        // Animate stats on page load
        window.addEventListener('load', () => {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const value = stat.textContent;
                stat.textContent = '0';
                
                let start = 0;
                const end = parseInt(value);
                const duration = 1000;
                const increment = end / (duration / 10);
                
                const timer = setInterval(() => {
                    start += increment;
                    if (start > end) {
                        stat.textContent = end;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(start);
                    }
                }, 10);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + E for export focus
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                document.getElementById('export_type').focus();
            }
            // Ctrl + P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                quickPrint('all');
            }
            // Escape to close loading overlay
            if (e.key === 'Escape') {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        });

        // Mobile menu creation
        if (window.innerWidth <= 768) {
            const menuBtn = document.createElement('button');
            menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            menuBtn.style.position = 'fixed';
            menuBtn.style.top = '1rem';
            menuBtn.style.left = '1rem';
            menuBtn.style.zIndex = '1001';
            menuBtn.style.background = 'var(--gradient)';
            menuBtn.style.color = 'white';
            menuBtn.style.border = 'none';
            menuBtn.style.borderRadius = '50%';
            menuBtn.style.width = '50px';
            menuBtn.style.height = '50px';
            menuBtn.style.fontSize = '1.5rem';
            menuBtn.style.cursor = 'pointer';
            menuBtn.style.boxShadow = 'var(--shadow-lg)';
            menuBtn.style.display = 'flex';
            menuBtn.style.alignItems = 'center';
            menuBtn.style.justifyContent = 'center';
            
            menuBtn.addEventListener('click', toggleSidebar);
            document.body.appendChild(menuBtn);
        }

        // Check width for responsive menu
        function checkWidth() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);

        // Ripple effect for buttons
        document.querySelectorAll('.btn, .print-preview-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.width = '20px';
                ripple.style.height = '20px';
                ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                ripple.style.borderRadius = '50%';
                ripple.style.pointerEvents = 'none';
                ripple.style.left = e.offsetX + 'px';
                ripple.style.top = e.offsetY + 'px';
                ripple.style.transform = 'translate(-50%, -50%) scale(0)';
                ripple.style.animation = 'ripple 0.6s ease-out';
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: translate(-50%, -50%) scale(20);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>