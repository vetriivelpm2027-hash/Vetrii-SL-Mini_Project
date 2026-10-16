<?php
/**
 * Password Migration Script
 * Converts MD5 passwords to bcrypt hashes
 * Run this once after updating the authentication system
 */

require_once 'config/database.php';

// Set timeout for large migrations
set_time_limit(300);

// Check if already migrated
$migrated = $conn->query("SELECT COUNT(*) as count FROM users WHERE password LIKE '\$2y\$%'");
$migrated_result = $migrated ? $migrated->fetch_assoc() : null;

if ($migrated_result && $migrated_result['count'] > 0) {
    $total_result = $conn->query("SELECT COUNT(*) as count FROM users");
    $total = $total_result ? $total_result->fetch_assoc()['count'] : 0;
    
    if ($migrated_result['count'] == $total) {
        die("✓ All passwords have already been migrated to bcrypt!");
    }
}

echo "Starting password migration from MD5 to bcrypt...\n\n";

// Get all users with MD5 passwords (MD5 hashes are always 32 hex characters)
$query = "SELECT id, username, password FROM users WHERE CHAR_LENGTH(password) = 32 OR password = MD5('admin123')";
$result = $conn->query($query);

if (!$result) {
    die("Error querying users: " . $conn->error);
}

$count = 0;
$failed = 0;

while ($user = $result->fetch_assoc()) {
    // Check if this looks like an MD5 hash (32 hex digits)
    if (preg_match('/^[a-f0-9]{32}$/i', $user['password'])) {
        // This is likely an MD5 hash, but we can't convert it back to plaintext
        // Instead, we'll set a temporary password for the user
        
        // Special case for admin user
        if ($user['username'] === 'admin' && $user['password'] === md5('admin123')) {
            $new_hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
            
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $new_hash, $user['id']);
                if ($update_stmt->execute()) {
                    echo "✓ Migrated admin password to bcrypt\n";
                    $count++;
                } else {
                    echo "✗ Failed to migrate {$user['username']}: " . $update_stmt->error . "\n";
                    $failed++;
                }
                $update_stmt->close();
            } else {
                echo "✗ Prepare failed for {$user['username']}\n";
                $failed++;
            }
        } else {
            // For other users, generate a temporary password
            $temp_password = substr(bin2hex(random_bytes(6)), 0, 12);
            $new_hash = password_hash($temp_password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $new_hash, $user['id']);
                if ($update_stmt->execute()) {
                    echo "✓ Migrated {$user['username']} - Temporary password: {$temp_password}\n";
                    echo "  (User should reset password on next login)\n";
                    $count++;
                } else {
                    echo "✗ Failed to migrate {$user['username']}: " . $update_stmt->error . "\n";
                    $failed++;
                }
                $update_stmt->close();
            } else {
                echo "✗ Prepare failed for {$user['username']}\n";
                $failed++;
            }
        }
    }
}

echo "\n========================================\n";
echo "Migration Complete!\n";
echo "Successful: {$count}\n";
echo "Failed: {$failed}\n";
echo "========================================\n\n";

if ($count > 0) {
    echo "⚠️  NOTE: Users with temporary passwords should reset them on next login!\n\n";
    echo "Migration from MD5 to bcrypt is complete. Your passwords are now more secure!\n";
} else {
    echo "No passwords needed migration (might already be done).\n";
}

// Verify the migration
$verify = $conn->query("SELECT COUNT(*) as bcrypt_count FROM users WHERE password LIKE '\$2y\$%' OR password LIKE '\$2b\$%'");
$verify_result = $verify ? $verify->fetch_assoc() : null;

if ($verify_result) {
    echo "\nVerification: {$verify_result['bcrypt_count']} users now have bcrypt passwords.\n";
}
?>
