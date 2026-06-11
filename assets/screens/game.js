// Game Screen - Multiplayer game board
class GameScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.roomCode = null;
        this.gameState = null;
        this.selectedNumber = null;
        this.gameTimer = null;
        this.turnTimer = null;
        this.countdownTimer = null;
        this.isMyTurn = false;
        this.pollingInterval = null;
        this.audioManager = window.audioManager;
        this.gameStarted = false;
        this.currentTurnStarted = false;  // Prevent duplicate turn timers
        this.lastGuessDisplayed = 0;  // Track last guess to avoid duplicate displays
        this.historyRowCount = 0;  // Track history rows to avoid duplicates
        this.playerGems = {};  // Track gems for each player
        window.gameScreen = this;  // Store reference globally for gem tracking
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Number buttons
        document.querySelectorAll('.number-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectNumber(parseInt(e.target.dataset.number)));
        });

        // Submit button
        document.getElementById('submitBtn').addEventListener('click', () => this.submitGuess());

        // Audio toggle
        document.getElementById('audioToggleBtn').addEventListener('click', () => {
            this.audioManager.toggleMute();
            this.updateAudioButton();
        });

        // Leaderboard button
        const leaderboardBtn = document.getElementById('bottom-leaderboard-btn');
        if (leaderboardBtn) {
            leaderboardBtn.addEventListener('click', () => window.router.goToLeaderboard());
        }

        // Screen lifecycle
        window.addEventListener('screen-changed', (e) => {
            if (e.detail.screen === 'game') {
                this.onScreenEnter();
            } else {
                this.onScreenExit();
            }
        });

        // Keyboard
        document.addEventListener('keydown', (e) => {
            if (window.router.currentScreen !== 'game') return;
            
            const num = parseInt(e.key);
            if (num >= 1 && num <= 9) {
                this.selectNumber(num);
            } else if (e.key === '0') {
                this.selectNumber(10);
            } else if (e.key === 'Enter') {
                this.submitGuess();
            }
        });
    }

    async onScreenEnter() {
        // Show sound preference dialog if first time
        if (this.audioManager.needsSoundPreferenceDialog()) {
            this.showSoundPreferenceDialog();
        }

        // Get room code from lobby
        const roomCode = window.lobbyScreen?.currentRoom?.code;
        if (!roomCode) {
            console.error('No room code available');
            window.router.goToLobby();
            return;
        }

        this.roomCode = roomCode;
        this.selectedNumber = null;
        this.isMyTurn = false;
        this.gameStarted = false;
        this.playerGems = {};  // Reset gems

        // Show leaderboard button in bottom bar
        const leaderboardBtn = document.getElementById('bottom-leaderboard-btn');
        if (leaderboardBtn) {
            leaderboardBtn.style.display = 'inline-block';
        }

        // Load initial game state
        await this.loadGameState();

        // Start polling for updates
        this.startPolling();

        // Check if both players are present and show countdown
        if (this.gameState.players[1] !== null) {
            // Both players present - start countdown
            this.showCountdown();
        }

        this.updateAudioButton();
    }

    onScreenExit() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        if (this.gameTimer) {
            clearTimeout(this.gameTimer);
        }
        if (this.turnTimer) {
            clearTimeout(this.turnTimer);
        }
        if (this.countdownTimer) {
            clearTimeout(this.countdownTimer);
        }
        
        // Hide leaderboard button when leaving game screen
        const leaderboardBtn = document.getElementById('bottom-leaderboard-btn');
        if (leaderboardBtn) {
            leaderboardBtn.style.display = 'none';
        }
    }

    async loadGameState() {
        try {
            const result = await this.requestManager.postJSON('api/game/state.php', {
                room_code: this.roomCode
            });
            this.gameState = result.game;
            
            // Determine if it's my turn
            const currentTurnId = parseInt(this.gameState.current_turn);
            const myId = parseInt(window.currentUser.id);
            const isWatching = (currentTurnId !== myId) && this.gameStarted;
            
            // If watching and there's a new guess from opponent, display it
            if (isWatching && this.gameState.last_guess && this.gameState.last_guess.player_id !== myId) {
                const lastGuess = this.gameState.last_guess;
                const guessNum = this.gameState.total_guesses || 0;
                
                // Only display if this guess is newer than what we last displayed
                if (guessNum > this.lastGuessDisplayed) {
                    document.getElementById('guessedNumber').textContent = lastGuess.guessed_number;
                    document.getElementById('realNumber').textContent = lastGuess.secret_number;
                    document.getElementById('outcome').textContent = lastGuess.is_correct ? '✓ Correct!' : '✗ Wrong number';
                    document.getElementById('result').classList.remove('hidden');
                    this.lastGuessDisplayed = guessNum;
                    
                    // Hide result after 2 seconds
                    setTimeout(() => {
                        document.getElementById('result').classList.add('hidden');
                    }, 2000);
                }
            }
            
            // Load and update history table
            this.updateHistoryTable();
            
            this.updateUI();
        } catch (e) {
            console.error('Failed to load game state:', e);
        }
    }

    async updateHistoryTable() {
        try {
            const result = await this.requestManager.postJSON('api/game/history.php', {
                room_code: this.roomCode
            });

            if (result.success && result.history) {
                const tbody = document.getElementById('history-body');
                
                // Only add new rows (avoid duplicates)
                if (result.history.length > this.historyRowCount) {
                    for (let i = this.historyRowCount; i < result.history.length; i++) {
                        const round = result.history[i];
                        const row = document.createElement('tr');
                        const resultText = round.is_correct ? '✓ Correct' : '✗ Wrong';
                        const resultClass = round.is_correct ? 'result-correct' : 'result-wrong';
                        
                        row.innerHTML = `
                            <td>${round.round}</td>
                            <td>${round.player}</td>
                            <td>${round.guessed_number}</td>
                            <td>${round.secret_number}</td>
                            <td class="${resultClass}">${resultText}</td>
                        `;
                        tbody.appendChild(row);
                    }
                    
                    this.historyRowCount = result.history.length;
                    
                    // Auto-scroll to bottom
                    const historyContainer = document.getElementById('history-container');
                    historyContainer.scrollTop = historyContainer.scrollHeight;
                }
            }
        } catch (e) {
            console.error('Failed to load history:', e);
        }
    }

    showCountdown() {
        let count = 5;
        const countdownEl = document.getElementById('countdown-display');
        const playAreaEl = document.getElementById('play-area');
        const notYourTurnEl = document.getElementById('not-your-turn');

        // Hide game controls, show countdown
        playAreaEl.classList.add('hidden');
        notYourTurnEl.classList.add('hidden');
        countdownEl.classList.remove('hidden');

        const tick = () => {
            if (count > 0) {
                countdownEl.textContent = count;
                if (this.audioManager) {
                    this.audioManager.playSound('tick');
                }
                count--;
                this.countdownTimer = setTimeout(tick, 1000);
            } else {
                // Countdown finished - start game
                countdownEl.textContent = 'GO!';
                if (this.audioManager) {
                    this.audioManager.playSound('start');
                }
                
                setTimeout(async () => {
                    countdownEl.classList.add('hidden');
                    this.gameStarted = true;
                    
                    // Refresh game state to ensure we have correct turn info
                    await this.loadGameState();
                    
                    // Start actual game timer
                    this.startGameTimer();
                    
                    // Determine whose turn and show play area
                    // Convert to integers for comparison
                    const currentTurnId = parseInt(this.gameState.current_turn);
                    const myId = parseInt(window.currentUser.id);
                    this.isMyTurn = (currentTurnId === myId);
                    
                    if (this.isMyTurn) {
                        playAreaEl.classList.remove('hidden');
                        notYourTurnEl.classList.add('hidden');
                        this.enablePlayArea(true);
                        this.startTurnTimer();
                    } else {
                        playAreaEl.classList.add('hidden');
                        notYourTurnEl.classList.remove('hidden');
                    }
                    
                    // Update badges to show whose turn it is
                    document.getElementById('player1-badge').textContent = parseInt(this.gameState.players[0].id) === currentTurnId ? '→' : '';
                    document.getElementById('player2-badge').textContent = parseInt(this.gameState.players[1]?.id) === currentTurnId ? '→' : '';
                }, 500);
            }
        };

        tick();
    }

    updateUI() {
        // Update player names and stats
        const p1 = this.gameState.players[0];
        const p2 = this.gameState.players[1];

        document.getElementById('player1-name').textContent = p1.username;
        document.getElementById('player1-correct').textContent = p1.correct;
        document.getElementById('player1-misses').textContent = p1.incorrect;
        document.getElementById('player1-gems').textContent = this.playerGems[p1.id] || 0;

        if (p2) {
            document.getElementById('player2-name').textContent = p2.username;
            document.getElementById('player2-correct').textContent = p2.correct;
            document.getElementById('player2-misses').textContent = p2.incorrect;
            document.getElementById('player2-gems').textContent = this.playerGems[p2.id] || 0;
        }

        // Update my gems display
        const myId = parseInt(window.currentUser.id);
        document.getElementById('my-gems').textContent = this.playerGems[myId] || 0;

        // Update round display
        const totalRounds = this.gameState.total_rounds || 20;
        const guessCount = this.gameState.total_guesses || 0;
        this.updateRoundDisplay(guessCount, totalRounds);

        // Update bottom bar
        this.updateBottomBar(guessCount, totalRounds);

        // Only update turn UI if game has started
        if (!this.gameStarted) return;

        // Determine if it's my turn - convert to integers for safe comparison
        const currentTurnId = parseInt(this.gameState.current_turn);
        const myId2 = parseInt(window.currentUser.id);
        this.isMyTurn = (currentTurnId === myId2);

        // Reset turn flag when turn changes
        if (this.isMyTurn && !this.currentTurnStarted) {
            // My turn and timer not started yet
            this.updateTurnUI();  // This handles visibility via enablePlayArea()
            this.startTurnTimer();
        } else if (!this.isMyTurn) {
            // Not my turn
            this.currentTurnStarted = false;  // Reset for next turn
            this.updateTurnUI();
            if (this.turnTimer) {
                clearTimeout(this.turnTimer);
                this.turnTimer = null;
            }
        }
    }

    updateTurnUI() {
        const notYourTurnEl = document.getElementById('not-your-turn');
        const submitBtn = document.getElementById('submitBtn');

        // Always update badges to show whose turn it is (based on current_turn, not current user)
        const currentTurnId = parseInt(this.gameState.current_turn);
        document.getElementById('player1-badge').textContent = parseInt(this.gameState.players[0].id) === currentTurnId ? '→' : '';
        document.getElementById('player2-badge').textContent = parseInt(this.gameState.players[1]?.id) === currentTurnId ? '→' : '';

        if (this.isMyTurn) {
            notYourTurnEl.classList.add('hidden');
            this.enablePlayArea(true);
            submitBtn.disabled = true;  // Disable button - will auto-submit after 10s
        } else {
            notYourTurnEl.classList.remove('hidden');
            this.enablePlayArea(false);
        }
    }

    startTurnTimer() {
        // Skip if timer already running for this turn
        if (this.currentTurnStarted) return;
        
        this.currentTurnStarted = true;

        let timeRemaining = 10;
        const timerDisplay = document.getElementById('turn-time');
        timerDisplay.textContent = '10s';
        const timerBar = document.getElementById('timerBar');
        if (timerBar) timerBar.style.width = '0%';

        const updateTimer = () => {
            if (timeRemaining > 0) {
                timerDisplay.textContent = `${timeRemaining}s`;
                const progress = ((10 - timeRemaining) / 10) * 100;
                if (timerBar) {
                    timerBar.style.width = progress + '%';
                }
                timeRemaining--;
                this.turnTimer = setTimeout(updateTimer, 1000);
            } else {
                // Time's up - auto-submit, fill bar to 100%
                timerDisplay.textContent = '0s';
                if (timerBar) {
                    timerBar.style.width = '100%';
                }
                this.autoSubmitGuess();
            }
        };

        updateTimer();
    }

    async autoSubmitGuess() {
        // Submit 0 if no number selected (no random selection)
        const guessNumber = this.selectedNumber ?? 0;

        try {
            const result = await this.requestManager.postJSON('api/game/guess.php', {
                room_code: this.roomCode,
                guess: guessNumber
            });

            if (result.success) {
                // Show result with guessed number and real number
                document.getElementById('guessedNumber').textContent = result.guess.guessed_number;
                document.getElementById('realNumber').textContent = result.guess.secret_number;
                document.getElementById('outcome').textContent = result.guess.is_correct ? '✓ Correct!' : '✗ Wrong number';
                document.getElementById('result').classList.remove('hidden');

                // Award gems if correct
                if (result.guess.is_correct) {
                    const myId = parseInt(window.currentUser.id);
                    this.playerGems[myId] = (this.playerGems[myId] || 0) + 10;
                    this.showGemNotification(10);
                }

                if (this.audioManager) {
                    this.audioManager.playSound(result.guess.is_correct ? 'success' : 'fail');
                }

                // Disable controls
                this.enablePlayArea(false);

                // Reset after 2 seconds and poll for next turn
                setTimeout(() => {
                    document.getElementById('result').classList.add('hidden');
                    this.selectedNumber = null;
                    document.querySelectorAll('.number-btn').forEach(btn => btn.classList.remove('active'));
                    document.getElementById('selectedNumber').textContent = '—';
                    
                    // Poll for state update
                    this.loadGameState();
                }, 2000);
            }
        } catch (e) {
            console.error('Failed to auto-submit guess:', e);
        }
    }

    selectNumber(num) {
        if (!this.isMyTurn || !this.gameStarted) return;

        this.selectedNumber = num;
        document.querySelectorAll('.number-btn').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.dataset.number) === num);
        });
        document.getElementById('selectedNumber').textContent = num;

        if (this.audioManager) {
            this.audioManager.playSound('click');
        }
    }

    async submitGuess() {
        // Manual submit is disabled - only auto-submit allowed
        return;
    }

    startPolling() {
        this.pollingInterval = setInterval(() => {
            this.loadGameState();
        }, 1000);
    }

    startGameTimer() {
        // Display initial round count
        const totalRounds = this.gameState.total_rounds || 20;
        const guessCount = this.gameState.total_guesses || 0;
        this.updateRoundDisplay(guessCount, totalRounds);
    }

    updateRoundDisplay(guessCount, totalRounds) {
        document.getElementById('game-time').textContent = `Round ${guessCount + 1}/${totalRounds}`;
        
        // Check if game should end (all rounds completed)
        if (guessCount >= totalRounds) {
            this.endGame();
        }
    }

    updateBottomBar(guessCount, totalRounds) {
        // Update round counter
        document.getElementById('bottom-round').textContent = `${guessCount}/${totalRounds}`;

        // Update status
        let status = 'Ready';
        if (!this.gameStarted) {
            status = 'Waiting...';
        } else if (this.isMyTurn) {
            status = 'Your Turn';
        } else {
            status = 'Watching';
        }
        document.getElementById('bottom-status').textContent = status;

        // Update score (my player stats)
        const myId = parseInt(window.currentUser.id);
        const myPlayer = this.gameState.players.find(p => parseInt(p.id) === myId);
        if (myPlayer) {
            document.getElementById('bottom-score').textContent = `${myPlayer.correct}/${myPlayer.correct + myPlayer.incorrect}`;
        }
    }

    showSoundPreferenceDialog() {
        const modal = document.getElementById('sound-preference-modal');
        const enableBtn = document.getElementById('sound-enable-btn');
        const disableBtn = document.getElementById('sound-disable-btn');

        if (!modal || !enableBtn || !disableBtn) return;

        modal.classList.remove('hidden');

        // Remove old listeners
        const newEnableBtn = enableBtn.cloneNode(true);
        const newDisableBtn = disableBtn.cloneNode(true);
        enableBtn.replaceWith(newEnableBtn);
        disableBtn.replaceWith(newDisableBtn);

        newEnableBtn.addEventListener('click', () => {
            this.audioManager.setSoundPreference(true);
            this.audioManager.init();
            modal.classList.add('hidden');
            this.updateAudioButton();
        });

        newDisableBtn.addEventListener('click', () => {
            this.audioManager.setSoundPreference(false);
            modal.classList.add('hidden');
            this.updateAudioButton();
        });
    }

    async endGame() {
        // Stop polling and timers
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        if (this.gameTimer) clearTimeout(this.gameTimer);

        // Complete the game in database
        try {
            await this.requestManager.postJSON('api/game/complete.php', {
                room_code: this.roomCode
            });
            
            // Update topbar with new gem count
            if (window.updateTopBarGems) {
                window.updateTopBarGems();
            }
        } catch (e) {
            console.error('Failed to complete game:', e);
        }

        // Go to results screen
        window.router.goToResults();
    }

    enablePlayArea(enable) {
        const playArea = document.getElementById('play-area');
        document.querySelectorAll('.number-btn').forEach(btn => {
            btn.disabled = !enable;
            btn.style.opacity = enable ? '1' : '0.5';
            btn.style.cursor = enable ? 'pointer' : 'not-allowed';
        });
        document.getElementById('submitBtn').disabled = true;  // Always disabled - auto-submit only
        
        if (enable) {
            playArea.classList.remove('hidden');
        } else {
            playArea.classList.add('hidden');
        }
    }

    showGemNotification(gems) {
        const notification = document.createElement('div');
        notification.className = 'gem-notification';
        notification.innerHTML = `<span>+${gems} 💎</span>`;
        document.body.appendChild(notification);

        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Remove after animation
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }

    updateAudioButton() {
        const btn = document.getElementById('audioToggleBtn');
        btn.textContent = this.audioManager.isMuted ? '🔇 Audio' : '🔊 Audio';
    }
}

// Initialize when app loads
window.addEventListener('load', () => {
    if (!window.gameScreen) {
        window.gameScreen = new GameScreen();
    }
});
