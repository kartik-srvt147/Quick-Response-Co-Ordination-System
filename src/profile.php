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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
        $error_message = "Please fill all required fields";
    } else {
        // Get form data
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $address = $conn->real_escape_string($_POST['address']);
        
        // Check if email already exists (if changed)
        if ($email != $currentUser['email']) {
            $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("si", $email, $currentUser['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email already exists. Please use a different email.";
            }
        }
        
        // Handle password change
        $password_updated = false;
        if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            if ($_POST['new_password'] != $_POST['confirm_password']) {
                $error_message = "New password and confirmation do not match.";
            } else {
                // Check current password
                if (empty($_POST['current_password'])) {
                    $error_message = "Please enter your current password to change your password.";
                } else {
                    $current_password = $_POST['current_password'];
                    
                    // Verify current password
                    $password_check = "SELECT password FROM users WHERE id = ?";
                    $stmt = $conn->prepare($password_check);
                    $stmt->bind_param("i", $currentUser['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                    
                    if (password_verify($current_password, $user_data['password'])) {
                        // Hash the new password
                        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $password_updated = true;
                    } else {
                        $error_message = "Current password is incorrect.";
                    }
                }
            }
        }
        
        // Update user profile if no errors
        if (empty($error_message)) {
            if ($password_updated) {
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $address, $new_password_hash, $currentUser['id']);
            } else {
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $currentUser['id']);
            }
            
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully.";
                
                // Update session data
                $_SESSION['user_data']['first_name'] = $first_name;
                $_SESSION['user_data']['last_name'] = $last_name;
                $_SESSION['user_data']['email'] = $email;
                $_SESSION['user_data']['phone'] = $phone;
                $_SESSION['user_data']['address'] = $address;
                
                // Refresh currentUser variable
                $currentUser = getCurrentUser();
            } else {
                $error_message = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Set page title
$page_title = "Edit Profile - QRCS";
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
            <a href="user_dashboard.php" class="text-blue-500 hover:underline">
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
            <!-- Profile Form -->
            <div class="md:col-span-2">
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-6">Edit Profile</h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-gray-300 mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                            </div>
                            
                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-gray-300 mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-gray-300 mb-2">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-gray-300 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($currentUser['phone']) ? htmlspecialchars($currentUser['phone']) : ''; ?>" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-gray-300 mb-2">Address</label>
                            <textarea id="address" name="address" rows="3" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500"><?php echo isset($currentUser['address']) ? htmlspecialchars($currentUser['address']) : ''; ?></textarea>
                        </div>
                        
                        <hr class="border-gray-700 my-6">
                        
                        <h3 class="text-lg font-medium text-white mb-4">Change Password</h3>
                        <p class="text-gray-400 text-sm mb-4">Leave blank if you don't want to change your password</p>
                        
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-gray-300 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- New Password -->
                            <div>
                                <label for="new_password" class="block text-gray-300 mb-2">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                            </div>
                            
                            <!-- Confirm Password -->
                            <div>
                                <label for="confirm_password" class="block text-gray-300 mb-2">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <div class="flex items-center justify-center mb-6">
                        <div class="p-3 rounded-full bg-blue-500 bg-opacity-20">
                            <i class="fas fa-user text-blue-500 text-4xl"></i>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-white text-center mb-4"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h3>
                    <p class="text-gray-400 text-center mb-6"><?php echo htmlspecialchars($currentUser['role']); ?></p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-gray-500 w-8"></i>
                            <span class="text-gray-300"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone text-gray-500 w-8"></i>
                            <span class="text-gray-300"><?php echo isset($currentUser['phone']) && !empty($currentUser['phone']) ? htmlspecialchars($currentUser['phone']) : 'Not provided'; ?></span>
                        </div>
                        <?php if (isset($currentUser['created_at'])): ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-gray-500 w-8"></i>
                            <span class="text-gray-300">Member since <?php echo date('M d, Y', strtotime($currentUser['created_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-900 bg-opacity-20 rounded-lg border border-blue-800">
                        <p class="text-blue-300 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            Keeping your profile information up to date helps emergency responders contact you when needed.
                        </p>
                    </div>
                </div>
                
                <!-- Account Settings -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mt-6">
                    <h3 class="text-lg font-bold text-white mb-4">Account Settings</h3>
                    
                    <div class="space-y-4">
                        <a href="notifications.php" class="flex items-center justify-between text-gray-300 hover:text-blue-400 transition-colors">
                            <span><i class="fas fa-bell mr-2"></i> Notification Settings</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <hr class="border-gray-700">
                        <a href="user_dashboard.php" class="flex items-center justify-between text-gray-300 hover:text-blue-400 transition-colors">
                            <span><i class="fas fa-tachometer-alt mr-2"></i> Return to Dashboard</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <hr class="border-gray-700">
                        <a href="includes/auth.php?logout=1" class="flex items-center text-red-400 hover:text-red-300 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(event) {
                // Check if new password is being set
                if (newPasswordInput.value || confirmPasswordInput.value) {
                    // Check if passwords match
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        event.preventDefault();
                        alert('New password and confirmation do not match.');
                    }
                    
                    // Check if current password is provided
                    const currentPasswordInput = document.getElementById('current_password');
                    if (!currentPasswordInput.value) {
                        event.preventDefault();
                        alert('Please enter your current password to change your password.');
                    }
                }
            });
        });
    </script>
</body>
</html> 