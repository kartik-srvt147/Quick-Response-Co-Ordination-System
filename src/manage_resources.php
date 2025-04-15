<?php
// Include auth and config files
require_once 'includes/config.php';
require_once 'includes/notification_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();

// Check if this is an admin user, redirect if not
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

// Handle resource actions
$success_message = '';
$error_message = '';

// Handle resource status update
if (isset($_GET['id']) && isset($_GET['action'])) {
    $resource_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Verify resource exists
    $check_sql = "SELECT * FROM resources WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $resource_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Resource not found.";
    } else {
        $resource = $result->fetch_assoc();
        
        switch ($action) {
            case 'available':
                $update_sql = "UPDATE resources SET status = 'available', assigned_to = NULL WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $resource_id);
                
                if ($update_stmt->execute()) {
                    // Create notification
                    createResourceNotification($resource_id, $resource['name'], 'available');
                    $success_message = "Resource #{$resource_id} marked as available.";
                } else {
                    $error_message = "Failed to update resource status.";
                }
                break;
                
            case 'unavailable':
                $update_sql = "UPDATE resources SET status = 'unavailable' WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $resource_id);
                
                if ($update_stmt->execute()) {
                    // Create notification
                    createResourceNotification($resource_id, $resource['name'], 'unavailable');
                    $success_message = "Resource #{$resource_id} marked as unavailable.";
                } else {
                    $error_message = "Failed to update resource status.";
                }
                break;
                
            case 'maintenance':
                $update_sql = "UPDATE resources SET status = 'maintenance' WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $resource_id);
                
                if ($update_stmt->execute()) {
                    // Create notification
                    createResourceNotification($resource_id, $resource['name'], 'under maintenance');
                    $success_message = "Resource #{$resource_id} marked as under maintenance.";
                } else {
                    $error_message = "Failed to update resource status.";
                }
                break;
                
            case 'delete':
                // Check if resource is assigned to an incident
                if (!empty($resource['assigned_to'])) {
                    $error_message = "Cannot delete resource that is currently assigned to an incident.";
                } else {
                    $delete_sql = "DELETE FROM resources WHERE id = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("i", $resource_id);
                    
                    if ($delete_stmt->execute()) {
                        $success_message = "Resource #{$resource_id} deleted successfully.";
                    } else {
                        $error_message = "Failed to delete resource.";
                    }
                }
                break;
                
            default:
                $error_message = "Invalid action.";
        }
    }
}

// Handle resource creation/edit form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create' || $action === 'edit') {
        // Get form data
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $description = $conn->real_escape_string($_POST['description']);
        $status = $conn->real_escape_string($_POST['status']);
        $location = !empty($_POST['location']) ? $conn->real_escape_string($_POST['location']) : null;
        
        if (empty($name) || empty($type) || empty($description)) {
            $error_message = "Name, type, and description are required fields.";
        } else {
            if ($action === 'create') {
                // Create new resource
                $sql = "INSERT INTO resources (name, type, description, status, location) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $name, $type, $description, $status, $location);
                
                if ($stmt->execute()) {
                    $success_message = "New resource created successfully.";
                } else {
                    $error_message = "Error creating resource: " . $conn->error;
                }
            } else {
                // Edit existing resource
                $resource_id = $_POST['resource_id'];
                
                $sql = "UPDATE resources SET name = ?, type = ?, description = ?, status = ?, location = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $name, $type, $description, $status, $location, $resource_id);
                
                if ($stmt->execute()) {
                    $success_message = "Resource updated successfully.";
                } else {
                    $error_message = "Error updating resource: " . $conn->error;
                }
            }
        }
    }
}

// Get resource data for editing
$edit_resource = null;
if (isset($_GET['id']) && isset($_GET['edit'])) {
    $resource_id = $_GET['id'];
    
    $sql = "SELECT * FROM resources WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_resource = $result->fetch_assoc();
    }
}

// Get all resources
$sql = "SELECT r.*, 
        CASE
            WHEN r.assigned_to IS NOT NULL THEN (SELECT title FROM incidents WHERE id = r.assigned_to)
            ELSE NULL
        END as incident_title
        FROM resources r
        ORDER BY 
            CASE r.status
                WHEN 'available' THEN 1
                WHEN 'in_use' THEN 2
                WHEN 'unavailable' THEN 3
                WHEN 'maintenance' THEN 4
            END, 
            r.type, 
            r.name";
