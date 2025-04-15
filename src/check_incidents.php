<?php
// Include auth and config files
require_once 'includes/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Status Checker</title>
    <link href="./output.css" rel="stylesheet">
    <style>
        body { 
            background-color: black; 
            color: white; 
            font-family: system-ui, sans-serif;
            padding: 2rem;
        }
        h1 { margin-bottom: 1rem; }
        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .status-table th, .status-table td {
            border: 1px solid #444;
            padding: 0.5rem;
            text-align: left;
        }
        .status-table th {
            background-color: #333;
        }
        .status-section {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #222;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <h1>Incident Status Checker</h1>
    
    <div class="status-section">
        <h2>Recent Incidents</h2>
        <table class="status-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Status (raw)</th>
                    <th>Created</th>
                    <th>Reporter</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT i.id, i.title, i.status, i.reported_at, 
                        CONCAT(u.first_name, ' ', u.last_name) as reporter_name
                        FROM incidents i
                        JOIN users u ON i.reported_by = u.id
                        ORDER BY i.reported_at DESC
                        LIMIT 10";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td><code>" . htmlspecialchars($row['status']) . "</code></td>";
                        echo "<td>" . $row['reported_at'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['reporter_name']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No incidents found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="status-section">
        <h2>Status Distribution</h2>
        <table class="status-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT status, COUNT(*) as count FROM incidents GROUP BY status";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><code>" . htmlspecialchars($row['status']) . "</code></td>";
                        echo "<td>" . $row['count'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>No status data found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div>
        <a href="admin_dashboard.php" style="color: #3b82f6; text-decoration: underline;">Back to Dashboard</a>
    </div>
</body>
</html> 