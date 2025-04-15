<?php
// Include config file
require_once 'config.php';

// Function to register a new user
function registerUser($firstName, $lastName, $email, $password) {
    global $conn;
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        // Set session variables and return success
        $userId = $conn->insert_id;
        $_SESSION['user_id'] = $userId;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = $email;
        
        // Update last login time
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $userId);
        $updateStmt->execute();
        
        return true;
    } else {
        return false;
    }
}

// Function to login a user
function loginUser($email, $password) {
    global $conn;
    
    // Prepare statement to get user with the given email
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            
            return true;
        }
    }
    
    return false;
}

// Function to logout a user
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    return true;
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        header("Location: ../index.php");
        exit;
    } else {
        // Store error in session instead of local variable
        $_SESSION['loginError'] = "Invalid email or password";
        header("Location: ../login.php");
        exit;
    }
}

// Process signup form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate password match
    if ($password !== $confirmPassword) {
        $_SESSION['signupError'] = "Passwords do not match";
        header("Location: ../signup.php");
        exit;
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['signupError'] = "Email already exists";
            header("Location: ../signup.php");
            exit;
        } else {
            if (registerUser($firstName, $lastName, $email, $password)) {
                header("Location: ../index.php");
                exit;
            } else {
                $_SESSION['signupError'] = "Registration failed. Please try again.";
                header("Location: ../signup.php");
                exit;
            }
        }
    }
}

// Process logout request
if (isset($_GET['logout'])) {
    logoutUser();
    header("Location: ../index.php");
    exit;
}
?> 