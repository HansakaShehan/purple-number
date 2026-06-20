// History Screen - Show all past games
class HistoryScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('history-back-btn').addEventListener('click', () => {
            window.router.goToLobby();
        });

        // Screen lifecycle
        window.addEventListener('screen-changed', (e) => {
            if (e.detail.screen === 'history') {
                this.onScreenEnter();
            }
        });
    }

    async onScreenEnter() {
        await this.loadGameHistory();
    }

    async loadGameHistory() {
        try {
            const result = await this.requestManager.postJSON('api/game/user-games.php', {});
            
            if (result.success && result.games && result.games.length > 0) {
                this.displayHistory(result.games);
            } else {
                this.showEmpty();
            }
        } catch (e) {
            console.error('[History] Failed to load game history:', e);
            this.showEmpty();
        }
    }

    showEmpty() {
        const emptyDiv = document.getElementById('history-empty');
        const containerDiv = document.getElementById('history-container');
        emptyDiv.style.display = 'block';
        containerDiv.style.display = 'none';
    }

    displayHistory(games) {
        const emptyDiv = document.getElementById('history-empty');
        const containerDiv = document.getElementById('history-container');
        emptyDiv.style.display = 'none';
        const container = document.getElementById('history-container');
        container.style.display = 'block';
        
        const tbody = document.getElementById('history-body');
        tbody.innerHTML = '';

        games.forEach((game, index) => {
            const row = document.createElement('tr');
            row.style.display = 'table-row';
            
            // Format date
            const date = new Date(game.date);
            const dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // Result styling
            let resultColor = 'result-neutral';
            if (game.result === 'Won') {
                resultColor = 'result-correct';
            } else if (game.result === 'Lost') {
                resultColor = 'result-wrong';
            }
            
            const userScore = `${game.user_correct}/${game.user_correct + game.user_incorrect}`;
            const oppScore = `${game.opponent_correct}/${game.opponent_correct + game.opponent_incorrect}`;
            
            row.innerHTML = `
                <td style="display: table-cell;">${dateStr}</td>
                <td style="display: table-cell;">${game.players}</td>
                <td style="display: table-cell;">${userScore}</td>
                <td style="display: table-cell;">${oppScore}</td>
                <td class="${resultColor}" style="display: table-cell;">${game.result}</td>
                <td style="display: table-cell;"><button class="btn small secondary view-details-btn" data-room-code="${game.room_code}">View Details</button></td>
            `;
            
            tbody.appendChild(row);
            
            // Add click handler to view details button
            const viewBtn = row.querySelector('.view-details-btn');
            viewBtn.addEventListener('click', () => {
                const roomCode = viewBtn.getAttribute('data-room-code');
                localStorage.setItem('lastGameCode', roomCode);
                window.router.goToResults();
            });
        });
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.historyScreen) {
        window.historyScreen = new HistoryScreen();
    }
});
