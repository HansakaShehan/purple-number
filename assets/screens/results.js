// Results Screen - Game over and winner display
class ResultsScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.resultsLoaded = false;
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('play-again-btn').addEventListener('click', () => {
            // Reset flag when leaving results screen
            this.resultsLoaded = false;
            window.lobbyScreen.clearForms();
            window.router.goToLobby();
        });

        document.getElementById('lobby-btn').addEventListener('click', () => {
            // Reset flag when leaving results screen
            this.resultsLoaded = false;
            window.lobbyScreen.clearForms();
            window.router.goToLobby();
        });

        // Screen lifecycle
        window.addEventListener('screen-changed', (e) => {
            if (e.detail.screen === 'results') {
                this.onScreenEnter();
            } else if (e.detail.screen !== 'results') {
                // Reset flag when leaving results screen
                this.resultsLoaded = false;
            }
        });
    }

    async onScreenEnter() {
        // Load final game state (only once per screen entry)
        let roomCode = window.gameScreen?.roomCode;
        
        // If no current game, try to load from localStorage
        if (!roomCode) {
            roomCode = localStorage.getItem('lastGameCode');
        }
        
        if (roomCode && !this.resultsLoaded) {
            await this.displayResults(roomCode);
        }
    }

    async displayResults(roomCode) {
        // Guard: only load once per screen entry to prevent loops
        if (this.resultsLoaded) {
            console.log('[Results] Already loaded, skipping duplicate load');
            return;
        }
        this.resultsLoaded = true;

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

            // Display results table with gems
            document.getElementById('result-p1-name').textContent = p1.username;
            document.getElementById('result-p1-correct').textContent = p1.correct;
            document.getElementById('result-p1-misses').textContent = p1.incorrect;

            document.getElementById('result-p2-name').textContent = p2.username;
            document.getElementById('result-p2-correct').textContent = p2.correct;
            document.getElementById('result-p2-misses').textContent = p2.incorrect;

            // Calculate gem statistics
            const p1Gems = this.calculatePlayerGems(p1.id, game.all_guesses || []);
            const p2Gems = this.calculatePlayerGems(p2.id, game.all_guesses || []);

            // Display gem totals
            document.getElementById('result-p1-gems').textContent = p1Gems.netGems;
            document.getElementById('result-p2-gems').textContent = p2Gems.netGems;
        } catch (e) {
            console.error('Failed to display results:', e);
        }
    }

    calculatePlayerGems(playerId, allGuesses) {
        let freeWins = 0;
        let paidWins = 0;
        let paidUsed = 0;

        // Filter guesses for this player only
        const playerGuesses = allGuesses.filter(guess => guess.player_id === playerId);

        for (const guess of playerGuesses) {
            const isCorrect = guess.is_correct === 1 || guess.is_correct === true;
            const category = guess.selected_category || '1-20';
            const categoryCost = guess.category_cost || 0;

            // Count paid category selections (they cost 10 gems)
            if (categoryCost > 0) {
                paidUsed++;
            }

            // Count wins
            if (isCorrect) {
                if (categoryCost === 0) {
                    // Free category: +10 gems
                    freeWins++;
                } else {
                    // Paid category: +20 gems
                    paidWins++;
                }
            }
        }

        // Calculate totals
        const freeTotal = freeWins * 10;
        const paidRewards = paidWins * 20;
        const paidCosts = paidUsed * 10;
        const netGems = freeTotal + paidRewards - paidCosts;

        return {
            freeWins,
            freeTotal,
            paidWins,
            paidRewards,
            paidUsed,
            paidCosts,
            netGems
        };
    }

    displayGemBreakdown(playerName, prefix, gems) {
        // Update player name
        document.getElementById(`${prefix}-breakdown-name`).textContent = playerName;

        // Update free category stats
        document.getElementById(`${prefix}-free-wins`).textContent = gems.freeWins;
        document.getElementById(`${prefix}-free-total`).textContent = gems.freeTotal;

        // Update paid category stats
        document.getElementById(`${prefix}-paid-wins`).textContent = gems.paidWins;
        document.getElementById(`${prefix}-paid-rewards`).textContent = gems.paidRewards;

        // Update paid costs
        document.getElementById(`${prefix}-paid-used`).textContent = gems.paidUsed;
        document.getElementById(`${prefix}-paid-costs`).textContent = gems.paidCosts;

        // Update net gems
        const netGemsEl = document.getElementById(`${prefix}-net-gems`);
        netGemsEl.textContent = gems.netGems;
        
        // Color code the net gems: green for positive, red for negative/zero
        if (gems.netGems > 0) {
            netGemsEl.style.color = 'var(--success)';
        } else if (gems.netGems < 0) {
            netGemsEl.style.color = 'var(--danger)';
        }
    }

    displayGameHistory(allGuesses, p1Name, p2Name, players) {
        const tbody = document.getElementById('game-history-body');
        if (!tbody) return;
        
        tbody.innerHTML = ''; // Clear existing rows

        // Create a map of player ID to player name
        const playerMap = {};
        players.forEach(p => {
            if (p) playerMap[p.id] = p.username;
        });

        // Calculate round number for each guess (2 per round)
        let round = 1;
        let guessesInRound = 0;

        allGuesses.forEach((guess, index) => {
            const row = document.createElement('tr');
            
            // Calculate round (every 2 guesses is a new round)
            if (guessesInRound === 2) {
                round++;
                guessesInRound = 0;
            }
            guessesInRound++;

            // Player name
            const playerName = playerMap[guess.player_id] || 'Unknown';
            
            // Result status
            const isCorrect = guess.is_correct === 1 || guess.is_correct === true;
            const resultText = isCorrect ? '✓ Correct' : '✗ Wrong';
            const resultClass = isCorrect ? 'result-correct' : 'result-wrong';
            
            // Category
            const category = guess.selected_category || '1-20';
            const categoryCost = guess.category_cost || 0;
            const categoryDisplay = categoryCost > 0 ? `${category} (Paid: -${categoryCost}💎)` : `${category} (Free)`;
            
            // Gem change calculation
            let gemChange = 0;
            let gemChangeClass = 'gem-neutral';
            
            if (isCorrect) {
                if (categoryCost === 0) {
                    gemChange = 10;
                    gemChangeClass = 'gem-positive';
                } else {
                    gemChange = 20 - categoryCost; // +20 for win, -10 for cost = +10 net
                    gemChangeClass = 'gem-positive';
                }
            } else {
                gemChange = -categoryCost; // Only lose gems if paid category was used
                if (categoryCost > 0) {
                    gemChangeClass = 'gem-negative';
                }
            }
            
            const gemChangeText = gemChange > 0 ? `+${gemChange}💎` : (gemChange < 0 ? `${gemChange}💎` : '—');

            row.innerHTML = `
                <td>${round}</td>
                <td>${playerName}</td>
                <td>${guess.guessed_number}</td>
                <td>${guess.secret_number}</td>
                <td class="${resultClass}">${resultText}</td>
                <td>${categoryDisplay}</td>
                <td class="${gemChangeClass}">${gemChangeText}</td>
            `;

            tbody.appendChild(row);
        });
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.resultsScreen) {
        window.resultsScreen = new ResultsScreen();
    }
});
