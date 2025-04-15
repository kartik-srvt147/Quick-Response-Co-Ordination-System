<?php
// Notifications Database Setup Script
// This script creates the notifications table for the QRCS system

// Include config to get database connection
require_once 'includes/config.php';

// Output setup beginning
echo "Starting notifications system setup...\n";

// Check if notifications table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0;

// Check for structure issues if table exists
$structure_issues = false;
if ($table_exists) {
    // Check if link column exists
    $check_link = $conn->query("SHOW COLUMNS FROM `notifications` LIKE 'link'");
    if ($check_link->num_rows == 0) {
        $structure_issues = true;
        echo "WARNING: The notifications table exists but is missing the 'link' column.\n";
    }
    
    // Check other necessary columns
    $required_columns = ['id', 'user_id', 'type', 'title', 'message', 'is_read', 'created_at'];
    foreach ($required_columns as $column) {
        $check_column = $conn->query("SHOW COLUMNS FROM `notifications` LIKE '$column'");
        if ($check_column->num_rows == 0) {
            $structure_issues = true;
            echo "WARNING: The notifications table is missing the '$column' column.\n";
        }
    }
}

// If there are structure issues, drop and recreate the table
if ($structure_issues) {
    echo "Would you like to drop and recreate the notifications table to fix structure issues? (y/n): ";
    $line = trim(fgets(STDIN));
    if (strtolower($line) === 'y') {
        echo "Dropping notifications table...\n";
        
        // Drop the existing table
        if ($conn->query("DROP TABLE IF EXISTS `notifications`")) {
            echo "Notifications table dropped successfully.\n";
            $table_exists = false;
        } else {
            echo "Error dropping notifications table: " . $conn->error . "\n";
            exit;
        }
    } else {
        echo "Continuing without dropping the table. Some functions may not work correctly.\n";
    }
}

// Create the notifications table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($create_table_sql) === TRUE) {
    echo "Notifications table created successfully or already exists.\n";
} else {
    echo "Error creating notifications table: " . $conn->error . "\n";
    exit;
}

// Check if columns exist and add any missing ones
$expected_columns = [
    'id' => 'int(11) NOT NULL AUTO_INCREMENT',
    'user_id' => 'int(11) NOT NULL',
    'type' => 'varchar(50) NOT NULL DEFAULT \'system\'',
    'title' => 'varchar(255) NOT NULL',
    'message' => 'text NOT NULL',
    'link' => 'varchar(255) DEFAULT NULL',
    'is_read' => 'tinyint(1) NOT NULL DEFAULT 0',
    'created_at' => 'timestamp NOT NULL DEFAULT current_timestamp()'
];

foreach ($expected_columns as $column => $definition) {
    $check_column = "SHOW COLUMNS FROM `notifications` LIKE '$column'";
    $column_result = $conn->query($check_column);
    if ($column_result->num_rows == 0) {
        echo "Adding missing '$column' column to notifications table...\n";
        $add_column = "ALTER TABLE `notifications` ADD COLUMN `$column` $definition";
        if ($conn->query($add_column) === TRUE) {
            echo "$column column added successfully.\n";
        } else {
            echo "Error adding $column column: " . $conn->error . "\n";
        }
    }
}

// Check for primary key
$check_primary = "SHOW KEYS FROM `notifications` WHERE Key_name = 'PRIMARY'";
$primary_result = $conn->query($check_primary);
if ($primary_result->num_rows == 0) {
    echo "Adding missing PRIMARY KEY to notifications table...\n";
    $add_primary = "ALTER TABLE `notifications` ADD PRIMARY KEY (`id`)";
    if ($conn->query($add_primary) === TRUE) {
        echo "PRIMARY KEY added successfully.\n";
    } else {
        echo "Error adding PRIMARY KEY: " . $conn->error . "\n";
    }
}

// Check for foreign key
$check_fk_index = "SHOW INDEXES FROM `notifications` WHERE Key_name = 'user_id'";
$fk_index_result = $conn->query($check_fk_index);
if ($fk_index_result->num_rows == 0) {
    echo "Adding missing index on user_id column...\n";
    $add_index = "ALTER TABLE `notifications` ADD INDEX (`user_id`)";
    if ($conn->query($add_index) === TRUE) {
        echo "Index on user_id added successfully.\n";
    } else {
        echo "Error adding index on user_id: " . $conn->error . "\n";
    }
}

