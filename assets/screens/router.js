// SPA Router - manages screen transitions
class Router {
    constructor() {
        this.currentScreen = 'login';
        this.screens = {
            'login': document.getElementById('login-screen'),
            'lobby': document.getElementById('lobby-screen'),
            'game': document.getElementById('game-screen'),
            'results': document.getElementById('results-screen'),
            'leaderboard': document.getElementById('leaderboard-screen')
        };
        this.init();
    }

    init() {
        // Check if user is already logged in
        this.checkAuthStatus();
    }

    async checkAuthStatus() {
        try {
            const response = await fetch('api/auth/status.php');
            const data = await response.json();

            if (data.logged_in) {
                // User is logged in, go to lobby
                window.currentUser = data.user;
                if (window.updateTopBar) {
                    window.updateTopBar();
                }
                this.goTo('lobby');
            } else {
                // User not logged in, stay at login
                this.goTo('login');
            }
        } catch (e) {
            console.error('Auth check failed:', e);
            this.goTo('login');
        }
    }

    goTo(screenName) {
        if (!this.screens[screenName]) {
            console.error('Screen not found:', screenName);
            return;
        }

        // Hide all screens
        Object.values(this.screens).forEach(screen => {
            screen.classList.remove('active');
        });

        // Show target screen
        this.screens[screenName].classList.add('active');
        this.currentScreen = screenName;

        // Trigger screen lifecycle
        window.dispatchEvent(new CustomEvent('screen-changed', { detail: { screen: screenName } }));
    }

    goToLogin() {
        window.currentUser = null;
        const badge = document.getElementById('top-user-display');
        if (badge) {
            badge.textContent = '';
        }
        this.goTo('login');
    }

    goToLobby() {
        this.goTo('lobby');
    }

    goToGame() {
        this.goTo('game');
    }

    goToResults() {
        this.goTo('results');
    }

    goToLeaderboard() {
        this.goTo('leaderboard');
    }
}

// Create global router instance
window.router = new Router();
