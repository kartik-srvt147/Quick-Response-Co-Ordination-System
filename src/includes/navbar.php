<?php
// Get current user if logged in
if (!isset($currentUser) && function_exists('getCurrentUser')) {
    $currentUser = getCurrentUser();
}
if (!isset($notificationCount) && function_exists('getNotificationCount')) {
    $notificationCount = getNotificationCount();
}
?>
<!-- Navigation Bar -->
<nav class="bg-gray-900 shadow-lg border-b border-gray-800 fixed w-full top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex items-center">
                    <i class="fas fa-heartbeat text-red-500 text-2xl mr-2"></i>
                    <span class="text-xl font-semibold text-white">QRCS</span>
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="text-gray-300 hover:text-red-500 px-3 py-2 rounded-md transition-colors duration-300">Home</a>
                <?php if (isset($currentUser) && $currentUser): ?>
                <a href="dashboard.php" class="text-gray-300 hover:text-red-500 px-3 py-2 rounded-md transition-colors duration-300">Dashboard</a>
                <?php if (isset($currentUser['role']) && $currentUser['role'] === 'admin'): ?>
                <a href="manage_resources.php" class="text-gray-300 hover:text-red-500 px-3 py-2 rounded-md transition-colors duration-300">Resources</a>
                <?php endif; ?>
                <div class="relative" id="notificationContainer">
                    <a href="notifications.php" class="text-gray-300 hover:text-red-500 px-3 py-2 rounded-md">
                        <i class="fas fa-bell"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center" id="notificationCount"><?php echo isset($notificationCount) ? $notificationCount : '0'; ?></span>
                    </a>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-300 mr-2">Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                    <a href="includes/auth.php?logout=1" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-300">Logout</a>
                </div>
                <?php else: ?>
                <?php 
                $current_page = basename($_SERVER['PHP_SELF']);
                if ($current_page != 'login.php'): 
                ?>
                <button onclick="window.location.href='login.php'" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-300">Login</button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobileMenuBtn" class="text-gray-300 hover:text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-gray-900 border-t border-gray-800">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="index.php" class="text-gray-300 hover:text-red-500 block px-3 py-2 rounded-md">Home</a>
            <?php if (isset($currentUser) && $currentUser): ?>
            <a href="dashboard.php" class="text-gray-300 hover:text-red-500 block px-3 py-2 rounded-md">Dashboard</a>
            <?php if (isset($currentUser['role']) && $currentUser['role'] === 'admin'): ?>
            <a href="manage_resources.php" class="text-gray-300 hover:text-red-500 block px-3 py-2 rounded-md">Resources</a>
            <?php endif; ?>
            <a href="notifications.php" class="text-gray-300 hover:text-red-500 block px-3 py-2 rounded-md">Notifications</a>
            <div class="px-3 py-2">
                <span class="text-gray-300">Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                <a href="includes/auth.php?logout=1" class="block mt-2 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-300 text-center">Logout</a>
            </div>
            <?php else: ?>
            <?php 
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page != 'login.php'): 
            ?>
            <a href="login.php" class="block mt-2 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-300 text-center">Login</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</nav>
<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
    });
</script> 