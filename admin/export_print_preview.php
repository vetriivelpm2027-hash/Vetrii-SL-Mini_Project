<?php
// Print preview template for export data

// Verify required variables exist
if (!isset($print_results)) {
    die('Error: No data available for preview');
}
$print_data = isset($print_data) ? $print_data : 'all';
$print_format = isset($print_format) ? $print_format : 'print';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Preview - RKMS Student Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 20px 0;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .print-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 20mm;
            background: white;
            border-radius: 10px;
            box-shadow: none;
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
            padding: 20px 25px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 10px 30px rgba(255, 140, 66, 0.3);
        }
        .print-header h1 {
            font-size: 26px;
            color: white;
            margin-bottom: 8px;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        .print-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
        }
        .print-date {
            text-align: right;
            margin-bottom: 15px;
            color: #666;
            font-size: 11px;
            background: linear-gradient(90deg, #e0e7ff, #f3e7ff);
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            page-break-inside: avoid;
        }
        table thead {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border: none;
        }
        table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 700;
            color: white;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }
        table tbody tr {
            transition: all 0.3s ease;
        }
        table tbody tr:nth-child(1) {
            background: linear-gradient(90deg, #e0f2ff, #e0e7ff);
        }
        table tbody tr:nth-child(2) {
            background: linear-gradient(90deg, #fff4e0, #ffe0e0);
        }
        table tbody tr:nth-child(3) {
            background: linear-gradient(90deg, #e0ffe0, #e0e7ff);
        }
        table tbody tr:nth-child(4n) {
            background: linear-gradient(90deg, #ffe0f0, #f0e0ff);
        }
        table tbody tr:nth-child(even) {
            background: linear-gradient(90deg, #f0f4ff, #fff8f0) !important;
        }
        table tbody tr:hover {
            background: linear-gradient(90deg, #ffd700, #ffed4e) !important;
            transform: scale(1.01);
            box-shadow: 0 3px 10px rgba(255, 215, 0, 0.3);
        }
        table tbody tr {
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-top: 25px;
            margin-bottom: 15px;
            padding: 12px 15px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            page-break-after: avoid;
        }
        /* Different colors for each section */
        .students-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .teachers-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .hods-section {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .empty-message {
            padding: 25px;
            text-align: center;
            color: #666;
            background: linear-gradient(135deg, #fff5e6, #ffe0e0);
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 5px solid #ff8c42;
            font-size: 15px;
            font-weight: 500;
        }
        .footer {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(90deg, #f0f4ff, #fff8f0);
            border-radius: 10px;
            text-align: center;
            color: #666;
            font-size: 11px;
            border-top: 3px solid #ff8c42;
            page-break-before: avoid;
        }
        .footer p {
            margin: 3px 0;
        }
        .no-data {
            text-align: center;
            padding: 40px 30px;
            color: #999;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
        }

        /* A4 Print styles */
        @page {
            size: A4;
            margin: 20mm;
            orphans: 4;
            widows: 4;
        }
        @media print {
            html {
                margin: 0;
                padding: 0;
            }
            body {
                background: white;
                animation: none;
                padding: 0;
                margin: 0;
            }
            .print-container {
                padding: 15mm;
                max-width: 100%;
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                width: auto;
            }
            .action-buttons {
                display: none !important;
            }
            table {
                page-break-inside: avoid;
                width: 100%;
            }
            table tbody tr {
                page-break-inside: avoid;
            }
            .section-title {
                page-break-after: avoid;
                page-break-before: avoid;
            }
            .print-header, .print-date {
                page-break-after: avoid;
            }
        }

        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            padding: 15px;
            background: linear-gradient(90deg, #f0f4ff, #fff8f0);
            border-radius: 12px;
        }
        .btn {
            padding: 10px 22px;
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 15px rgba(255, 140, 66, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn:hover {
            background: linear-gradient(135deg, #FF6B35, #FF5722);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 140, 66, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #764ba2, #f093fb);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Record count badge */
        .record-count {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #333;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
            box-shadow: 0 2px 8px rgba(255, 165, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="action-buttons">
            <button onclick="window.print()" class="btn">🖨️ Print</button>
            <button onclick="window.history.back()" class="btn btn-secondary">← Back</button>
        </div>

        <div class="print-header">
            <h1>RKMS Student Portal - Data Export</h1>
            <p>Print Preview</p>
        </div>

        <div class="print-date">
            <strong>Generated:</strong> <?php echo date('d/m/Y H:i:s'); ?>
        </div>

        <?php if (empty($print_results)): ?>
            <div class="no-data">
                <p>No data available to display.</p>
            </div>
        <?php else: ?>

            <!-- Students Section -->
            <?php if (isset($print_results['students']) && !empty($print_results['students'])): ?>
                <div class="section-title students-section">👥 Students <span class="record-count"><?php echo count($print_results['students']); ?> records</span></div>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Registration #</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Semester</th>
                            <th>Department</th>
                            <th>Aadhar</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($print_results['students'] as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['username'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['registration_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['mobile'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['semester'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['department'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['aadhar_number'] ?? ''); ?></td>
                                <td><?php echo isset($student['created_at']) ? date('d/m/Y', strtotime($student['created_at'])) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Teachers Section -->
            <?php if (isset($print_results['teachers']) && !empty($print_results['teachers'])): ?>
                <div class="section-title teachers-section">👨‍🏫 Teachers <span class="record-count"><?php echo count($print_results['teachers']); ?> records</span></div>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Qualification</th>
                            <th>Experience</th>
                            <th>Department</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($print_results['teachers'] as $teacher): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($teacher['username'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($teacher['first_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($teacher['last_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($teacher['qualification'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($teacher['experience'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($teacher['department'] ?? ''); ?></td>
                                <td><?php echo isset($teacher['created_at']) ? date('d/m/Y', strtotime($teacher['created_at'])) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- HODs Section -->
            <?php if (isset($print_results['hods']) && !empty($print_results['hods'])): ?>
                <div class="section-title hods-section">👨‍💼 Heads of Department <span class="record-count"><?php echo count($print_results['hods']); ?> records</span></div>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Qualification</th>
                            <th>Department</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($print_results['hods'] as $hod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hod['username'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($hod['first_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($hod['last_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($hod['qualification'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($hod['department'] ?? ''); ?></td>
                                <td><?php echo isset($hod['created_at']) ? date('d/m/Y', strtotime($hod['created_at'])) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

        <div class="footer">
            <p>RKMS Student Portal - Data Export System</p>
            <p>© 2026 All Rights Reserved</p>
        </div>
    </div>
</body>
</html>
