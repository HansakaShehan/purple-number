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
            console.log('[History] Loading game history...');
            const result = await this.requestManager.postJSON('api/game/user-games.php', {});
            console.log('[History] API Response:', result);
            
            if (result.success && result.games && result.games.length > 0) {
                console.log('[History] Found ' + result.games.length + ' games, displaying...');
                this.displayHistory(result.games);
            } else {
                console.log('[History] No games found or request failed, showing empty state');
                this.showEmpty();
            }
        } catch (e) {
            console.error('[History] Failed to load game history:', e);
            this.showEmpty();
        }
    }

    showEmpty() {
        console.log('[History] Setting empty state - hiding container, showing empty message');
        const emptyDiv = document.getElementById('history-empty');
        const containerDiv = document.getElementById('history-container');
        console.log('[History] history-empty element:', emptyDiv);
        console.log('[History] history-container element:', containerDiv);
        emptyDiv.style.display = 'block';
        containerDiv.style.display = 'none';
        console.log('[History] Empty state applied');
    }

    displayHistory(games) {
        console.log('[History] ===== displayHistory START =====');
        console.log('[History] displayHistory called with', games.length, 'games');
        const emptyDiv = document.getElementById('history-empty');
        const containerDiv = document.getElementById('history-container');
        console.log('[History] history-empty element:', emptyDiv);
        console.log('[History] history-container element:', containerDiv);
        emptyDiv.style.display = 'none';
        console.log('[History] Set history-empty display to none');
        const container = document.getElementById('history-container');
        console.log('[History] About to set history-container display to block');
        container.style.display = 'block';
        console.log('[History] Set history-container display to block');
        
        const tbody = document.getElementById('history-body');
        console.log('[History] tbody element:', tbody);
        tbody.innerHTML = '';
        console.log('[History] Cleared tbody innerHTML');

        games.forEach((game, index) => {
            console.log('[History] Creating row for game', index, ':', game);
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
            
            console.log('[History] Appending row to tbody');
            tbody.appendChild(row);
            console.log('[History] Row appended. Current tbody innerHTML length:', tbody.innerHTML.length);
            console.log('[History] tbody children count:', tbody.children.length);
            
            // Add click handler to view details button
            const viewBtn = row.querySelector('.view-details-btn');
            viewBtn.addEventListener('click', () => {
                const roomCode = viewBtn.getAttribute('data-room-code');
                localStorage.setItem('lastGameCode', roomCode);
                window.router.goToResults();
            });
        });
        
        console.log('[History] Final tbody innerHTML:', tbody.innerHTML.substring(0, 300));
        console.log('[History] ===== displayHistory END =====' );
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.historyScreen) {
        window.historyScreen = new HistoryScreen();
    }
});
