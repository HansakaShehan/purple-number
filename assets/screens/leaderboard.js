// Leaderboard Screen - Display rankings
class LeaderboardScreen {
    constructor() {
        this.leaderboard = [];
        this.currentUserRank = null;
        this.setupEventListeners();
    }

    get requestManager() {
        return window.requestManager;
    }

    setupEventListeners() {
        // Back to lobby button
        const backBtn = document.getElementById('leaderboard-back-btn');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                window.router.goToLobby();
            });
        }

        // Screen lifecycle
        window.addEventListener('screen-changed', (e) => {
            if (e.detail.screen === 'leaderboard') {
                this.onScreenEnter();
            }
        });
    }

    async onScreenEnter() {
        await this.loadLeaderboard();
    }

    async loadLeaderboard() {
        try {
            const result = await this.requestManager.postJSON('api/game/leaderboard.php', {});
            
            if (result && result.success) {
                this.leaderboard = result.leaderboard || [];
                this.renderLeaderboard();
            } else if (result && result.error) {
                console.error('Failed to load leaderboard:', result.error);
                this.showError('Failed to load leaderboard: ' + result.error);
            } else {
                console.error('Unexpected response:', result);
                this.showError('Failed to load leaderboard: Invalid response');
            }
        } catch (e) {
            console.error('Error loading leaderboard:', e.message, e);
            this.showError('Error loading leaderboard: ' + e.message);
        }
    }

    renderLeaderboard() {
        const tbody = document.getElementById('leaderboard-body');
        tbody.innerHTML = '';

        if (this.leaderboard.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: var(--text-secondary);">No games played yet</td></tr>';
            return;
        }

        this.leaderboard.forEach((player, index) => {
            const isCurrentUser = player.id === parseInt(window.currentUser?.id || 0);
            const row = document.createElement('tr');
            row.className = isCurrentUser ? 'leaderboard-current-user' : '';
            
            let medalEmoji = '';
            if (player.rank === 1) medalEmoji = '🥇';
            else if (player.rank === 2) medalEmoji = '🥈';
            else if (player.rank === 3) medalEmoji = '🥉';

            row.innerHTML = `
                <td class="rank-cell">${medalEmoji ? medalEmoji + ' ' : ''}#${player.rank}</td>
                <td class="name-cell">${this.escapeHtml(player.username)}${isCurrentUser ? ' <span class="you-badge">YOU</span>' : ''}</td>
                <td class="stat-cell">${player.total_games || 0}</td>
                <td class="stat-cell"><strong>${player.wins || 0}</strong></td>
                <td class="stat-cell">${player.win_rate || 0}%</td>
                <td class="stat-cell">${player.correct_guesses || 0} / ${player.total_guesses || 0}</td>
                <td class="stat-cell">${player.accuracy || 0}%</td>
            `;
            tbody.appendChild(row);
        });
    }

    showError(message) {
        const tbody = document.getElementById('leaderboard-body');
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 20px; color: var(--danger);">${message}</td></tr>`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when document is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.leaderboardScreen = new LeaderboardScreen();
    });
} else {
    window.leaderboardScreen = new LeaderboardScreen();
}
