<?php
// Session configuration
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['role'] == $role || ($_SESSION['role'] == 'admin');
}

// Check multiple roles
function hasAnyRole($roles = []) {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'], $roles) || $_SESSION['role'] == 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . APP_URL . "/index.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!hasRole('admin')) {
        header("Location: " . APP_URL . "/unauthorized.php");
        exit();
    }
}

// Get user info from session
function getUserInfo() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'department' => $_SESSION['department'] ?? null
        ];
    }
    return null;
}

// Logout function
function logout() {
    session_destroy();
    header("Location: " . APP_URL . "/index.php");
    exit();
}
?>
