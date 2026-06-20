// Lobby Screen - Room selection and creation
class LobbyScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.currentRoom = null;
        this.pollingInterval = null;
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('create-room-btn').addEventListener('click', () => this.createRoom());
        document.getElementById('copy-code-btn').addEventListener('click', () => this.copyRoomCode());
        document.getElementById('join-room-btn').addEventListener('click', () => this.joinRoom());
        document.getElementById('logout-btn').addEventListener('click', () => this.logout());
        document.getElementById('save-config-btn').addEventListener('click', () => this.saveConfig());
        document.getElementById('view-leaderboard-btn').addEventListener('click', () => window.router.goToLeaderboard());

        // Screen lifecycle
        window.addEventListener('screen-changed', (e) => {
            if (e.detail.screen === 'lobby') {
                this.onScreenEnter();
            } else {
                this.onScreenExit();
            }
        });
    }

    onScreenEnter() {
        this.updateUserDisplay();
        this.checkAdminStatus();
        this.clearForms();
    }

    onScreenExit() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }

    updateUserDisplay() {
        const user = window.currentUser;
        document.getElementById('user-display').textContent = user.username;
    }

    checkAdminStatus() {
        const adminPanel = document.getElementById('admin-panel');
        if (window.currentUser.is_admin) {
            adminPanel.classList.remove('hidden');
            this.loadAdminConfig();
        } else {
            adminPanel.classList.add('hidden');
        }
    }

    async loadAdminConfig() {
        try {
            const res = await fetch('api/admin/config.php');
            const result = await res.json();
            if (!res.ok) {
                throw result;
            }

            document.getElementById('rounds-count-input').value = result.config.rounds_count;
            this.renderGemCategoryConfig(result.config);
        } catch (e) {
            console.error('Failed to load admin config:', e);
        }
    }

    renderGemCategoryConfig(config) {
        const container = document.getElementById('gem-categories-config');
        if (!container) return;

        const categories = config.gem_categories || [];
        const disabled = new Set(config.disabled_gem_categories || []);

        container.innerHTML = '';

        categories.forEach(category => {
            const label = document.createElement('label');
            label.className = 'admin-category-option';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = category.name;
            checkbox.checked = !disabled.has(category.name);

            const text = document.createElement('span');
            text.textContent = `${category.label} (${category.description})`;

            label.appendChild(checkbox);
            label.appendChild(text);
            container.appendChild(label);
        });
    }

    getDisabledGemCategoriesFromUI() {
        const disabled = [];
        document.querySelectorAll('#gem-categories-config input[type="checkbox"]').forEach(checkbox => {
            if (!checkbox.checked) {
                disabled.push(checkbox.value);
            }
        });
        return disabled;
    }

    async saveConfig() {
        const roundsCount = parseInt(document.getElementById('rounds-count-input').value);
        const disabledGemCategories = this.getDisabledGemCategoriesFromUI();
        const msgEl = document.getElementById('admin-message');

        try {
            await this.requestManager.postJSON('api/admin/config.php', {
                rounds_count: roundsCount,
                disabled_gem_categories: disabledGemCategories
            });
            msgEl.textContent = '✓ Settings saved!';
            msgEl.classList.add('success');
            setTimeout(() => {
                msgEl.textContent = '';
                msgEl.classList.remove('success');
            }, 3000);
        } catch (e) {
            msgEl.textContent = '✗ Failed to save settings';
        }
    }

    async createRoom() {
        try {
            const result = await this.requestManager.postJSON('api/rooms/create.php', {});

            if (result.success) {
                this.currentRoom = result.room;
                document.getElementById('room-code-display').textContent = result.room.code;
                document.getElementById('created-room').classList.remove('hidden');
                document.getElementById('create-room-btn').disabled = true;
                
                // Hide join room input while waiting for opponent
                document.querySelector('.lobby-section:nth-of-type(2)').classList.add('hidden');

                // Start polling for opponent
                this.startRoomPolling();
            }
        } catch (e) {
            alert('Failed to create room: ' + (e.error || 'Unknown error'));
        }
    }

    copyRoomCode() {
        const code = document.getElementById('room-code-display').textContent;
        navigator.clipboard.writeText(code).then(() => {
            alert('Room code copied!');
        });
    }

    async joinRoom() {
        const code = document.getElementById('join-code-input').value.toUpperCase();
        const errorEl = document.getElementById('join-error');
        errorEl.textContent = '';

        if (!code) {
            errorEl.textContent = 'Please enter a room code';
            return;
        }

        try {
            const result = await this.requestManager.postJSON('api/rooms/join.php', {
                room_code: code
            });

            if (result.success) {
                this.currentRoom = result.room;
                window.router.goToGame();
            }
        } catch (e) {
            errorEl.textContent = e.error || 'Failed to join room';
        }
    }

    startRoomPolling() {
        this.pollingInterval = setInterval(async () => {
            try {
                const result = await this.requestManager.postJSON('api/game/state.php', {
                    room_code: this.currentRoom.code
                });

                if (result.game.players[1] !== null) {
                    // Opponent joined!
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                    window.router.goToGame();
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }, 1000);
    }

    async logout() {
        try {
            await this.requestManager.postJSON('api/auth/logout.php', {});
            window.router.goToLogin();
        } catch (e) {
            alert('Logout failed');
        }
    }

    clearForms() {
        document.getElementById('join-code-input').value = '';
        document.getElementById('created-room').classList.add('hidden');
        document.getElementById('create-room-btn').disabled = false;
        // Show join room section again
        document.querySelector('.lobby-section:nth-of-type(2)').classList.remove('hidden');
        this.currentRoom = null;
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.lobbyScreen) {
        window.lobbyScreen = new LobbyScreen();
    }
});
