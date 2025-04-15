<?php
/**
 * Notification Helper Functions
 * This file contains functions for creating and managing notifications
 */

/**
 * Create a new notification for a user
 * 
 * @param int $user_id The ID of the user to create the notification for
 * @param string $type The type of notification (emergency, incident_update, resource_update, system)
 * @param string $title The title of the notification
 * @param string $message The message content of the notification
 * @param string $link Optional link to more details
 * @return bool True if notification was created successfully, false otherwise
 */
function createNotification($user_id, $type, $title, $message, $link = '') {
    global $conn;
    
    // Check if the link column exists in the notifications table
    $check_link_column = "SHOW COLUMNS FROM `notifications` LIKE 'link'";
    $link_result = $conn->query($check_link_column);
    $link_exists = ($link_result && $link_result->num_rows > 0);
    
    if ($link_exists) {
        // If link column exists, use it in the insert
        $sql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $type, $title, $message, $link);
    } else {
        // If link column doesn't exist, skip it
        $sql = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $type, $title, $message);
    }
    
    return $stmt->execute();
}

/**
 * Create a notification for all users or a specific role
 * 
 * @param string $type The type of notification (emergency, incident_update, resource_update, system)
 * @param string $title The title of the notification
 * @param string $message The message content of the notification
 * @param string $link Optional link to more details
 * @param string $role Optional role to filter users (admin, responder, reporter, null for all)
 * @return bool True if notifications were created successfully, false otherwise
 */
function createNotificationForAll($type, $title, $message, $link = '', $role = null) {
    global $conn;
    
    // Get all users or users with specific role
    if ($role !== null) {
        $sql = "SELECT id FROM users WHERE role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $role);
    } else {
        $sql = "SELECT id FROM users";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $success = true;
    
    while ($user = $result->fetch_assoc()) {
        $success = $success && createNotification($user['id'], $type, $title, $message, $link);
    }
    
    return $success;
}

/**
 * Create an emergency notification
 * 
 * @param int $incident_id The ID of the incident
 * @param string $title The incident title
 * @param string $severity The severity level (critical, high, medium, low)
 * @param string $location The location of the incident
 * @param string $role Optional role to filter users
 * @return bool Success status
 */
function createEmergencyNotification($incident_id, $title, $severity, $location, $role = null) {
    $type = 'emergency';
    $notif_title = "New " . ucfirst($severity) . " Emergency";
    $message = "A new {$severity} emergency '{$title}' has been reported at {$location}.";
    $link = "view_incident.php?id=" . $incident_id;
    
    return createNotificationForAll($type, $notif_title, $message, $link, $role);
}

/**
 * Create an incident update notification
 * 
 * @param int $incident_id The ID of the incident
 * @param string $title The incident title
 * @param string $status The new status (active, pending, resolved, etc.)
 * @param int $reporter_id The user who reported the incident
 * @return bool Success status
 */
function createIncidentUpdateNotification($incident_id, $title, $status, $reporter_id) {
    $type = 'incident_update';
    $notif_title = "Incident Status Update";
    $message = "The incident '{$title}' has been updated to {$status}.";
    $link = "view_incident.php?id=" . $incident_id;
    
    return createNotification($reporter_id, $type, $notif_title, $message, $link);
}

/**
 * Create a resource update notification for admin
 * 
 * @param int $resource_id The ID of the resource
 * @param string $resource_name The name of the resource
 * @param string $status The new status
 * @return bool Success status
 */
function createResourceNotification($resource_id, $resource_name, $status) {
    $type = 'resource_update';
    $notif_title = "Resource Status Update";
    $message = "The resource '{$resource_name}' is now {$status}.";
    $link = "manage_resources.php?id=" . $resource_id . "&edit=true";
    
    return createNotificationForAll($type, $notif_title, $message, $link, 'admin');
}

/**
 * Create a system notification
 * 
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $role Optional role to filter users
 * @return bool Success status
 */
function createSystemNotification($title, $message, $role = null) {
    $type = 'system';
    return createNotificationForAll($type, $title, $message, '', $role);
}
?> 

function getNotificationCount($user_id = null) {
    global $conn;
    
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return 0;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}
