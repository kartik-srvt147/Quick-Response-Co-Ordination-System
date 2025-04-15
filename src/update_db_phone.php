<?php
// Include config
require_once 'includes/config.php';

echo "<h1>Database Update Script - Add Phone Column</h1>";

// Check if phone column exists in users table
$check_query = "SHOW COLUMNS FROM users LIKE 'phone'";
$result = $conn->query($check_query);

if ($result->num_rows == 0) {
    // Column doesn't exist, so add it
    $alter_query = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email";
    
    if ($conn->query($alter_query) === TRUE) {
        echo "<p style='color:green'>Successfully added 'phone' column to users table.</p>";
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:blue'>The 'phone' column already exists in the users table.</p>";
}

echo "<p>Database update completed. <a href='admin_dashboard.php'>Return to Admin Dashboard</a></p>";

$conn->close();
?> 