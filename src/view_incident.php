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

// Check if incident ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No incident ID provided.";
    
    // Redirect based on user role
    if ($currentUser['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit;
}

$incident_id = $_GET['id'];

// Get incident details
$sql = "SELECT i.*, 
        CONCAT(u.first_name, ' ', u.last_name) as reporter_name, 
        u.email as reporter_email,
        u.phone as reporter_phone
        FROM incidents i
        JOIN users u ON i.reported_by = u.id
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if incident exists
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Incident not found.";
    
    // Redirect based on user role
    if ($currentUser['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit;
}

// Get incident data
$incident = $result->fetch_assoc();

// Check access permissions
// Regular users can only view their own incidents
if ($currentUser['role'] !== 'admin' && $incident['reported_by'] != $currentUser['id']) {
    $_SESSION['error'] = "You don't have permission to view this incident.";
    header("Location: user_dashboard.php");
    exit;
}

// Format display status
$display_status = $incident['status'];
switch ($incident['status']) {
    case 'reported':
        $display_status = 'Pending';
        $status_class = 'bg-yellow-500 bg-opacity-50 text-white';
        break;
    case 'pending':
        $display_status = 'Pending';
        $status_class = 'bg-yellow-500 bg-opacity-50 text-white';
        break;
    case 'active':
        $display_status = 'Active';
        $status_class = 'bg-blue-500 bg-opacity-50 text-white';
        break;
    case 'responding':
        $display_status = 'Responding';
        $status_class = 'bg-purple-500 bg-opacity-50 text-white';
        break;
    case 'resolved':
    case 'closed':
        $display_status = 'Resolved';
        $status_class = 'bg-green-500 bg-opacity-50 text-white';
        break;
    case 'rejected':
        $display_status = 'Rejected';
        $status_class = 'bg-red-500 bg-opacity-50 text-white';
        break;
    default:
        $display_status = ucfirst($incident['status']);
        $status_class = 'bg-gray-500 bg-opacity-50 text-white';
}

// Parse additional data if available
$additional_data = [];
if (!empty($incident['additional_data'])) {
    $additional_data = json_decode($incident['additional_data'], true);
}

// Set page title
$page_title = "Incident #" . $incident_id . " - QRCS";
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
            padding-top: 5rem; /* Add body padding for fixed navbar */
        }
        main {
            padding-top: 1rem; /* Additional spacing for content */
        }
        .data-row {
            display: flex;
            border-bottom: 1px solid #333;
            padding: 0.75rem 0;
        }
        .data-label {
            width: 30%;
            font-weight: 500;
            color: #9ca3af;
        }
        .data-value {
            width: 70%;
            color: #e5e7eb;
        }
    </style>
</head>
<body class="bg-black min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Back button -->
        <div class="mb-6">
            <a href="<?php echo $currentUser['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="text-blue-500 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Incident Title -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-white">
                <?php echo htmlspecialchars($incident['title']); ?>
                <span class="text-lg ml-3 px-3 py-1 rounded-full <?php echo $status_class; ?>">
                    <?php echo $display_status; ?>
                </span>
            </h1>
            <!-- Admin action buttons -->
            <?php if ($currentUser['role'] === 'admin'): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php if ($incident['status'] == 'pending' || $incident['status'] == 'reported'): ?>
                <a href="manage_incident.php?id=<?php echo $incident_id; ?>&action=approve" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-base font-medium transition-all duration-200 shadow-md hover:shadow-lg text-center" style="margin-right: 1rem;">
                    <i class="fas fa-check mr-2"></i>Approve
                </a>
                <a href="manage_incident.php?id=<?php echo $incident_id; ?>&action=reject" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg text-base font-medium transition-all duration-200 shadow-md hover:shadow-lg text-center" style="margin-right: 1rem;">
                    <i class="fas fa-ban mr-2"></i>Reject
                </a>
                <?php elseif ($incident['status'] == 'active'): ?>
                <a href="manage_incident.php?id=<?php echo $incident_id; ?>&action=dispatch" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-base font-medium transition-all duration-200 shadow-md hover:shadow-lg text-center" style="margin-right: 1rem;">
                    <i class="fas fa-truck mr-2"></i>Dispatch
                </a>
                <?php elseif ($incident['status'] == 'responding'): ?>
                <a href="manage_incident.php?id=<?php echo $incident_id; ?>&action=resolve" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-base font-medium transition-all duration-200 shadow-md hover:shadow-lg text-center" style="margin-right: 1rem;">
                    <i class="fas fa-check-circle mr-2"></i>Resolve
                </a>
                <?php endif; ?>
                <a href="manage_incident.php?id=<?php echo $incident_id; ?>&action=delete" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg text-base font-medium transition-all duration-200 shadow-md hover:shadow-lg text-center">
                    <i class="fas fa-trash mr-2"></i>Delete
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main incident information -->
            <div class="lg:col-span-2">
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mb-8">
                    <h2 class="text-xl font-bold text-white mb-4">Incident Details</h2>
                    
                    <div class="space-y-2">
                        <div class="data-row">
                            <div class="data-label">Incident ID</div>
                            <div class="data-value">#<?php echo $incident['id']; ?></div>
                        </div>
                        <div class="data-row">
                            <div class="data-label">Type</div>
                            <div class="data-value"><?php echo htmlspecialchars($incident['title']); ?></div>
                        </div>
                        <div class="data-row">
                            <div class="data-label">Severity</div>
                            <div class="data-value"><?php echo ucfirst(htmlspecialchars($incident['severity'])); ?></div>
                        </div>
                        <div class="data-row">
                            <div class="data-label">Status</div>
                            <div class="data-value">
                                <span class="px-2 py-1 rounded-full <?php echo $status_class; ?>">
                                    <?php echo $display_status; ?>
                                </span>
                                <?php if (strtolower($display_status) !== strtolower($incident['status'])): ?>
                                <span class="text-xs text-gray-400 ml-2">(<?php echo $incident['status']; ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="data-row">
                            <div class="data-label">Reported By</div>
                            <div class="data-value">
                                <?php echo htmlspecialchars($incident['reporter_name']); ?>
                                <?php if ($currentUser['role'] === 'admin'): ?>
                                <div class="text-sm text-gray-400 mt-1">
                                    <div><?php echo htmlspecialchars($incident['reporter_email']); ?></div>
                                    <?php if (!empty($incident['reporter_phone'])): ?>
                                    <div><?php echo htmlspecialchars($incident['reporter_phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="data-row">
                            <div class="data-label">Reported At</div>
                            <div class="data-value"><?php echo date('F j, Y g:i a', strtotime($incident['reported_at'])); ?></div>
                        </div>
                        <?php if (!empty($incident['resolved_at'])): ?>
                        <div class="data-row">
                            <div class="data-label">Resolved At</div>
                            <div class="data-value"><?php echo date('F j, Y g:i a', strtotime($incident['resolved_at'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="data-row">
                            <div class="data-label">Location</div>
                            <div class="data-value"><?php echo htmlspecialchars($incident['location']); ?></div>
                        </div>
                        <?php if (!empty($additional_data) && !empty($additional_data['peopleAffected'])): ?>
                        <div class="data-row">
                            <div class="data-label">People Affected</div>
                            <div class="data-value"><?php echo htmlspecialchars($additional_data['peopleAffected']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($additional_data) && !empty($additional_data['landmark'])): ?>
                        <div class="data-row">
                            <div class="data-label">Nearest Landmark</div>
                            <div class="data-value"><?php echo htmlspecialchars($additional_data['landmark']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($additional_data) && !empty($additional_data['contactPhone'])): ?>
                        <div class="data-row">
                            <div class="data-label">Contact Phone</div>
                            <div class="data-value"><?php echo htmlspecialchars($additional_data['contactPhone']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($additional_data) && !empty($additional_data['resources'])): ?>
                        <div class="data-row">
                            <div class="data-label">Resources Needed</div>
                            <div class="data-value">
                                <ul class="list-disc list-inside">
                                <?php foreach($additional_data['resources'] as $resource): ?>
                                    <li><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($resource))); ?></li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Description Section -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Emergency Description</h2>
                    <div class="text-gray-300 whitespace-pre-line leading-relaxed">
                        <?php echo htmlspecialchars($incident['description']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar content -->
            <div>
                <!-- Map Section -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mb-8">
                    <h2 class="text-xl font-bold text-white mb-4">Location</h2>
                    <div id="incidentMap" class="h-64 rounded-lg"></div>
                </div>
                
                <!-- Timeline Section (for Admin only) -->
                <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Incident Timeline</h2>
                    <div class="space-y-6">
                        <div class="flex">
                            <div class="mr-5 flex flex-col items-center">
                                <div class="rounded-full bg-blue-500 h-5 w-5 flex-shrink-0"></div>
                                <div class="bg-gray-700 flex-grow w-0.5 mt-2"></div>
                            </div>
                            <div class="pt-0.5">
                                <div class="text-white font-medium">Reported</div>
                                <div class="text-sm text-gray-400 mt-1"><?php echo date('F j, Y g:i a', strtotime($incident['reported_at'])); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($incident['status'] == 'active' || $incident['status'] == 'responding' || $incident['status'] == 'resolved'): ?>
                        <div class="flex">
                            <div class="mr-5 flex flex-col items-center">
                                <div class="rounded-full bg-blue-500 h-5 w-5 flex-shrink-0"></div>
                                <div class="bg-gray-700 flex-grow w-0.5 mt-2"></div>
                            </div>
                            <div class="pt-0.5">
                                <div class="text-white font-medium">Approved</div>
                                <div class="text-sm text-gray-400 mt-1">Status set to Active</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($incident['status'] == 'responding' || $incident['status'] == 'resolved'): ?>
                        <div class="flex">
                            <div class="mr-5 flex flex-col items-center">
                                <div class="rounded-full bg-blue-500 h-5 w-5 flex-shrink-0"></div>
                                <div class="bg-gray-700 flex-grow w-0.5 mt-2"></div>
                            </div>
                            <div class="pt-0.5">
                                <div class="text-white font-medium">Response Dispatched</div>
                                <div class="text-sm text-gray-400 mt-1">Units deployed to the scene</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($incident['status'] == 'resolved'): ?>
                        <div class="flex">
                            <div class="mr-5 flex flex-col items-center">
                                <div class="rounded-full bg-green-500 h-5 w-5 flex-shrink-0"></div>
                                <div class="bg-transparent flex-grow w-0.5"></div>
                            </div>
                            <div class="pt-0.5">
                                <div class="text-white font-medium">Resolved</div>
                                <div class="text-sm text-gray-400 mt-1">
                                    <?php echo !empty($incident['resolved_at']) ? date('F j, Y g:i a', strtotime($incident['resolved_at'])) : 'Date not recorded'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($incident['status'] == 'rejected'): ?>
                        <div class="flex">
                            <div class="mr-5 flex flex-col items-center">
                                <div class="rounded-full bg-red-500 h-5 w-5 flex-shrink-0"></div>
                                <div class="bg-transparent flex-grow w-0.5"></div>
                            </div>
                            <div class="pt-0.5">
                                <div class="text-white font-medium">Rejected</div>
                                <div class="text-sm text-gray-400 mt-1">Report was declined</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            const map = L.map('incidentMap').setView([
                <?php echo !empty($incident['latitude']) ? $incident['latitude'] : 0; ?>, 
                <?php echo !empty($incident['longitude']) ? $incident['longitude'] : 0; ?>
            ], 14);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Add marker for incident location
            <?php if (!empty($incident['latitude']) && !empty($incident['longitude'])): ?>
            L.marker([<?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?>])
                .addTo(map)
                .bindPopup("<?php echo addslashes(htmlspecialchars($incident['location'])); ?>");
            <?php endif; ?>
        });
    </script>
</body>
</html> 