$result = $conn->query($sql);

// Set page title
$page_title = "Manage Resources - QRCS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="./output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    <?php include 'includes/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Back button -->
        <div class="mb-6">
            <a href="admin_dashboard.php" class="text-blue-500 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="bg-green-600 bg-opacity-25 border border-green-500 text-green-100 px-4 py-3 rounded mb-6 flex items-center">
            <i class="fas fa-check-circle text-2xl mr-3"></i>
            <div>
                <p class="font-bold">Success!</p>
                <p><?php echo $success_message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="bg-red-600 bg-opacity-25 border border-red-500 text-red-100 px-4 py-3 rounded mb-6 flex items-center">
            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
            <div>
                <p class="font-bold">Error</p>
                <p><?php echo $error_message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Resource form -->
            <div>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-4">
                        <?php echo $edit_resource ? 'Edit Resource' : 'Add New Resource'; ?>
                    </h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-4">
                        <input type="hidden" name="action" value="<?php echo $edit_resource ? 'edit' : 'create'; ?>">
                        <?php if ($edit_resource): ?>
                        <input type="hidden" name="resource_id" value="<?php echo $edit_resource['id']; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label for="name" class="block text-gray-300 mb-1">Resource Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required
                                value="<?php echo $edit_resource ? htmlspecialchars($edit_resource['name']) : ''; ?>"
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="type" class="block text-gray-300 mb-1">Resource Type <span class="text-red-500">*</span></label>
                            <select id="type" name="type" required
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                                <option value="">-- Select Type --</option>
                                <option value="vehicle" <?php echo ($edit_resource && $edit_resource['type'] == 'vehicle') ? 'selected' : ''; ?>>Vehicle</option>
                                <option value="equipment" <?php echo ($edit_resource && $edit_resource['type'] == 'equipment') ? 'selected' : ''; ?>>Equipment</option>
                                <option value="personnel" <?php echo ($edit_resource && $edit_resource['type'] == 'personnel') ? 'selected' : ''; ?>>Personnel</option>
                                <option value="facility" <?php echo ($edit_resource && $edit_resource['type'] == 'facility') ? 'selected' : ''; ?>>Facility</option>
                                <option value="other" <?php echo ($edit_resource && $edit_resource['type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status" class="block text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                                <option value="available" <?php echo ($edit_resource && $edit_resource['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="unavailable" <?php echo ($edit_resource && $edit_resource['status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                <option value="maintenance" <?php echo ($edit_resource && $edit_resource['status'] == 'maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                                <?php if ($edit_resource && $edit_resource['status'] == 'in_use'): ?>
                                <option value="in_use" selected>In Use</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="location" class="block text-gray-300 mb-1">Location</label>
                            <input type="text" id="location" name="location"
                                value="<?php echo $edit_resource && !empty($edit_resource['location']) ? htmlspecialchars($edit_resource['location']) : ''; ?>"
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-gray-300 mb-1">Description <span class="text-red-500">*</span></label>
                            <textarea id="description" name="description" rows="4" required
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"><?php echo $edit_resource ? htmlspecialchars($edit_resource['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="fas fa-<?php echo $edit_resource ? 'save' : 'plus'; ?> mr-2"></i>
                                <?php echo $edit_resource ? 'Update Resource' : 'Add Resource'; ?>
                            </button>
                        </div>
                        
                        <?php if ($edit_resource): ?>
                        <div class="pt-2">
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="block text-center bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel Editing
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Resource Stats -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mt-6">
                    <h2 class="text-xl font-bold text-white mb-4">Resource Overview</h2>
                    
                    <?php
                    // Get resource stats
                    $stats_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                        SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END) as unavailable,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                    FROM resources";
                    
                    $stats_result = $conn->query($stats_sql);
                    $stats = $stats_result->fetch_assoc();
                    ?>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-300">
                            <span>Total Resources:</span>
                            <span class="font-medium"><?php echo $stats['total']; ?></span>
                        </div>
                        <div class="flex justify-between text-gray-300">
                            <span>Available:</span>
                            <span class="text-green-400 font-medium"><?php echo $stats['available']; ?></span>
                        </div>
                        <div class="flex justify-between text-gray-300">
                            <span>In Use:</span>
                            <span class="text-blue-400 font-medium"><?php echo $stats['in_use']; ?></span>
                        </div>
                        <div class="flex justify-between text-gray-300">
                            <span>Unavailable:</span>
                            <span class="text-red-400 font-medium"><?php echo $stats['unavailable']; ?></span>
                        </div>
                        <div class="flex justify-between text-gray-300">
                            <span>Under Maintenance:</span>
                            <span class="text-yellow-400 font-medium"><?php echo $stats['maintenance']; ?></span>
                        </div>
                    </div>
                    
                    <!-- Resource type breakdown -->
                    <?php
                    $type_sql = "SELECT 
                        type,
                        COUNT(*) as count
                    FROM resources
                    GROUP BY type
                    ORDER BY count DESC";
                    
                    $type_result = $conn->query($type_sql);
                    
                    if ($type_result && $type_result->num_rows > 0):
                    ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-white mb-3">Resources by Type</h3>
                        <div class="space-y-2">
                            <?php while ($type = $type_result->fetch_assoc()): ?>
                            <div class="flex justify-between text-gray-300">
                                <span><?php echo ucfirst($type['type']); ?>:</span>
                                <span class="font-medium"><?php echo $type['count']; ?></span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Resources List -->
            <div class="lg:col-span-2">
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-white">Resources</h2>
                        
                        <div class="flex space-x-4">
                            <select id="statusFilter" class="bg-gray-800 text-gray-300 rounded-md border border-gray-700 px-3 py-1">
                                <option value="all">All Status</option>
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="unavailable">Unavailable</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            
                            <select id="typeFilter" class="bg-gray-800 text-gray-300 rounded-md border border-gray-700 px-3 py-1">
                                <option value="all">All Types</option>
                                <option value="vehicle">Vehicle</option>
                                <option value="equipment">Equipment</option>
                                <option value="personnel">Personnel</option>
                                <option value="facility">Facility</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-900">
                            <thead>
                                <tr class="bg-gray-800">
                                    <th class="py-2 px-4 text-left text-gray-300">#</th>
                                    <th class="py-2 px-4 text-left text-gray-300">Resource</th>
                                    <th class="py-2 px-4 text-left text-gray-300">Type</th>
                                    <th class="py-2 px-4 text-left text-gray-300">Status</th>
                                    <th class="py-2 px-4 text-left text-gray-300">Assignment</th>
                                    <th class="py-2 px-4 text-left text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($resource = $result->fetch_assoc()): ?>
                                <tr class="border-t border-gray-800 resource-row" data-status="<?php echo $resource['status']; ?>" data-type="<?php echo $resource['type']; ?>">
                                    <td class="py-3 px-4 text-gray-300"><?php echo $resource['id']; ?></td>
                                    <td class="py-3 px-4">
                                        <div class="text-gray-100 font-medium"><?php echo htmlspecialchars($resource['name']); ?></div>
                                        <?php if (!empty($resource['location'])): ?>
                                        <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($resource['location']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-300">
                                        <?php
                                        $type_icon = '';
                                        switch ($resource['type']) {
                                            case 'vehicle':
                                                $type_icon = 'truck';
                                                break;
                                            case 'equipment':
                                                $type_icon = 'tools';
                                                break;
                                            case 'personnel':
                                                $type_icon = 'users';
                                                break;
                                            case 'facility':
                                                $type_icon = 'building';
                                                break;
                                            default:
                                                $type_icon = 'box';
                                        }
                                        ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-<?php echo $type_icon; ?> text-blue-400 mr-2"></i>
                                            <?php echo ucfirst($resource['type']); ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $status_class = '';
                                        switch ($resource['status']) {
                                            case 'available':
                                                $status_class = 'bg-green-800 text-green-100';
                                                break;
                                            case 'in_use':
                                                $status_class = 'bg-blue-800 text-blue-100';
                                                break;
                                            case 'unavailable':
                                                $status_class = 'bg-red-800 text-red-100';
                                                break;
                                            case 'maintenance':
                                                $status_class = 'bg-yellow-800 text-yellow-100';
                                                break;
                                            default:
                                                $status_class = 'bg-gray-800 text-gray-100';
                                        }
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $resource['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-300">
                                        <?php if (!empty($resource['assigned_to']) && !empty($resource['incident_title'])): ?>
                                        <a href="view_incident.php?id=<?php echo $resource['assigned_to']; ?>" class="text-blue-400 hover:underline">
                                            <?php echo htmlspecialchars($resource['incident_title']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-gray-500">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="grid grid-flow-col auto-cols-max gap-6">
                                            <!-- Edit button -->
                                            <a href="?id=<?php echo $resource['id']; ?>&edit=true" class="text-blue-400 hover:text-blue-300" title="Edit Resource" style="margin-right: 12px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Status change buttons -->
                                            <?php if ($resource['status'] != 'available'): ?>
                                            <a href="?id=<?php echo $resource['id']; ?>&action=available" class="text-green-400 hover:text-green-300" title="Mark as Available" style="margin-right: 12px;">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($resource['status'] != 'unavailable'): ?>
                                            <a href="?id=<?php echo $resource['id']; ?>&action=unavailable" class="text-red-400 hover:text-red-300" title="Mark as Unavailable" style="margin-right: 12px;">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($resource['status'] != 'maintenance'): ?>
                                            <a href="?id=<?php echo $resource['id']; ?>&action=maintenance" class="text-yellow-400 hover:text-yellow-300" title="Mark as Under Maintenance" style="margin-right: 12px;">
                                                <i class="fas fa-wrench"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <!-- Delete button - only if not assigned -->
                                            <?php if (empty($resource['assigned_to'])): ?>
                                            <a href="?id=<?php echo $resource['id']; ?>&action=delete" class="text-red-400 hover:text-red-300" title="Delete Resource" onclick="return confirm('Are you sure you want to delete this resource?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-box-open text-5xl mb-4"></i>
                        <p>No resources found. Add your first resource using the form.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Resource Details Section -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mt-6">
                    <h2 class="text-xl font-bold text-white mb-4">Resource Management Guide</h2>
                    
                    <div class="space-y-4 text-gray-300">
                        <div>
                            <h3 class="text-lg font-medium text-white mb-2">Resource Status Definitions</h3>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><span class="text-green-400 font-medium">Available</span> - Resource is ready for deployment</li>
                                <li><span class="text-blue-400 font-medium">In Use</span> - Currently assigned to an incident</li>
                                <li><span class="text-red-400 font-medium">Unavailable</span> - Not available for deployment</li>
                                <li><span class="text-yellow-400 font-medium">Maintenance</span> - Undergoing maintenance or repairs</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-white mb-2">Resource Types</h3>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><i class="fas fa-truck text-blue-400 mr-1"></i> <span class="font-medium">Vehicles</span> - Ambulances, fire trucks, police cars, rescue vehicles</li>
                                <li><i class="fas fa-tools text-blue-400 mr-1"></i> <span class="font-medium">Equipment</span> - Medical equipment, rescue tools, communication devices</li>
                                <li><i class="fas fa-users text-blue-400 mr-1"></i> <span class="font-medium">Personnel</span> - Medical staff, emergency responders, volunteers</li>
                                <li><i class="fas fa-building text-blue-400 mr-1"></i> <span class="font-medium">Facilities</span> - Medical centers, command posts, temporary shelters</li>
                                <li><i class="fas fa-box text-blue-400 mr-1"></i> <span class="font-medium">Other</span> - Supplies, food, water, fuel, etc.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-white mb-2">Resource Management Tips</h3>
                            <ul class="list-disc pl-5 space-y-2">
                                <li>Regularly update resource status to maintain accurate inventory</li>
                                <li>Include detailed descriptions for clear resource identification</li>
                                <li>Record location information for faster resource deployment</li>
                                <li>Resources assigned to incidents will automatically show "In Use" status</li>
                                <li>Resources must be marked as "Available" before they can be assigned to new incidents</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        
        function applyFilters() {
            const statusValue = statusFilter.value.toLowerCase();
            const typeValue = typeFilter.value.toLowerCase();
            
            document.querySelectorAll('.resource-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status').toLowerCase();
                const rowType = row.getAttribute('data-type').toLowerCase();
                
                const statusMatch = statusValue === 'all' || rowStatus === statusValue;
                const typeMatch = typeValue === 'all' || rowType === typeValue;
                
                if (statusMatch && typeMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Add event listeners to filters
        if (statusFilter && typeFilter) {
            statusFilter.addEventListener('change', applyFilters);
            typeFilter.addEventListener('change', applyFilters);
        }
    });
    </script>
</body>
</html> 