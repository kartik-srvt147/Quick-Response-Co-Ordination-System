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

// Handle actions
$success_message = '';
$error_message = '';

// Mark notification as read
if (isset($_GET['action']) && $_GET['action'] == 'read' && isset($_GET['id'])) {
    $notification_id = $_GET['id'];
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $currentUser['id']);
    
    if ($stmt->execute()) {
        $success_message = "Notification marked as read.";
    } else {
        $error_message = "Error updating notification.";
    }
}

// Mark all notifications as read
if (isset($_GET['action']) && $_GET['action'] == 'read_all') {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $currentUser['id']);
    
    if ($stmt->execute()) {
        $success_message = "All notifications marked as read.";
    } else {
        $error_message = "Error updating notifications.";
    }
}

// Delete notification
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $notification_id = $_GET['id'];
    
    $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $currentUser['id']);
    
    if ($stmt->execute()) {
        $success_message = "Notification deleted.";
    } else {
        $error_message = "Error deleting notification.";
    }
}

// Delete all notifications
if (isset($_GET['action']) && $_GET['action'] == 'delete_all') {
    $sql = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $currentUser['id']);
    
    if ($stmt->execute()) {
        $success_message = "All notifications deleted.";
    } else {
        $error_message = "Error deleting notifications.";
    }
}

// Get notifications for current user
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$result = $stmt->get_result();

// Set page title
$page_title = "Notifications - QRCS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="./output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    <?php include 'includes/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Back button -->
        <div class="mb-6">
            <a href="<?php echo $currentUser['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="text-blue-500 hover:underline">
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
        
        <!-- Notifications Header -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-white">Notifications</h1>
                
                <div class="flex space-x-4">
                    <?php if ($result && $result->num_rows > 0): ?>
                    <a href="?action=read_all" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md transition-colors duration-300 text-sm flex items-center">
                        <i class="fas fa-check-double mr-2"></i>Mark All as Read
                    </a>
                    <a href="?action=delete_all" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md transition-colors duration-300 text-sm flex items-center" onclick="return confirm('Are you sure you want to delete all notifications?');">
                        <i class="fas fa-trash mr-2"></i>Delete All
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Notifications List -->
        <div class="bg-gray-900 rounded-lg border border-gray-800">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="divide-y divide-gray-800">
                    <?php while ($notification = $result->fetch_assoc()): ?>
                        <?php
                        // Determine notification icon and color
                        $icon = 'bell';
                        $color_class = 'text-blue-500';
                        
                        switch ($notification['type']) {
                            case 'emergency':
                                $icon = 'exclamation-circle';
                                $color_class = 'text-red-500';
                                break;
                            case 'incident_update':
                                $icon = 'info-circle';
                                $color_class = 'text-blue-500';
                                break;
                            case 'resource_update':
                                $icon = 'ambulance';
                                $color_class = 'text-green-500';
                                break;
                            case 'system':
                                $icon = 'cog';
                                $color_class = 'text-yellow-500';
                                break;
                        }
                        ?>
                        <div class="p-6 <?php echo $notification['is_read'] ? 'bg-gray-900' : 'bg-gray-800'; ?> hover:bg-gray-800 transition-colors">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-<?php echo $icon; ?> <?php echo $color_class; ?> text-2xl mt-1"></i>
                                </div>
                                <div class="ml-4 flex-grow">
                                    <div class="flex justify-between">
                                        <h3 class="text-lg font-medium text-white">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                            <span class="ml-2 inline-block h-2 w-2 rounded-full bg-red-500"></span>
                                            <?php endif; ?>
                                        </h3>
                                        <span class="text-sm text-gray-400">
                                            <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-gray-300">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </p>
                                    <?php if (!empty($notification['link'])): ?>
                                    <div class="mt-2">
                                        <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="text-blue-400 hover:underline">
                                            View Details <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <div class="mt-4 flex space-x-4">
                                        <?php if (!$notification['is_read']): ?>
                                        <a href="?action=read&id=<?php echo $notification['id']; ?>" class="text-blue-400 hover:text-blue-300 text-sm">
                                            <i class="fas fa-check mr-1"></i> Mark as Read
                                        </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $notification['id']; ?>" class="text-red-400 hover:text-red-300 text-sm" onclick="return confirm('Are you sure you want to delete this notification?');">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-16">
                    <i class="fas fa-bell-slash text-gray-600 text-6xl mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-400 mb-2">No Notifications</h3>
                    <p class="text-gray-500">You have no notifications at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Refresh notification count after actions
        document.addEventListener('DOMContentLoaded', function() {
            // If we've performed any actions, update the notification badge
            <?php if (!empty($success_message)): ?>
            if (window.notificationSystem) {
                window.notificationSystem.updateNotificationBadge();
            }
            <?php endif; ?>
        });
    </script>
</body>
</html> 