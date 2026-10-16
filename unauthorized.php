<?php
// Generic unauthorized access page used by many scripts

// Check for optional context
$profileEdit = isset($_GET['profile_edit']);
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; }
        .container { max-width: 600px; margin: 80px auto; background: white; padding: 30px; border:1px solid #ccc; border-radius:8px; }
        .warning { background: #ffebee; border: 2px solid #d32f2f; padding: 20px; border-radius:8px; }
        .info { margin-top: 20px; }
        a.button { display:inline-block; margin-top:10px; padding:10px 15px; background:#1976d2; color:white; text-decoration:none; border-radius:4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Access Denied</h1>
        <div class="warning">
            You do not have permission to view this page.
        </div>

        <?php if ($profileEdit): ?>
            <div class="info">
                <p>It looks like you tried to edit your profile.</p>
                <p>If you'd like permission to edit your details, <a href="student/request_edit_permission.php" class="button">request permission</a>.</p>
            </div>
        <?php endif; ?>

        <div class="info">
            <a href="index.php" class="button">Return to Home</a>
        </div>

        <?php if ($debug): ?>
            <div class="info" style="margin-top:30px;">
                <h3>Debug Info</h3>
                <pre><?php echo htmlspecialchars(var_export($_SERVER, true)); ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>