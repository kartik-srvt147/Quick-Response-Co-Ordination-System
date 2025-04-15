<?php
// Database Configuration
$host = 'localhost';
$username = 'root'; // Change to your MySQL username
$password = ''; // Change to your MySQL password
$database = 'qrcs_db';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected to MySQL server successfully.<br>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($database);

// Create Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'responder', 'user') DEFAULT 'user',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create Incidents table
$sql = "CREATE TABLE IF NOT EXISTS incidents (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('reported', 'pending', 'active', 'responding', 'resolved', 'closed', 'rejected') DEFAULT 'reported',
    reported_by INT(11) NOT NULL,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    additional_data TEXT NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Incidents table created successfully.<br>";
} else {
    echo "Error creating incidents table: " . $conn->error . "<br>";
}

// Create Responders table
$sql = "CREATE TABLE IF NOT EXISTS responders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    incident_id INT(11) NOT NULL,
    status ENUM('assigned', 'en_route', 'on_scene', 'completed') DEFAULT 'assigned',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (incident_id) REFERENCES incidents(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Responders table created successfully.<br>";
} else {
    echo "Error creating responders table: " . $conn->error . "<br>";
}

// Create Notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('alert', 'update', 'info') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Notifications table created successfully.<br>";
} else {
    echo "Error creating notifications table: " . $conn->error . "<br>";
}

// Create Resources table
$sql = "CREATE TABLE IF NOT EXISTS resources (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('vehicle', 'equipment', 'personnel', 'facility', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('available', 'in_use', 'unavailable', 'maintenance') DEFAULT 'available',
    location VARCHAR(255) NULL,
    assigned_to INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES incidents(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Resources table created successfully.<br>";
} else {
    echo "Error creating resources table: " . $conn->error . "<br>";
}

// Add some sample data
// Create admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (first_name, last_name, email, password, role) 
        VALUES ('Admin', 'User', 'admin@qrcs.com', '$admin_password', 'admin')
        ON DUPLICATE KEY UPDATE id=id";

if ($conn->query($sql) === TRUE) {
    echo "Admin user created successfully.<br>";
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

echo "Database setup completed successfully!";

$conn->close();
?> 