// Check for foreign key constraint
$check_fk = "SELECT * FROM information_schema.TABLE_CONSTRAINTS 
             WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
             AND TABLE_NAME = 'notifications' 
             AND CONSTRAINT_NAME = 'notifications_ibfk_1'";
$fk_result = $conn->query($check_fk);
if ($fk_result->num_rows == 0) {
    echo "Adding missing foreign key constraint to notifications table...\n";
    $add_fk = "ALTER TABLE `notifications` 
               ADD CONSTRAINT `notifications_ibfk_1` 
               FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE";
    if ($conn->query($add_fk) === TRUE) {
        echo "Foreign key constraint added successfully.\n";
    } else {
        echo "Error adding foreign key constraint: " . $conn->error . "\n";
    }
}

// Insert sample notifications if they don't exist
$check_sql = "SELECT COUNT(*) as count FROM notifications";
$result = $conn->query($check_sql);
$row = $result->fetch_assoc();
$notification_count = $row['count'];

if ($notification_count == 0) {
    echo "Adding sample notifications...\n";
    
    $sample_data_sql = "INSERT INTO `notifications` 
      (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) 
    VALUES
      (1, 'system', 'Welcome to QRCS', 'Welcome to the Quick Response Coordination System. This platform helps coordinate emergency responses efficiently.', NULL, 0, NOW() - INTERVAL 1 DAY),
      (1, 'emergency', 'New Critical Emergency', 'A new critical emergency has been reported at Downtown. Emergency services are being dispatched.', 'view_incident.php?id=1', 0, NOW() - INTERVAL 12 HOUR),
      (1, 'incident_update', 'Incident Status Update', 'The reported fire incident at Main Street has been updated to active.', 'view_incident.php?id=2', 0, NOW() - INTERVAL 6 HOUR),
      (1, 'resource_update', 'Resource Status Update', 'Ambulance #3 is now available for deployment.', 'manage_resources.php?id=3&edit=true', 1, NOW() - INTERVAL 1 HOUR),
      (2, 'system', 'Welcome to QRCS', 'Welcome to the Quick Response Coordination System. This platform helps coordinate emergency responses efficiently.', NULL, 0, NOW() - INTERVAL 2 DAY),
      (2, 'emergency', 'New High Emergency', 'A new high priority emergency has been reported at Westside. Emergency services are responding.', 'view_incident.php?id=3', 1, NOW() - INTERVAL 1 DAY);";
    
    if ($conn->query($sample_data_sql) === TRUE) {
        echo "Sample notifications added successfully.\n";
    } else {
        echo "Error adding sample notifications: " . $conn->error . "\n";
    }
} else {
    echo "Notifications table already contains data. Skipping sample data insertion.\n";
}

// Create notification count function if it doesn't exist
$check_function_sql = "SHOW FUNCTION STATUS WHERE Db = DATABASE() AND Name = 'get_notification_count'";
$function_result = $conn->query($check_function_sql);

if ($function_result && $function_result->num_rows == 0) {
    echo "Creating notification count function...\n";
    
    // Create function without using DELIMITER commands
    $create_function_sql = "CREATE FUNCTION get_notification_count(p_user_id INT) 
    RETURNS INT
    DETERMINISTIC
    BEGIN
        DECLARE count_val INT;
        SELECT COUNT(*) INTO count_val FROM notifications WHERE user_id = p_user_id AND is_read = 0;
        RETURN count_val;
    END";
    
    if ($conn->query($create_function_sql)) {
        echo "Notification count function created successfully.\n";
    } else {
        echo "Error creating notification count function: " . $conn->error . "\n";
        echo "Using PHP function alternative instead.\n";
    }
} else {
    echo "Notification count function already exists.\n";
}

