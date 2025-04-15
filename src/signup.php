<?php
// Include auth file
require_once 'includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Set page variables
$page_title = "Sign Up - QRCS";
$signupError = isset($_SESSION['signupError']) ? $_SESSION['signupError'] : null;

// Clear session errors after displaying them
unset($_SESSION['signupError']);
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
        .page-container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .content-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 0;
        }
        .form-container {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-black min-h-screen">
    <div class="page-container">
        <?php include 'includes/navbar.php'; ?>

        <div class="content-wrapper">
            <div class="form-container bg-gray-900 p-8 rounded-lg border border-gray-800 transform transition-all hover:shadow-2xl hover:-translate-y-1">
                <!-- Signup Form -->
                <div class="text-center">
                    <i class="fas fa-heartbeat text-red-500 text-4xl mb-4"></i>
                    <h2 class="text-3xl font-bold text-white">Create Account</h2>
                    <p class="mt-2 text-gray-400">Join QRCS today</p>
                </div>

                <?php if ($signupError): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-400 text-red-100 px-4 py-3 rounded relative mt-4">
                    <span class="block sm:inline"><?php echo htmlspecialchars($signupError); ?></span>
                </div>
                <?php endif; ?>

                <form action="includes/auth.php" method="POST" class="mt-8 space-y-6">
                    <input type="hidden" name="signup" value="1">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="firstName" class="sr-only">First Name</label>
                                <input id="firstName" name="firstName" type="text" required 
                                    class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-700 bg-gray-800 text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                                    placeholder="First Name">
                            </div>
                            <div>
                                <label for="lastName" class="sr-only">Last Name</label>
                                <input id="lastName" name="lastName" type="text" required 
                                    class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-700 bg-gray-800 text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                                    placeholder="Last Name">
                            </div>
                        </div>
                        <div>
                            <label for="email" class="sr-only">Email address</label>
                            <input id="email" name="email" type="email" required 
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-700 bg-gray-800 text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                                placeholder="Email address">
                        </div>
                        <div>
                            <label for="password" class="sr-only">Password</label>
                            <input id="password" name="password" type="password" required 
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-700 bg-gray-800 text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                                placeholder="Password">
                        </div>
                        <div>
                            <label for="confirmPassword" class="sr-only">Confirm Password</label>
                            <input id="confirmPassword" name="confirmPassword" type="password" required 
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-700 bg-gray-800 text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                                placeholder="Confirm Password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Create Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-6">
                    <p class="text-gray-400">
                        Already have an account? 
                        <a href="login.php" class="text-red-500 hover:text-red-400 font-medium">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 