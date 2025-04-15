<?php
// Include config
require_once 'includes/config.php';

echo "<h1>Database Update Script - Status Options</h1>";

// Try to update the incidents table status ENUM
$alter_query = "ALTER TABLE incidents MODIFY COLUMN status ENUM('reported', 'pending', 'active', 'responding', 'resolved', 'closed', 'rejected') DEFAULT 'reported'";

if ($conn->query($alter_query) === TRUE) {
    echo "<p style='color:green'>Successfully updated status options in incidents table.</p>";
} else {
    echo "<p style='color:red'>Error updating status options: " . $conn->error . "</p>";
}

// Display current status values in the database
$status_query = "SELECT DISTINCT status, COUNT(*) as count FROM incidents GROUP BY status";
$result = $conn->query($status_query);

if ($result && $result->num_rows > 0) {
    echo "<h2>Current Status Distribution:</h2>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['status']) . ": " . $row['count'] . " incidents</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No incidents found in the database.</p>";
}

echo "<p>Database update completed. <a href='admin_dashboard.php'>Return to Admin Dashboard</a></p>";

$conn->close();
?> 