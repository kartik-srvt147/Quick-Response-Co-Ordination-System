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
$notificationCount = getNotificationCount();

// Check if this is an admin user, redirect if not
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

// Set page title
$page_title = "Admin Dashboard - QRCS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="./output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            padding-top: 5rem; /* Add body padding to account for fixed navbar */
        }
        main {
            padding-top: 1rem; /* Additional spacing for content */
        }
    </style>
</head>
<body class="bg-black min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <main>
        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-600 bg-opacity-25 border border-green-500 text-green-100 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">Success!</p>
                    <p><?php echo $_SESSION['success']; ?></p>
                </div>
            </div>
            <?php 
                unset($_SESSION['success']);
            endif; 
            ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-600 bg-opacity-25 border border-red-500 text-red-100 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">Error</p>
                    <p><?php echo $_SESSION['error']; ?></p>
                </div>
            </div>
            <?php 
                unset($_SESSION['error']);
            endif; 
            ?>
            
            <!-- Dashboard Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Admin Control Panel</h1>
                <p class="text-gray-400 mb-6">Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?> (Administrator)</p>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Statistics Cards -->
                    <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-500 bg-opacity-20">
                                <i class="fas fa-exclamation-circle text-red-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-400 text-sm">Active Emergencies</p>
                                <h3 class="text-xl font-bold text-white" id="activeEmergencies">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 bg-opacity-20">
                                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-400 text-sm">Resolved Cases</p>
                                <h3 class="text-xl font-bold text-white" id="resolvedCases">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-500 bg-opacity-20">
                                <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-400 text-sm">Pending</p>
                                <h3 class="text-xl font-bold text-white" id="pendingCases">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 bg-opacity-20">
                                <i class="fas fa-ambulance text-blue-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-400 text-sm">Available Units</p>
                                <h3 class="text-xl font-bold text-white" id="availableUnits">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Emergency List -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-white">All Emergency Reports</h2>
                            <div class="flex space-x-2">
                                <select id="statusFilter" class="bg-gray-800 text-gray-300 rounded-md border border-gray-700 px-3 py-1">
                                    <option value="all">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="responding">Responding</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <select id="typeFilter" class="bg-gray-800 text-gray-300 rounded-md border border-gray-700 px-3 py-1">
                                    <option value="all">All Severity</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="text-gray-400 text-left">
                                        <th class="py-3 px-4">Type</th>
                                        <th class="py-3 px-4">Reporter</th>
                                        <th class="py-3 px-4">Location</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Time</th>
                                        <th class="py-3 px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="emergencyList" class="text-gray-300">
                                    <?php
                                    // Get all incidents from database
                                    $sql = "SELECT i.id, i.title, i.description, i.location, i.latitude, i.longitude, 
                                            i.severity, i.status, i.reported_at, 
                                            CONCAT(u.first_name, ' ', u.last_name) as reporter_name, u.id as reporter_id
                                            FROM incidents i 
                                            JOIN users u ON i.reported_by = u.id
                                            ORDER BY 
                                                CASE 
                                                    WHEN i.status = 'pending' THEN 1
                                                    WHEN i.status = 'active' THEN 2
                                                    WHEN i.status = 'responding' THEN 3
                                                    WHEN i.status = 'resolved' THEN 4
                                                    ELSE 5
                                                END,
                                                i.reported_at DESC";
                                    
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($incident = $result->fetch_assoc()) {
                                            $status_class = '';
                                            $display_status = '';
                                            switch ($incident['status']) {
                                                case 'pending':
                                                case 'reported':
                                                    $status_class = 'bg-yellow-500 bg-opacity-50 text-white font-medium';
                                                    $display_status = 'Pending';
                                                    break;
                                                case 'active':
                                                    $status_class = 'bg-blue-500 bg-opacity-50 text-white font-medium';
                                                    $display_status = 'Active';
                                                    break;
                                                case 'responding':
                                                    $status_class = 'bg-purple-500 bg-opacity-50 text-white font-medium';
                                                    $display_status = 'Responding';
                                                    break;
                                                case 'resolved':
                                                case 'closed':
                                                    $status_class = 'bg-green-500 bg-opacity-50 text-white font-medium';
                                                    $display_status = 'Resolved';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'bg-red-500 bg-opacity-50 text-white font-medium';
                                                    $display_status = 'Rejected';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-500 bg-opacity-50 text-white font-medium';
                                                    $display_status = $incident['status'];
                                            }

                                            // Map DB status to display status for data attribute
                                            $data_status = strtolower($display_status);
                                            
                                            // Determine icon based on severity
                                            $icon = 'exclamation-circle';
                                            switch ($incident['severity']) {
                                                case 'low':
                                                    $icon = 'info-circle';
                                                    break;
                                                case 'medium':
                                                    $icon = 'exclamation-triangle';
                                                    break;
                                                case 'high':
                                                    $icon = 'fire';
                                                    break;
                                                case 'critical':
                                                    $icon = 'ambulance';
                                                    break;
                                            }
                                            
                                            echo "<tr class='border-t border-gray-800 incident-row' data-status='" . $data_status . "' data-severity='" . $incident['severity'] . "'>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<div class='flex items-center'>";
                                            echo "<i class='fas fa-{$icon} text-red-500 mr-2'></i>";
                                            echo htmlspecialchars($incident['severity']);
                                            echo "</div>";
                                            echo "</td>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<a href='user_profile.php?id=" . $incident['reporter_id'] . "' class='hover:text-blue-400'>";
                                            echo htmlspecialchars($incident['reporter_name']);
                                            echo "</a>";
                                            echo "</td>";
                                            echo "<td class='py-3 px-4'>" . htmlspecialchars($incident['location']) . "</td>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<span class='px-2 py-1 rounded-full text-xs {$status_class}'>";
                                            echo htmlspecialchars($display_status);
                                            if (strtolower($display_status) !== strtolower($incident['status'])) {
                                                echo "<span class='text-xs opacity-50 ml-1'>(" . htmlspecialchars($incident['status']) . ")</span>";
                                            }
                                            echo "</span>";
                                            echo "</td>";
                                            echo "<td class='py-3 px-4'>" . date('M d, Y H:i', strtotime($incident['reported_at'])) . "</td>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<div class='flex space-x-4'>";
                                            
                                            // Show different action buttons based on status
                                            if ($incident['status'] == 'pending' || $incident['status'] == 'reported') {
                                                // Pending reports - approve or reject
                                                echo "<a href='manage_incident.php?id=" . $incident['id'] . "&action=approve' class='text-green-400 hover:text-green-300' title='Approve Report'>";
                                                echo "<i class='fas fa-check'></i>";
                                                echo "</a>";
                                                
                                                echo "<a href='manage_incident.php?id=" . $incident['id'] . "&action=reject' class='text-red-400 hover:text-red-300' title='Reject Report'>";
                                                echo "<i class='fas fa-ban'></i>";
                                                echo "</a>";
                                            } else if ($incident['status'] == 'active') {
                                                // Active reports - dispatch unit
                                                echo "<a href='manage_incident.php?id=" . $incident['id'] . "&action=dispatch' class='text-blue-400 hover:text-blue-300' title='Dispatch Response Unit'>";
                                                echo "<i class='fas fa-truck'></i>";
                                                echo "</a>";
                                            } else if ($incident['status'] == 'responding') {
                                                // Responding reports - mark as resolved
                                                echo "<a href='manage_incident.php?id=" . $incident['id'] . "&action=resolve' class='text-green-400 hover:text-green-300' title='Mark as Resolved'>";
                                                echo "<i class='fas fa-check-circle'></i>";
                                                echo "</a>";
                                            }
                                            
                                            // View details always available
                                            echo "<a href='view_incident.php?id=" . $incident['id'] . "' class='text-blue-400 hover:text-blue-300' title='View Details'>";
                                            echo "<i class='fas fa-eye'></i>";
                                            echo "</a>";
                                            
                                            // Delete option always available for admins
                                            echo "<a href='manage_incident.php?id=" . $incident['id'] . "&action=delete' class='text-red-400 hover:text-red-300' title='Delete Record'>";
                                            echo "<i class='fas fa-trash'></i>";
                                            echo "</a>";
                                            
                                            echo "</div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='py-3 px-4 text-center'>No emergency reports found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Map and Quick Actions -->
                <div class="space-y-8">
                    <!-- Live Map -->
                    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                        <h2 class="text-xl font-bold text-white mb-4">Emergency Map</h2>
                        <div id="dashboardMap" class="h-64 rounded-lg"></div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                        <h2 class="text-xl font-bold text-white mb-4">Admin Actions</h2>
                        <div class="space-y-2">
                            <button onclick="window.location.href='manage_resources.php'" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-ambulance mr-2"></i>Manage Resources
                            </button>
                            <button onclick="window.location.href='send_notifications.php'" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                <i class="fas fa-bell mr-2"></i>Send Notifications
                            </button>
                            <button onclick="window.location.href='generate_reports.php'" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-file-alt mr-2"></i>Generate Reports
                            </button>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                        <h2 class="text-xl font-bold text-white mb-4">System Status</h2>
                        
                        <?php
                        // Get system stats
                        $user_count = 0;
                        $userCountQuery = "SELECT COUNT(*) as count FROM users";
                        $userResult = $conn->query($userCountQuery);
                        if ($userResult && $userResult->num_rows > 0) {
                            $row = $userResult->fetch_assoc();
                            $user_count = $row['count'];
                        }
                        
                        // Get resource stats
                        $resource_count = 0;
                        $available_resources = 0;
                        $resourceCountQuery = "SELECT COUNT(*) as count, SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available FROM resources";
                        $resourceResult = $conn->query($resourceCountQuery);
                        if ($resourceResult && $resourceResult->num_rows > 0) {
                            $row = $resourceResult->fetch_assoc();
                            $resource_count = $row['count'];
                            $available_resources = $row['available'];
                        }
                        ?>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-300">
                                <span>Registered Users:</span>
                                <span><?php echo $user_count; ?></span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>Total Resources:</span>
                                <span><?php echo $resource_count; ?></span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>Available Resources:</span>
                                <span><?php echo $available_resources; ?></span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>System Status:</span>
                                <span class="text-green-500">Online</span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>Database Status:</span>
                                <span class="text-green-500">Connected</span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>Last Backup:</span>
                                <span><?php echo date('Y-m-d H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            const map = L.map('dashboardMap').setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add markers for incidents
            <?php
            // Reset result pointer
            if ($result) {
                $result->data_seek(0);
                while ($incident = $result->fetch_assoc()) {
                    if (!empty($incident['latitude']) && !empty($incident['longitude'])) {
                        // Different colors based on status
                        $iconColor = 'blue';
                        if ($incident['status'] == 'pending') $iconColor = 'orange';
                        if ($incident['status'] == 'active') $iconColor = 'red';
                        if ($incident['status'] == 'resolved') $iconColor = 'green';
                        
                        echo "const marker" . $incident['id'] . " = L.marker([" . $incident['latitude'] . ", " . $incident['longitude'] . "]).addTo(map)\n";
                        echo ".bindPopup('<strong>" . addslashes($incident['title']) . "</strong><br>" . 
                             "Type: " . addslashes($incident['severity']) . "<br>" .
                             "Status: " . addslashes($incident['status']) . "<br>" .
                             "Reporter: " . addslashes($incident['reporter_name']) . "<br>" .
                             "<a href=\"view_incident.php?id=" . $incident['id'] . "\" class=\"text-blue-500\">View Details</a>');\n";
                    }
                }
                
                // If we have at least one incident with coordinates, center the map on the first one
                $result->data_seek(0);
                $has_centered = false;
                while ($incident = $result->fetch_assoc()) {
                    if (!empty($incident['latitude']) && !empty($incident['longitude']) && !$has_centered) {
                        echo "map.setView([" . $incident['latitude'] . ", " . $incident['longitude'] . "], 10);\n";
                        $has_centered = true;
                        break;
                    }
                }
            }
            ?>

            // If no incidents or no coordinates, try to get user's location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        <?php if (!isset($has_centered) || !$has_centered): ?>
                        map.setView([latitude, longitude], 13);
                        <?php endif; ?>
                    },
                    (error) => {
                        console.error('Error getting location:', error);
                    }
                );
            }

            // Update statistics
            <?php
            // Count incidents by status
            $active = 0;
            $resolved = 0;
            $pending = 0;
            
            if ($result) {
                $result->data_seek(0);
                while ($incident = $result->fetch_assoc()) {
                    if ($incident['status'] == 'active' || $incident['status'] == 'responding') {
                        $active++;
                    } else if ($incident['status'] == 'resolved' || $incident['status'] == 'closed') {
                        $resolved++;
                    } else if ($incident['status'] == 'pending' || $incident['status'] == 'reported') {
                        $pending++;
                    }
                }
            }
            ?>
            
            document.getElementById('activeEmergencies').textContent = '<?php echo $active; ?>';
            document.getElementById('resolvedCases').textContent = '<?php echo $resolved; ?>';
            document.getElementById('pendingCases').textContent = '<?php echo $pending; ?>';
            document.getElementById('availableUnits').textContent = '<?php echo $available_resources; ?>';
            
            // Filter incidents
            function applyFilters() {
                const statusValue = $('#statusFilter').val().toLowerCase();
                const severityValue = $('#typeFilter').val().toLowerCase();

                $('.incident-row').each(function() {
                    const rowStatus = $(this).data('status').toLowerCase();
                    const rowSeverity = $(this).data('severity').toLowerCase();
                    
                    const statusMatch = statusValue === 'all' || rowStatus === statusValue;
                    const severityMatch = severityValue === 'all' || rowSeverity === severityValue;
                    
                    if (statusMatch && severityMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            // Apply filters when dropdown changes
            $('#statusFilter, #typeFilter').on('change', applyFilters);
        });
    </script>
</body>
</html> 