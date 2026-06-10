// Results Screen - Game over and winner display
class ResultsScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('play-again-btn').addEventListener('click', () => {
            window.lobbyScreen.clearForms();
            window.router.goToLobby();
        });

        document.getElementById('lobby-btn').addEventListener('click', () => {
            window.lobbyScreen.clearForms();
            window.router.goToLobby();
        });

        // Screen lifecycle
        window.addEventListener('screen-changed', (e) => {
            if (e.detail.screen === 'results') {
                this.onScreenEnter();
            }
        });
    }

    async onScreenEnter() {
        // Load final game state
        const roomCode = window.gameScreen?.roomCode;
        if (roomCode) {
            await this.displayResults(roomCode);
        }
    }

    async displayResults(roomCode) {
        try {
            const result = await this.requestManager.postJSON('api/game/state.php', {
                room_code: roomCode
            });
            const game = result.game;
            const p1 = game.players[0];
            const p2 = game.players[1];

            // Determine winner
            const p1Total = p1.correct;
            const p2Total = p2.correct;
            let winnerText = '';

            if (p1Total > p2Total) {
                winnerText = `${p1.username} Wins! 🏆`;
            } else if (p2Total > p1Total) {
                winnerText = `${p2.username} Wins! 🏆`;
            } else {
                winnerText = "It's a Tie! 🤝";
            }

            document.getElementById('winner-text').textContent = winnerText;

            // Display results table
            document.getElementById('result-p1-name').textContent = p1.username;
            document.getElementById('result-p1-correct').textContent = p1.correct;
            document.getElementById('result-p1-misses').textContent = p1.incorrect;

            document.getElementById('result-p2-name').textContent = p2.username;
            document.getElementById('result-p2-correct').textContent = p2.correct;
            document.getElementById('result-p2-misses').textContent = p2.incorrect;
        } catch (e) {
            console.error('Failed to display results:', e);
        }
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.resultsScreen) {
        window.resultsScreen = new ResultsScreen();
    }
});
