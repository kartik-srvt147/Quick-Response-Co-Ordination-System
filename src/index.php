<?php
// Include configuration and authentication files
require_once 'includes/config.php';

// Get current user if logged in
$currentUser = getCurrentUser();
$notificationCount = 0;
if (isLoggedIn()) {
    $notificationCount = getNotificationCount();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Response Coordination System (QRCS)</title>
    <link href="./output.css" rel="stylesheet">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Add Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="./js/notifications.js" defer></script>
    <style>
        body {
            padding-top: 5rem; /* Add body padding for fixed navbar */
        }
        main {
            padding-top: 1rem; /* Additional spacing for content */
        }
    </style>
</head>
<body class="bg-black min-h-screen">
    <!-- Include the shared navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content Area -->
    <main>
        <!-- Home Page Content -->
        <div id="homePage" class="page-content">
            <!-- Hero Section -->
            <div class="bg-gray-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold text-white mb-4">
                            Emergency Response Made Simple
                        </h1>
                        <p class="text-xl text-gray-300 mb-8">
                            Coordinate emergency responses quickly and efficiently
                        </p>
                        <button id="reportIncidentBtn" onclick="window.location.href='report-emergency.php'" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Report Emergency
                        </button>
                    </div>
                </div>
            </div>

            <!-- Incident Map -->
            <div class="bg-gray-900 py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-white mb-4">Live Incident Map</h2>
                    <div id="incidentMap" class="h-96 rounded-lg shadow-lg"></div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="bg-black py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="bg-gray-900 p-6 rounded-lg shadow-lg border border-gray-800">
                            <i class="fas fa-clock text-red-500 text-3xl mb-4"></i>
                            <h3 class="text-xl font-semibold mb-2 text-white">Quick Response</h3>
                            <p class="text-gray-300">Immediate coordination with emergency services</p>
                        </div>
                        <div class="bg-gray-900 p-6 rounded-lg shadow-lg border border-gray-800">
                            <i class="fas fa-map-marker-alt text-red-500 text-3xl mb-4"></i>
                            <h3 class="text-xl font-semibold mb-2 text-white">Location Tracking</h3>
                            <p class="text-gray-300">Real-time location monitoring and updates</p>
                        </div>
                        <div class="bg-gray-900 p-6 rounded-lg shadow-lg border border-gray-800">
                            <i class="fas fa-users text-red-500 text-3xl mb-4"></i>
                            <h3 class="text-xl font-semibold mb-2 text-white">Team Coordination</h3>
                            <p class="text-gray-300">Efficient team management and communication</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Incidents Section -->
            <div class="bg-gray-900 py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-white mb-4">Recent Incidents</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Title</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Location</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Severity</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Reported</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php
                                // Get recent incidents from database
                                $sql = "SELECT i.id, i.title, i.location, i.severity, i.status, i.reported_at, 
                                               CONCAT(u.first_name, ' ', u.last_name) as reporter
                                        FROM incidents i 
                                        JOIN users u ON i.reported_by = u.id
                                        ORDER BY i.reported_at DESC 
                                        LIMIT 5";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0) {
                                    while ($incident = $result->fetch_assoc()) {
                                        // Determine severity class
                                        $severityClass = '';
                                        switch ($incident['severity']) {
                                            case 'critical':
                                                $severityClass = 'text-red-500';
                                                break;
                                            case 'high':
                                                $severityClass = 'text-orange-500';
                                                break;
                                            case 'medium':
                                                $severityClass = 'text-yellow-500';
                                                break;
                                            case 'low':
                                                $severityClass = 'text-green-500';
                                                break;
                                        }
                                        
                                        // Format date
                                        $reportedDate = date('M d, Y - H:i', strtotime($incident['reported_at']));
                                        
                                        echo "<tr class='hover:bg-gray-700'>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-300'>" . htmlspecialchars($incident['title']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-300'>" . htmlspecialchars($incident['location']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm {$severityClass} font-medium'>" . ucfirst(htmlspecialchars($incident['severity'])) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-300'>" . ucfirst(htmlspecialchars($incident['status'])) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-300'>" . $reportedDate . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-4 py-3 text-sm text-gray-300 text-center'>No incidents reported yet</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-400">
                <p>&copy; 2024 Quick Response Coordination System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Add Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // Initialize map
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('incidentMap').setView([51.505, -0.09], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Fetch incidents from the database via AJAX
            fetch('get_incidents.php')
                .then(response => response.json())
                .then(incidents => {
                    // Add markers for each incident
                    incidents.forEach(incident => {
                        var marker = L.marker([incident.latitude, incident.longitude]).addTo(map);
                        marker.bindPopup(`
                            <strong>${incident.title}</strong><br>
                            ${incident.description}<br>
                            <span class="text-sm">Status: ${incident.status}</span>
                        `);
                    });
                    
                    // If incidents exist, center map on the first one
                    if (incidents.length > 0) {
                        map.setView([incidents[0].latitude, incidents[0].longitude], 10);
                    }
                })
                .catch(error => {
                    console.error('Error fetching incidents:', error);
                });
        });
    </script>
</body>
</html> 