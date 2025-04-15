<?php
// Database Configuration
require_once 'includes/config.php';

echo "<h1>Database Update Script</h1>";

// Check if additional_data column exists in incidents table
$check_query = "SHOW COLUMNS FROM incidents LIKE 'additional_data'";
$result = $conn->query($check_query);

if ($result->num_rows == 0) {
    // Column doesn't exist, so add it
    $alter_query = "ALTER TABLE incidents ADD COLUMN additional_data TEXT NULL AFTER resolved_at";
    
    if ($conn->query($alter_query) === TRUE) {
        echo "<p style='color:green'>Successfully added 'additional_data' column to incidents table.</p>";
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:blue'>The 'additional_data' column already exists in the incidents table.</p>";
}

echo "<p>Database update completed. <a href='dashboard.php'>Return to Dashboard</a></p>";

$conn->close();
?> 