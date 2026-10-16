<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// only admins allowed
requireLogin();
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // basic CSV import stub
    $file = $_FILES['csv_file']['tmp_name'];
    if (is_uploaded_file($file)) {
        // parse first few rows as sample
        $handle = fopen($file, 'r');
        if ($handle) {
            $row = fgetcsv($handle);
            fclose($handle);
            $success = 'File received, first row: ' . implode(', ', $row);
        } else {
            $error = 'Could not open uploaded file.';
        }
    } else {
        $error = 'File upload error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Import Users</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#fff8e7;padding:2rem;color:#2b2b2b}
.container{max-width:600px;margin:0 auto;background:white;padding:1.5rem;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.08)}
label{display:block;margin:.5rem 0;font-weight:600}
input{width:100%;padding:.5rem;margin:.25rem 0;border:1px solid #ccc;border-radius:4px}
.btn{display:inline-block;padding:.5rem .75rem;background:#3b82f6;color:#fff;border-radius:6px;text-decoration:none;cursor:pointer;border:none}
.alert{padding:.75rem 1rem;margin-bottom:1rem;border-radius:6px}
.alert-success{background:#e6ffed;color:#064e3b;border:1px solid #a7f3d0}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
</style>
</head>
<body>
<div class="container">
    <h2>Import Users via CSV</h2>
    <?php if ($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label for="csv_file">Select CSV file</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
        <br>
        <button class="btn" type="submit">Upload</button>
        <a href="users.php" class="btn" style="background:#6b4a2b;margin-left:.5rem;">Back to Users</a>
    </form>
</div>
</body>
</html>