-- Notifications Table Creation Script
-- This script creates the notifications table for the QRCS system

-- Create the notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample notifications
INSERT INTO `notifications` 
  (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) 
VALUES
  (1, 'system', 'Welcome to QRCS', 'Welcome to the Quick Response Coordination System. This platform helps coordinate emergency responses efficiently.', NULL, 0, NOW() - INTERVAL 1 DAY),
  (1, 'emergency', 'New Critical Emergency', 'A new critical emergency has been reported at Downtown. Emergency services are being dispatched.', 'view_incident.php?id=1', 0, NOW() - INTERVAL 12 HOUR),
  (1, 'incident_update', 'Incident Status Update', 'The reported fire incident at Main Street has been updated to active.', 'view_incident.php?id=2', 0, NOW() - INTERVAL 6 HOUR),
  (1, 'resource_update', 'Resource Status Update', 'Ambulance #3 is now available for deployment.', 'manage_resources.php?id=3&edit=true', 1, NOW() - INTERVAL 1 HOUR),
  (2, 'system', 'Welcome to QRCS', 'Welcome to the Quick Response Coordination System. This platform helps coordinate emergency responses efficiently.', NULL, 0, NOW() - INTERVAL 2 DAY),
  (2, 'emergency', 'New High Emergency', 'A new high priority emergency has been reported at Westside. Emergency services are responding.', 'view_incident.php?id=3', 1, NOW() - INTERVAL 1 DAY);

-- Ensure the notifications count function works correctly
DELIMITER //
CREATE FUNCTION IF NOT EXISTS get_notification_count(user_id INT) 
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE count INT;
    SELECT COUNT(*) INTO count FROM notifications WHERE user_id = user_id AND is_read = 0;
    RETURN count;
END//
DELIMITER ; 