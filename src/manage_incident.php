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

// Check if this is an admin user, redirect if not
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

// Check if id and action are provided
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = "Invalid request. Missing incident ID or action.";
    header("Location: admin_dashboard.php");
    exit;
}

$incident_id = $_GET['id'];
$action = $_GET['action'];

// Validate incident exists
$check_sql = "SELECT * FROM incidents WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $incident_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Incident not found.";
    header("Location: admin_dashboard.php");
    exit;
}

$incident = $result->fetch_assoc();

// Process the action
switch($action) {
    case 'approve':
        // Change status from pending/reported to active
        if ($incident['status'] == 'pending' || $incident['status'] == 'reported') {
            $update_sql = "UPDATE incidents SET status = 'active' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $incident_id);
            
            if ($update_stmt->execute()) {
                // Send notification to reporter
                $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 
                                     VALUES (?, 'Emergency Report Approved', 'Your emergency report has been approved and is now active.', 'info')";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("i", $incident['reported_by']);
                $notification_stmt->execute();
                
                $_SESSION['success'] = "Incident #{$incident_id} approved and marked as active.";
            } else {
                $_SESSION['error'] = "Failed to approve incident. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Can only approve pending incidents.";
        }
        break;
        
    case 'reject':
        // Change status to rejected
        if ($incident['status'] == 'pending' || $incident['status'] == 'reported') {
            $update_sql = "UPDATE incidents SET status = 'rejected' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $incident_id);
            
            if ($update_stmt->execute()) {
                // Send notification to reporter
                $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 
                                     VALUES (?, 'Emergency Report Rejected', 'Your emergency report has been reviewed and rejected.', 'alert')";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("i", $incident['reported_by']);
                $notification_stmt->execute();
                
                $_SESSION['success'] = "Incident #{$incident_id} has been rejected.";
            } else {
                $_SESSION['error'] = "Failed to reject incident. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Can only reject pending incidents.";
        }
        break;
        
    case 'dispatch':
        // Show dispatch form or process resource assignment
        if ($incident['status'] == 'active') {
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispatch_resources'])) {
                // Process resource assignment
                if (isset($_POST['resources']) && is_array($_POST['resources']) && !empty($_POST['resources'])) {
                    $assigned_count = 0;
                    $resources_list = [];
                    
                    // Begin transaction
                    $conn->begin_transaction();
                    
                    try {
                        // Update incident status to responding
                        $update_incident_sql = "UPDATE incidents SET status = 'responding' WHERE id = ?";
                        $update_incident_stmt = $conn->prepare($update_incident_sql);
                        $update_incident_stmt->bind_param("i", $incident_id);
                        $update_incident_stmt->execute();
                        
                        // Assign each resource
                        foreach ($_POST['resources'] as $resource_id) {
                            $resource_id = intval($resource_id);
                            
                            // Update resource status to in_use and assign to incident
                            $update_resource_sql = "UPDATE resources SET status = 'in_use', assigned_to = ? WHERE id = ? AND status = 'available'";
                            $update_resource_stmt = $conn->prepare($update_resource_sql);
                            $update_resource_stmt->bind_param("ii", $incident_id, $resource_id);
                            
                            if ($update_resource_stmt->execute() && $update_resource_stmt->affected_rows > 0) {
                                $assigned_count++;
                                
                                // Get resource name for notification
                                $resource_name_sql = "SELECT name FROM resources WHERE id = ?";
                                $resource_name_stmt = $conn->prepare($resource_name_sql);
                                $resource_name_stmt->bind_param("i", $resource_id);
                                $resource_name_stmt->execute();
                                $resource_result = $resource_name_stmt->get_result();
                                
                                if ($resource_row = $resource_result->fetch_assoc()) {
                                    $resources_list[] = $resource_row['name'];
                                }
                            }
                        }
                        
                        // Commit transaction
                        $conn->commit();
                        
                        // Send notification to reporter
                        $resources_text = !empty($resources_list) ? " (" . implode(", ", $resources_list) . ")" : "";
                        $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 
                                           VALUES (?, 'Response Team Dispatched', 'A response team has been dispatched to your emergency{$resources_text}.', 'info')";
                        $notification_stmt = $conn->prepare($notification_sql);
                        $notification_stmt->bind_param("i", $incident['reported_by']);
                        $notification_stmt->execute();
                        
                        // Success message
                        if ($assigned_count > 0) {
                            $_SESSION['success'] = "Response team dispatched to incident #{$incident_id}. {$assigned_count} resources assigned.";
                            
                            // Redirect to admin dashboard
                            header("Location: admin_dashboard.php");
                            exit;
                        } else {
                            $_SESSION['error'] = "No resources were assigned. Please try again.";
                        }
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $conn->rollback();
                        $_SESSION['error'] = "Error dispatching resources: " . $e->getMessage();
                    }
                } else {
                    $_SESSION['error'] = "No resources selected. Please select at least one resource.";
                }
            } else {
                // Show dispatch form
                $title = "Dispatch Resources";
                $page_title = "Dispatch Resources - Incident #{$incident_id}";
                
                // Get available resources
                $resources_sql = "SELECT id, name, type, description, location FROM resources WHERE status = 'available' ORDER BY type, name";
                $resources_result = $conn->query($resources_sql);
                
                // Render dispatch form
                include 'includes/header.php';
                ?>
                <div class="container mx-auto px-4 py-8">
                    <!-- Back button -->
                    <div class="mb-6">
                        <a href="view_incident.php?id=<?php echo $incident_id; ?>" class="text-blue-500 hover:underline">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Incident
                        </a>
                    </div>
                    
                    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                        <h2 class="text-xl font-bold text-white mb-6">Dispatch Resources to Incident #<?php echo $incident_id; ?></h2>
                        
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-white mb-3">Incident Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-300">
                                <div>
                                    <strong>Type:</strong> <?php echo htmlspecialchars($incident['title']); ?>
                                </div>
                                <div>
                                    <strong>Severity:</strong> <?php echo ucfirst($incident['severity']); ?>
                                </div>
                                <div>
                                    <strong>Location:</strong> <?php echo htmlspecialchars($incident['location']); ?>
                                </div>
                                <div>
                                    <strong>Reported:</strong> <?php echo date('M d, Y H:i', strtotime($incident['reported_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($resources_result && $resources_result->num_rows > 0): ?>
                        <form method="POST" action="manage_incident.php?id=<?php echo $incident_id; ?>&action=dispatch">
                            <h3 class="text-lg font-medium text-white mb-3">Select Resources to Dispatch</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                <?php 
                                $current_type = '';
                                while ($resource = $resources_result->fetch_assoc()):
                                    // Display type heading when type changes
                                    if ($current_type != $resource['type']):
                                        $current_type = $resource['type'];
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
                                <div class="col-span-full mt-4 mb-2">
                                    <h4 class="text-blue-400 font-medium">
                                        <i class="fas fa-<?php echo $type_icon; ?> mr-2"></i>
                                        <?php echo ucfirst($current_type); ?>
                                    </h4>
                                </div>
                                <?php endif; ?>
                                
                                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700 hover:border-blue-500 transition-colors">
                                    <label class="flex items-start cursor-pointer">
                                        <input type="checkbox" name="resources[]" value="<?php echo $resource['id']; ?>" class="mt-1 mr-3">
                                        <div>
                                            <div class="text-gray-100 font-medium"><?php echo htmlspecialchars($resource['name']); ?></div>
                                            <?php if (!empty($resource['location'])): ?>
                                            <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($resource['location']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($resource['description'])): ?>
                                            <div class="text-gray-500 text-sm mt-1"><?php echo htmlspecialchars(substr($resource['description'], 0, 80)); ?><?php echo strlen($resource['description']) > 80 ? '...' : ''; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <div class="flex justify-end space-x-4">
                                <a href="view_incident.php?id=<?php echo $incident_id; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" name="dispatch_resources" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-truck mr-2"></i>Dispatch Selected Resources
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="bg-yellow-500 bg-opacity-20 text-yellow-300 p-4 rounded-lg mb-6">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No available resources found. Please mark some resources as available before dispatching.
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="view_incident.php?id=<?php echo $incident_id; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                Back to Incident
                            </a>
                            <a href="manage_resources.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="fas fa-tools mr-2"></i>Manage Resources
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                include 'includes/footer.php';
                exit;
            }
        } else {
            $_SESSION['error'] = "Can only dispatch teams to active incidents.";
        }
        break;
        
    case 'resolve':
        // Change status to resolved
        if ($incident['status'] == 'responding' || $incident['status'] == 'active') {
            $update_sql = "UPDATE incidents SET status = 'resolved', resolved_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $incident_id);
            
            if ($update_stmt->execute()) {
                // Free up all resources assigned to this incident
                $free_resources_sql = "UPDATE resources SET status = 'available', assigned_to = NULL WHERE assigned_to = ?";
                $free_resources_stmt = $conn->prepare($free_resources_sql);
                $free_resources_stmt->bind_param("i", $incident_id);
                $free_resources_stmt->execute();
                
                // Send notification to reporter
                $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 
                                    VALUES (?, 'Emergency Resolved', 'Your emergency has been marked as resolved.', 'info')";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("i", $incident['reported_by']);
                $notification_stmt->execute();
                
                $_SESSION['success'] = "Incident #{$incident_id} has been resolved.";
            } else {
                $_SESSION['error'] = "Failed to resolve incident. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Can only resolve active or responding incidents.";
        }
        break;
        
    case 'delete':
        // Delete the incident
        $delete_sql = "DELETE FROM incidents WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $incident_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Incident #{$incident_id} has been deleted.";
        } else {
            $_SESSION['error'] = "Failed to delete incident. Please try again.";
        }
        break;
        
    default:
        $_SESSION['error'] = "Invalid action requested.";
}

// Redirect back to admin dashboard
header("Location: admin_dashboard.php");
exit;
?> 