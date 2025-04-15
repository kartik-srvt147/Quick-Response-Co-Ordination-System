document.addEventListener('DOMContentLoaded', function() {
    // Handle login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('remember-me')?.checked;

            try {
                // Here you would typically make an API call to your backend
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
                window.location.href = 'index.html#dashboard';
            } catch (error) {
                console.error('Login failed:', error);
                alert('Login failed. Please try again.');
            }
        });
    }

    // Handle signup form submission
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Basic validation
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            try {
                // Here you would typically make an API call to your backend
                const user = {
                    firstName,
                    lastName,
                    email,
                    isLoggedIn: true,
                    registrationTime: new Date().toISOString()
                };

                localStorage.setItem('user', JSON.stringify(user));

                // Redirect to dashboard
                window.location.href = 'index.html#dashboard';
            } catch (error) {
                console.error('Signup failed:', error);
                alert('Signup failed. Please try again.');
            }
        });
    }
}); 