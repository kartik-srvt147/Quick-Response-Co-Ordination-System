function checkAuthAndRedirect() {
    const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
    const publicPages = ['/', '/index.html', '/login.html', '/signup.html'];
    const currentPage = window.location.pathname;

    if (!user || !user.isLoggedIn) {
        if (!publicPages.includes(currentPage)) {
            window.location.href = 'login.html';
            return false;
        }
    }
    return true;
}

// Add this function to handle protected navigation
function checkAuthAndNavigate(path) {
    const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
    if (!user || !user.isLoggedIn) {
        window.location.href = 'login.html';
        return false;
    }
    window.location.href = path;
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication at page load
    if (!checkAuthAndRedirect()) return;

    // Mobile menu functionality
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');

    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // Page navigation
    const pages = document.querySelectorAll('.page-content');
    const navLinks = document.querySelectorAll('[data-page]');

    function showPage(pageId) {
        pages.forEach(page => {
            page.classList.add('hidden');
        });
        document.getElementById(`${pageId}Page`).classList.remove('hidden');
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const pageId = e.target.getAttribute('data-page');
            
            // Check if it's a direct link to another page
            if (pageId === 'dashboard') {
                // Check if user is logged in before redirecting to dashboard
                const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
                if (!user || !user.isLoggedIn) {
                    window.location.href = 'login.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            } else {
                showPage(pageId);
                mobileMenu.classList.add('hidden'); // Close mobile menu after navigation
            }
        });
    });

    // Initialize map
    if (document.getElementById('incidentMap')) {
        const map = L.map('incidentMap').setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Get user's location and center map
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    map.setView([latitude, longitude], 13);
                },
                (error) => {
                    console.error('Error getting location:', error);
                }
            );
        }
    }

    // Report Incident button
    const reportIncidentBtn = document.getElementById('reportIncidentBtn');
    if (reportIncidentBtn) {
        reportIncidentBtn.addEventListener('click', () => {
            const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
            if (!user || !user.isLoggedIn) {
                window.location.href = 'login.html';
            } else {
                window.location.href = 'report-emergency.html';
            }
        });
    }

    // Login button
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
            if (user && user.isLoggedIn) {
                handleLogout();
            } else {
                showPage('login');
            }
        });
    }

    // Notification system
    let notificationCount = 0;
    const notificationCountElement = document.getElementById('notificationCount');

    // Example: Update notifications every 30 seconds
    setInterval(() => {
        // This is a placeholder for real notification checking
        if (Math.random() > 0.7) { // 30% chance of new notification
            notificationCount++;
            notificationCountElement.textContent = notificationCount;
            notificationCountElement.classList.remove('hidden');
        }
    }, 30000);

    // Login/Signup functionality
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const showSignupBtn = document.getElementById('showSignupBtn');
    const showLoginBtn = document.getElementById('showLoginBtn');
    const loginFormElement = document.getElementById('loginFormElement');
    const signupFormElement = document.getElementById('signupFormElement');

    // Toggle between login and signup forms
    showSignupBtn?.addEventListener('click', () => {
        loginForm.classList.add('hidden');
        signupForm.classList.remove('hidden');
    });

    showLoginBtn?.addEventListener('click', () => {
        signupForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
    });

    // Handle login form submission
    loginFormElement?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        const rememberMe = document.getElementById('remember-me').checked;

        try {
            // Here you would typically make an API call to your backend
            // For now, we'll just store in localStorage
            const user = {
                email,
                isLoggedIn: true,
                loginTime: new Date().toISOString()
            };

            if (rememberMe) {
                localStorage.setItem('user', JSON.stringify(user));
            } else {
                sessionStorage.setItem('user', JSON.stringify(user));
            }

            // Redirect to dashboard
            showPage('dashboard');
            
            // Update UI to show logged in state
            updateLoginState(true);
        } catch (error) {
            console.error('Login failed:', error);
            // Here you would typically show an error message to the user
        }
    });

    // Handle signup form submission
    signupFormElement?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('signupEmail').value;
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Basic validation
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }

        try {
            // Here you would typically make an API call to your backend
            // For now, we'll just store in localStorage
            const user = {
                firstName,
                lastName,
                email,
                isLoggedIn: true,
                registrationTime: new Date().toISOString()
            };

            localStorage.setItem('user', JSON.stringify(user));

            // Redirect to dashboard
            showPage('dashboard');
            
            // Update UI to show logged in state
            updateLoginState(true);
        } catch (error) {
            console.error('Signup failed:', error);
            // Here you would typically show an error message to the user
        }
    });

    // Function to update UI based on login state
    function updateLoginState(isLoggedIn) {
        const loginBtn = document.getElementById('loginBtn');
        if (isLoggedIn) {
            loginBtn.textContent = 'Logout';
            loginBtn.addEventListener('click', handleLogout);
        } else {
            loginBtn.textContent = 'Login';
            loginBtn.removeEventListener('click', handleLogout);
        }
    }

    // Handle logout
    function handleLogout() {
        localStorage.removeItem('user');
        sessionStorage.removeItem('user');
        updateLoginState(false);
        showPage('home');
    }

    // Check initial login state
    function checkLoginState() {
        const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
        if (user && user.isLoggedIn) {
            updateLoginState(true);
        }
    }

    // Call checkLoginState when the page loads
    checkLoginState();
}); 