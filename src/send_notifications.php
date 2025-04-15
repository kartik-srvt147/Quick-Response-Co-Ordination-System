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
$notificationCount = getNotificationCount();

// Check if this is an admin user, redirect if not
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    if (empty($_POST['notification_title']) || empty($_POST['notification_message'])) {
        $error_message = "Please fill all required fields";
    } else {
        // Get form data
        $notification_title = $conn->real_escape_string($_POST['notification_title']);
        $notification_message = $conn->real_escape_string($_POST['notification_message']);
        $notification_type = $conn->real_escape_string($_POST['notification_type']);
        $notification_target = $conn->real_escape_string($_POST['notification_target']);
        
        // Determine target role based on selection
        $target_role = null;
        if ($notification_target !== 'all') {
            $target_role = $notification_target;
        }
        
        // Create the notification
        $notification_created = createSystemNotification($notification_title, $notification_message, $target_role);
        
        if ($notification_created) {
            $success_message = "Notification sent successfully.";
        } else {
            $error_message = "Error sending notification.";
        }
    }
}

// Get all users for the dropdown
$users_query = "SELECT id, first_name, last_name, email, role FROM users ORDER BY role, first_name";
$users_result = $conn->query($users_query);

// Set page title
$page_title = "Send Notifications - QRCS";
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
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Send Notification Form -->
            <div class="md:col-span-2">
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-6">Send Notification</h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <!-- Notification Type -->
                        <div>
                            <label for="notification_type" class="block text-gray-300 mb-2">Notification Type</label>
                            <select id="notification_type" name="notification_type" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-yellow-500">
                                <option value="system">System Message</option>
                                <option value="emergency">Emergency Alert</option>
                                <option value="incident_update">Incident Update</option>
                                <option value="resource_update">Resource Update</option>
                            </select>
                        </div>
                        
                        <!-- Recipients -->
                        <div>
                            <label for="notification_target" class="block text-gray-300 mb-2">Send To</label>
                            <select id="notification_target" name="notification_target" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-yellow-500">
                                <option value="all">All Users</option>
                                <option value="admin">Administrators Only</option>
                                <option value="responder">Responders Only</option>
                                <option value="reporter">Reporters Only</option>
                            </select>
                        </div>
                        
                        <!-- Title -->
                        <div>
                            <label for="notification_title" class="block text-gray-300 mb-2">Notification Title <span class="text-red-500">*</span></label>
                            <input type="text" id="notification_title" name="notification_title" required class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-yellow-500" placeholder="Enter notification title">
                        </div>
                        
                        <!-- Message -->
                        <div>
                            <label for="notification_message" class="block text-gray-300 mb-2">Message <span class="text-red-500">*</span></label>
                            <textarea id="notification_message" name="notification_message" rows="6" required class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-yellow-500" placeholder="Enter notification message"></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg transition-colors">
                                <i class="fas fa-paper-plane mr-2"></i>Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Information Panel -->
            <div>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Notification Tips</h2>
                    
                    <div class="space-y-4 text-gray-300">
                        <div>
                            <h3 class="text-lg font-medium text-white mb-2">Notification Types</h3>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><span class="text-blue-400 font-medium">System Message</span> - General system announcements</li>
                                <li><span class="text-red-400 font-medium">Emergency Alert</span> - Urgent notifications that require immediate attention</li>
                                <li><span class="text-green-400 font-medium">Incident Update</span> - Updates about specific emergency incidents</li>
                                <li><span class="text-yellow-400 font-medium">Resource Update</span> - Information about resource changes</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-white mb-2">Best Practices</h3>
                            <ul class="list-disc pl-5 space-y-2">
                                <li>Use clear, concise titles that communicate the purpose</li>
                                <li>Keep messages brief but informative</li>
                                <li>Only use Emergency Alert type for truly urgent situations</li>
                                <li>Target notifications to relevant user groups</li>
                                <li>Include actionable information when appropriate</li>
                            </ul>
                        </div>
                        
                        <div class="bg-yellow-900 bg-opacity-30 border border-yellow-800 rounded-lg p-4 mt-6">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-yellow-500 text-xl mt-0.5 mr-3"></i>
                                <p>Notifications will appear in users' notification panel and also generate real-time alerts when they are logged in.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Notifications -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mt-6">
                    <h2 class="text-xl font-bold text-white mb-4">Recently Sent</h2>
                    
                    <?php
                    // Get recently sent system notifications
                    $recent_query = "SELECT 
                                        title, 
                                        message, 
                                        type,
                                        MIN(created_at) as sent_date, 
                                        COUNT(*) as recipient_count 
                                    FROM notifications 
                                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                    GROUP BY title, message, type
                                    ORDER BY sent_date DESC 
                                    LIMIT 5";
                    $recent_result = $conn->query($recent_query);
                    
                    if ($recent_result && $recent_result->num_rows > 0):
                    ?>
                    <div class="space-y-4">
                        <?php while ($notification = $recent_result->fetch_assoc()): ?>
                        <div class="border-b border-gray-800 pb-3">
                            <div class="flex items-center">
                                <h4 class="text-white font-medium"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                <span class="ml-2 px-2 py-0.5 text-xs rounded-full 
                                    <?php 
                                    $type_class = "bg-blue-900 text-blue-200";
                                    if ($notification['type'] === 'emergency') {
                                        $type_class = "bg-red-900 text-red-200";
                                    } elseif ($notification['type'] === 'incident_update') {
                                        $type_class = "bg-green-900 text-green-200";
                                    } elseif ($notification['type'] === 'resource_update') {
                                        $type_class = "bg-yellow-900 text-yellow-200";
                                    }
                                    echo $type_class;
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-400 text-sm mt-1"><?php echo htmlspecialchars(mb_substr($notification['message'], 0, 60)) . (mb_strlen($notification['message']) > 60 ? '...' : ''); ?></p>
                            <div class="flex justify-between text-gray-500 text-xs mt-2">
                                <span><?php echo date('M d, Y H:i', strtotime($notification['sent_date'])); ?></span>
                                <span><?php echo $notification['recipient_count']; ?> recipients</span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-400 text-center">No recent notifications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 