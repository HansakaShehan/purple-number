// Auth Screen - Login and Registration
class AuthScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
        });

        // Login form
        document.getElementById('login-form').addEventListener('submit', (e) => this.handleLogin(e));

        // Register form
        document.getElementById('register-form').addEventListener('submit', (e) => this.handleRegister(e));
    }

    switchTab(tabName) {
        // Update active tab button
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        // Update active form
        document.querySelectorAll('.auth-form').forEach(form => {
            form.classList.toggle('active', form.id === tabName);
        });

        // Clear errors
        document.getElementById('login-error').textContent = '';
        document.getElementById('register-error').textContent = '';
    }

    async handleLogin(e) {
        e.preventDefault();
        const username = document.getElementById('login-username').value;
        const password = document.getElementById('login-password').value;
        const errorEl = document.getElementById('login-error');

        errorEl.textContent = '';

        try {
            const result = await this.requestManager.postJSON('api/auth/login.php', {
                username,
                password
            });

            if (result.success) {
                window.currentUser = result.user;
                window.router.goToLobby();
            }
        } catch (error) {
            errorEl.textContent = error.error || 'Login failed';
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const username = document.getElementById('register-username').value;
        const password = document.getElementById('register-password').value;
        const errorEl = document.getElementById('register-error');

        errorEl.textContent = '';

        try {
            const result = await this.requestManager.postJSON('api/auth/register.php', {
                username,
                password
            });

            if (result.success) {
                window.currentUser = result.user;
                window.router.goToLobby();
            }
        } catch (error) {
            errorEl.textContent = error.error || 'Registration failed';
        }
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.authScreen) {
        window.authScreen = new AuthScreen();
    }
});
