<?php
// Include auth and config files
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();

// Redirect based on user role
if ($currentUser && $currentUser['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
} else {
    header("Location: user_dashboard.php");
    exit;
}
?> 