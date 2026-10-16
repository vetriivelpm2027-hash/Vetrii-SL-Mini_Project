<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

// Basic placeholder for permissions management - expand as needed
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Changes saved (placeholder).';
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Permissions - Admin</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#fff8e7;padding:2rem;color:#2b2b2b}
    .card{max-width:900px;margin:0 auto;background:#fff;padding:1.25rem;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    .btn{display:inline-block;padding:0.5rem 0.75rem;background:#3b82f6;color:#fff;border-radius:6px;text-decoration:none}
    .muted{color:#666}
    label{font-weight:600;color:#6b4a2b}
    .row{display:flex;gap:1rem;align-items:center;margin:0.5rem 0}
  </style>
</head>
<body>
  <div class="card">
    <h1>Permissions</h1>
    <?php if ($message): ?>
      <div style="padding:0.5rem;background:#e6ffed;border:1px solid #b7f3c7;border-radius:6px;margin-bottom:1rem;">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <p class="muted">This is a safe placeholder for the permissions management page. Implement role-based permissions here.</p>

    <form method="post">
      <div class="row">
        <label for="role">Role</label>
        <select id="role" name="role">
          <option value="admin">Admin</option>
          <option value="secretary">Secretary</option>
          <option value="principal">Principal</option>
          <option value="hod">HOD</option>
          <option value="class_teacher">Class Teacher</option>
        </select>
      </div>

      <div class="row">
        <label for="perm">Permission</label>
        <input id="perm" name="perm" placeholder="e.g., manage_students" />
      </div>

      <button class="btn" type="submit">Save (placeholder)</button>
      <a class="btn" href="users.php" style="background:#6b4a2b;margin-left:0.5rem">Back to Users</a>
    </form>
  </div>
</body>
</html>
