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

// Check if this is a regular user, redirect if admin
if ($currentUser && $currentUser['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

// Set page title
$page_title = "User Dashboard - QRCS";
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
                <h1 class="text-3xl font-bold text-white mb-2">Emergency Response Dashboard</h1>
                <p class="text-gray-400 mb-6">Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Statistics Cards -->
                    <div class="bg-gray-900 p-4 rounded-lg border border-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-500 bg-opacity-20">
                                <i class="fas fa-exclamation-circle text-red-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-400 text-sm">My Active Reports</p>
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
                                <p class="text-gray-400 text-sm">Resolved Reports</p>
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
                                <p class="text-gray-400 text-sm">Pending Reports</p>
                                <h3 class="text-xl font-bold text-white" id="pendingCases">0</h3>
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
                            <h2 class="text-xl font-bold text-white">My Emergency Reports</h2>
                            <div class="flex space-x-2">
                                <select id="statusFilter" class="bg-gray-800 text-gray-300 rounded-md border border-gray-700 px-3 py-1">
                                    <option value="all">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="resolved">Resolved</option>
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
                                        <th class="py-3 px-4">Location</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Time</th>
                                        <th class="py-3 px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="emergencyList" class="text-gray-300">
                                    <?php
                                    // Get user's incidents from database
                                    $sql = "SELECT i.id, i.title, i.description, i.location, i.latitude, i.longitude, 
                                            i.severity, i.status, i.reported_at
                                            FROM incidents i 
                                            WHERE i.reported_by = ? 
                                            ORDER BY i.reported_at DESC";
                                    
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $currentUser['id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($incident = $result->fetch_assoc()) {
                                            $status_class = '';
                                            switch ($incident['status']) {
                                                case 'pending':
                                                case 'reported':
                                                    $status_class = 'bg-yellow-500 bg-opacity-50 text-white font-medium';
                                                    break;
                                                case 'active':
                                                case 'responding':
                                                    $status_class = 'bg-blue-500 bg-opacity-50 text-white font-medium';
                                                    break;
                                                case 'resolved':
                                                case 'closed':
                                                    $status_class = 'bg-green-500 bg-opacity-50 text-white font-medium';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'bg-red-500 bg-opacity-50 text-white font-medium';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-500 bg-opacity-50 text-white font-medium';
                                            }
                                            
                                            // Map DB status to display status
                                            $display_status = $incident['status'];
                                            if ($incident['status'] == 'reported') {
                                                $display_status = 'pending';
                                            } else if ($incident['status'] == 'closed') {
                                                $display_status = 'resolved';
                                            }
                                            
                                            // Capitalize first letter of display status
                                            $display_status = ucfirst($display_status);
                                            
                                            // Determine icon based on severity instead of emergency_type
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
                                            
                                            echo "<tr class='border-t border-gray-800 incident-row' data-status='" . $display_status . "' data-severity='" . $incident['severity'] . "'>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<div class='flex items-center'>";
                                            echo "<i class='fas fa-{$icon} text-red-500 mr-2'></i>";
                                            echo htmlspecialchars($incident['severity']);
                                            echo "</div>";
                                            echo "</td>";
                                            echo "<td class='py-3 px-4'>" . htmlspecialchars($incident['location']) . "</td>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<span class='px-2 py-1 rounded-full text-xs {$status_class}'>";
                                            echo htmlspecialchars($display_status);
                                            echo "</span>";
                                            echo "</td>";
                                            echo "<td class='py-3 px-4'>" . date('M d, Y H:i', strtotime($incident['reported_at'])) . "</td>";
                                            echo "<td class='py-3 px-4'>";
                                            echo "<div class='flex space-x-4'>";
                                            
                                            // Only show cancel button if not resolved
                                            if ($incident['status'] != 'resolved') {
                                                echo "<a href='cancel_report.php?id=" . $incident['id'] . "' class='text-red-400 hover:text-red-300' title='Cancel Report'>";
                                                echo "<i class='fas fa-times-circle'></i>";
                                                echo "</a>";
                                            }
                                            
                                            echo "<a href='view_report.php?id=" . $incident['id'] . "' class='text-blue-400 hover:text-blue-300' title='View Details'>";
                                            echo "<i class='fas fa-eye'></i>";
                                            echo "</a>";
                                            
                                            echo "</div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='py-3 px-4 text-center'>No emergency reports found</td></tr>";
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
                        <h2 class="text-xl font-bold text-white mb-4">My Reports Map</h2>
                        <div id="dashboardMap" class="h-64 rounded-lg"></div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                        <h2 class="text-xl font-bold text-white mb-4">Quick Actions</h2>
                        <div class="space-y-2">
                            <button onclick="window.location.href='report-emergency.php'" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Report Emergency
                            </button>
                            <button onclick="window.location.href='notifications.php'" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-bell mr-2"></i>View Notifications
                            </button>
                            <button onclick="window.location.href='profile.php'" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-user-edit mr-2"></i>Edit Profile
                            </button>
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
                        echo "L.marker([" . $incident['latitude'] . ", " . $incident['longitude'] . "]).addTo(map)";
                        echo ".bindPopup('" . addslashes($incident['title']) . "<br>Status: " . addslashes($incident['status']) . "');\n";
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

            // Filter incidents
            function applyFilters() {
                const statusValue = $('#statusFilter').val();
                const typeValue = $('#typeFilter').val();
                
                $('.incident-row').each(function() {
                    const cardStatus = $(this).data('status');
                    const cardType = $(this).data('severity');
                    
                    const statusMatch = statusValue === 'all' || cardStatus === statusValue;
                    const typeMatch = typeValue === 'all' || cardType === typeValue;
                    
                    if (statusMatch && typeMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Update map markers
                updateMarkersVisibility();
            }
            
            // Event listeners for filters
            $('#statusFilter, #typeFilter').on('change', applyFilters);
        });
    </script>
</body>
</html> 