// Create a PHP function equivalent for getting notification count
// This is useful if the MySQL function doesn't work in some environments
function createNotificationCountFunction() {
    $function_code = "
function getNotificationCount(\$user_id = null) {
    global \$conn;
    
    if (!\$user_id && isset(\$_SESSION['user_id'])) {
        \$user_id = \$_SESSION['user_id'];
    }
    
    if (!\$user_id) {
        return 0;
    }
    
    \$stmt = \$conn->prepare(\"SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0\");
    \$stmt->bind_param(\"i\", \$user_id);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    
    if (\$result && \$row = \$result->fetch_assoc()) {
        return \$row['count'];
    }
    
    return 0;
}
";
    
    // Check if notification_helper.php exists and create it if not
    if (!file_exists('includes/notification_helper.php')) {
        file_put_contents('includes/notification_helper.php', "<?php\n" . $function_code);
        echo "Created notification_helper.php with getNotificationCount function.\n";
    } else {
        // Check if the function already exists in the file
        $helper_content = file_get_contents('includes/notification_helper.php');
        if (strpos($helper_content, 'function getNotificationCount') === false) {
            file_put_contents('includes/notification_helper.php', $helper_content . "\n" . $function_code);
            echo "Added getNotificationCount function to existing notification_helper.php.\n";
        } else {
            echo "getNotificationCount function already exists in notification_helper.php.\n";
        }
    }
}

// Create or update the notification_helper.php file
createNotificationCountFunction();

// Create function to generate a notification
function createNotificationFunction() {
    $function_code = "
function createNotification(\$user_id, \$title, \$message, \$type = 'system', \$link = null) {
    global \$conn;
    
    \$stmt = \$conn->prepare(\"INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)\");
    \$stmt->bind_param(\"issss\", \$user_id, \$type, \$title, \$message, \$link);
    
    if (\$stmt->execute()) {
        return \$conn->insert_id;
    }
    
    return false;
}

function createSystemNotification(\$title, \$message, \$target_role = null, \$link = null) {
    global \$conn;
    
    // If target_role is set, send to all users with that role
    if (\$target_role) {
        \$user_query = \"SELECT id FROM users WHERE role = ?\";
        \$stmt = \$conn->prepare(\$user_query);
        \$stmt->bind_param(\"s\", \$target_role);
    } else {
        // Send to all users
        \$user_query = \"SELECT id FROM users\";
        \$stmt = \$conn->prepare(\$user_query);
    }
    
    \$stmt->execute();
    \$result = \$stmt->get_result();
    
    \$success = true;
    
    while (\$user = \$result->fetch_assoc()) {
        \$notification_success = createNotification(\$user['id'], \$title, \$message, 'system', \$link);
        if (!\$notification_success) {
            \$success = false;
        }
    }
    
    return \$success;
}

function createResourceNotification(\$resource_id, \$title, \$message) {
    global \$conn;
    
    // Get resource details for the notification
    \$resource_query = \"SELECT name, type FROM resources WHERE id = ?\";
    \$stmt = \$conn->prepare(\$resource_query);
    \$stmt->bind_param(\"i\", \$resource_id);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    
    if (\$resource = \$result->fetch_assoc()) {
        \$resource_name = \$resource['name'];
        \$resource_type = \$resource['type'];
        
        // Find all responders and admins to notify
        \$user_query = \"SELECT id FROM users WHERE role IN ('admin', 'responder')\";
        \$user_result = \$conn->query(\$user_query);
        
        \$link = \"manage_resources.php?id={\$resource_id}&edit=true\";
        
        while (\$user = \$user_result->fetch_assoc()) {
            createNotification(\$user['id'], \$title, \$message, 'resource_update', \$link);
        }
        
        return true;
    }
    
    return false;
}

function createIncidentNotification(\$incident_id, \$title, \$message, \$type = 'incident_update') {
    global \$conn;
    
    // Get incident reporter to notify them
    \$incident_query = \"SELECT reported_by, severity FROM incidents WHERE id = ?\";
    \$stmt = \$conn->prepare(\$incident_query);
    \$stmt->bind_param(\"i\", \$incident_id);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    
    if (\$incident = \$result->fetch_assoc()) {
        \$reporter_id = \$incident['reported_by'];
        \$severity = \$incident['severity'];
        
        // Create link to view the incident
        \$link = \"view_incident.php?id={\$incident_id}\";
        
        // Always notify the reporter
        createNotification(\$reporter_id, \$title, \$message, \$type, \$link);
        
        // For critical and high severity, also notify all responders and admins
        if (\$severity == 'critical' || \$severity == 'high') {
            \$user_query = \"SELECT id FROM users WHERE role IN ('admin', 'responder')\";
            \$user_result = \$conn->query(\$user_query);
            
            while (\$user = \$user_result->fetch_assoc()) {
                // Skip the reporter if they've already been notified
                if (\$user['id'] != \$reporter_id) {
                    createNotification(\$user['id'], \$title, \$message, \$type, \$link);
                }
            }
        }
        
        return true;
    }
    
    return false;
}

function markNotificationAsRead(\$notification_id, \$user_id = null) {
    global \$conn;
    
    if (!\$user_id && isset(\$_SESSION['user_id'])) {
        \$user_id = \$_SESSION['user_id'];
    }
    
    if (!\$user_id) {
        return false;
    }
    
    \$stmt = \$conn->prepare(\"UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?\");
    \$stmt->bind_param(\"ii\", \$notification_id, \$user_id);
    
    return \$stmt->execute();
}

function markAllNotificationsAsRead(\$user_id = null) {
    global \$conn;
    
    if (!\$user_id && isset(\$_SESSION['user_id'])) {
        \$user_id = \$_SESSION['user_id'];
    }
    
    if (!\$user_id) {
        return false;
    }
    
    \$stmt = \$conn->prepare(\"UPDATE notifications SET is_read = 1 WHERE user_id = ?\");
    \$stmt->bind_param(\"i\", \$user_id);
    
    return \$stmt->execute();
}

function deleteNotification(\$notification_id, \$user_id = null) {
    global \$conn;
    
    if (!\$user_id && isset(\$_SESSION['user_id'])) {
        \$user_id = \$_SESSION['user_id'];
    }
    
    if (!\$user_id) {
        return false;
    }
    
    \$stmt = \$conn->prepare(\"DELETE FROM notifications WHERE id = ? AND user_id = ?\");
    \$stmt->bind_param(\"ii\", \$notification_id, \$user_id);
    
    return \$stmt->execute();
}

function getUserNotifications(\$user_id = null, \$limit = 10, \$offset = 0, \$unread_only = false) {
    global \$conn;
    
    if (!\$user_id && isset(\$_SESSION['user_id'])) {
        \$user_id = \$_SESSION['user_id'];
    }
    
    if (!\$user_id) {
        return [];
    }
    
    \$sql = \"SELECT * FROM notifications WHERE user_id = ?\";
    
    if (\$unread_only) {
        \$sql .= \" AND is_read = 0\";
    }
    
    \$sql .= \" ORDER BY created_at DESC LIMIT ? OFFSET ?\";
    
    \$stmt = \$conn->prepare(\$sql);
    \$stmt->bind_param(\"iii\", \$user_id, \$limit, \$offset);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    
    \$notifications = [];
    while (\$row = \$result->fetch_assoc()) {
        \$notifications[] = \$row;
    }
    
    return \$notifications;
}";
    
    // Check if notification_helper.php exists and create it if not
    if (!file_exists('includes/notification_helper.php')) {
        file_put_contents('includes/notification_helper.php', "<?php\n" . $function_code);
        echo "Created notification_helper.php with notification helper functions.\n";
    } else {
        // Check if the functions already exist in the file
        $helper_content = file_get_contents('includes/notification_helper.php');
        
        // If the file doesn't contain createNotification function, append all our functions
        if (strpos($helper_content, 'function createNotification') === false) {
            file_put_contents('includes/notification_helper.php', $helper_content . "\n" . $function_code);
            echo "Added notification helper functions to existing notification_helper.php.\n";
        } else {
            echo "Notification helper functions already exist in notification_helper.php.\n";
        }
    }
}

// Create or update the notification_helper.php file with helper functions
createNotificationFunction();

echo "Notifications system setup completed successfully!\n";
?> 