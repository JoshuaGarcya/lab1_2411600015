document.addEventListener('DOMContentLoaded', function() {

    const loginBtn = document.getElementById('loginBtn');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const feedbackDiv = document.getElementById('loginFeedback');
    const rememberMe = document.getElementById('rememberMe');

    // Auto-fill if Remember Me was used before
    if (localStorage.getItem('rememberUser') && localStorage.getItem('rememberPass')) {
        usernameInput.value = localStorage.getItem('rememberUser');
        passwordInput.value = localStorage.getItem('rememberPass');
        rememberMe.checked = true;
    }

    // Redirect if already logged in
    if (localStorage.getItem('isLoggedIn') === 'true') {
        window.location.href = 'dashboard.html';
    }

    loginBtn.addEventListener('click', function() {
        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        feedbackDiv.innerHTML = '';

        if (username === '' || password === '') {
            showFeedback('Please enter both username and password.', 'danger');
            return;
        }

        const validUsername = 'admin';
        const validPassword = 'password123';

        if (username === validUsername && password === validPassword) {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('user', username);

            // Save credentials if Remember Me is checked
            if (rememberMe.checked) {
                localStorage.setItem('rememberUser', username);
                localStorage.setItem('rememberPass', password);
            } else {
                localStorage.removeItem('rememberUser');
                localStorage.removeItem('rememberPass');
            }

            showFeedback('Login successful! Redirecting...', 'success');
            setTimeout(() => window.location.href = 'dashboard.html', 1000);
        } else {
            showFeedback('Invalid username or password. Please try again.', 'danger');
        }
    });

    function showFeedback(message, type) {
        const alertClass = type === 'danger' ? 'alert-danger' : 'alert-success';
        feedbackDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    usernameInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') loginBtn.click();
    });

    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') loginBtn.click();
    });
});
