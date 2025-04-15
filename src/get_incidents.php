<?php
// Include database configuration
require_once 'includes/config.php';

// Get all active incidents
$sql = "SELECT id, title, description, location, latitude, longitude, severity, status
        FROM incidents 
        WHERE status != 'closed'
        ORDER BY reported_at DESC";

$result = $conn->query($sql);
$incidents = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Sanitize data for JSON output
        $incidents[] = [
            'id' => (int)$row['id'],
            'title' => htmlspecialchars($row['title']),
            'description' => htmlspecialchars($row['description']),
            'location' => htmlspecialchars($row['location']),
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'severity' => htmlspecialchars($row['severity']),
            'status' => htmlspecialchars($row['status'])
        ];
    }
}

// Set content type to JSON
header('Content-Type: application/json');

// Output JSON data
echo json_encode($incidents);
?> 