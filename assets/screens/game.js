// Game Screen - Multiplayer game board
class GameScreen {
    constructor() {
        this.requestManager = window.requestManager;
        this.roomCode = null;
        this.gameState = null;
        this.selectedNumber = null;
        this.selectedCategory = null;  // NEW: Track selected category
        this.disabledNumbers = [];  // NEW: Track disabled/hidden numbers
        this.gameTimer = null;
        this.turnTimer = null;
        this.countdownTimer = null;
        this.isMyTurn = false;
        this.pollingInterval = null;
        this.audioManager = window.audioManager;
        this.gameStarted = false;
        this.currentTurnStarted = false;  // Prevent duplicate turn timers
        this.lastGuessDisplayed = 0;  // Track last guess to avoid duplicate displays
        this.roundStartTime = null;   // Track when round started for timing
        this.lastGuessTime = 0;       // Store last guess duration
        this.lastTrackedGuessCount = 0;  // Track last guess count for round timing
        this.historyRowCount = 0;  // Track history rows to avoid duplicates
        this.playerGems = {};  // Track gems for each player
        this.lastHintRoundShown = 0;  // Track which round hint was shown
        window.gameScreen = this;  // Store reference globally for gem tracking
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Number buttons
        document.querySelectorAll('.number-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectNumber(parseInt(e.target.dataset.number)));
        });

        // Category options (will be populated dynamically)
        document.addEventListener('category-option-click', (e) => {
            this.selectCategory(e.detail.category);
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
            window.router.goToLobby();
            return;
        }

        this.roomCode = roomCode;
        this.selectedNumber = null;
        this.selectedCategory = null;  // NEW: Reset category
        this.disabledNumbers = [];  // NEW: Reset disabled numbers
        this.isMyTurn = false;
        this.gameStarted = false;
        this.playerGems = {};  // Reset gems
        
        // Clear history table for new game
        this.clearHistoryTable();

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
    }

    async loadGameState() {
        try {
            const result = await this.requestManager.postJSON('api/game/state.php', {
                room_code: this.roomCode
            });
            this.gameState = result.game;
            
            // Initialize playerGems from game state (fixes initial display)
            if (this.gameState.players) {
                this.gameState.players.forEach(player => {
                    if (player) {
                        this.playerGems[player.id] = player.gems || 0;
                    }
                });
            }
            
            // Track round start time on first load or when guess count changes
            const currentGuessCount = this.gameState.total_guesses || 0;
            if (!this.lastTrackedGuessCount || currentGuessCount > this.lastTrackedGuessCount) {
                this.roundStartTime = Date.now();
                this.lastTrackedGuessCount = currentGuessCount;
            }
            
            // Update disabled numbers
            this.disabledNumbers = this.gameState.disabled_numbers || [];
            const currentRound = Math.ceil((this.gameState.total_guesses + 1) / 2);
            const cyclePosition = (((currentRound - 1) % 4) + 1);
            const cyclePhase = cyclePosition === 4 ? 'DISABLED' : 'normal';
            
            // Determine which pattern would be used
            let pattern = 'random';
            if (currentRound > 5) {
                pattern = 'distance_progression';
            } else if (currentRound > 2) {
                pattern = 'quartile_cycling';
            }
            if (cyclePosition === 4) {
                pattern = 'proximity_disabled';
            }
            
            this.updateDisabledNumbersUI();
            
            // Display available categories
            if (this.gameState.available_categories && !this.selectedCategory) {
                this.displayCategories(this.gameState.available_categories);
            }
            
            // Determine if it's my turn
            const currentTurnId = parseInt(this.gameState.current_turn);
            const myId = parseInt(window.currentUser.id);
            const isWatching = (currentTurnId !== myId) && this.gameStarted;
            
            // If watching and there's a new guess from opponent, display it
            if (isWatching && this.gameState.last_guess && this.gameState.last_guess.player_id !== myId) {
                const lastGuess = this.gameState.last_guess;
                const guessNum = this.gameState.total_guesses || 0;
                
                // Calculate time elapsed for this guess
                if (this.roundStartTime) {
                    this.lastGuessTime = (Date.now() - this.roundStartTime) / 1000;
                }
                
                // Only display if this guess is newer than what we last displayed
                if (guessNum > this.lastGuessDisplayed) {
                    document.getElementById('guessedNumber').textContent = lastGuess.guessed_number;
                    document.getElementById('realNumber').textContent = lastGuess.secret_number;
                    
                    // Display guess time if available
                    if (this.lastGuessTime > 0) {
                        document.getElementById('guessTime').textContent = this.lastGuessTime.toFixed(1) + 's';
                    }
                    
                    document.getElementById('outcome').textContent = lastGuess.is_correct ? '✓ Correct!' : '✗ Wrong number';
                    document.getElementById('result').classList.remove('hidden');
                    this.lastGuessDisplayed = guessNum;
                    
                    // Hide result after 4 seconds (increased from 2s)
                    setTimeout(() => {
                        document.getElementById('result').classList.add('hidden');
                    }, 4000);
                }
            }
            
            // Load and update history table
            this.updateHistoryTable();
            
            this.updateUI();
        } catch (e) {
            console.error('Failed to load game state:', e);
        }
    }

    async checkAndDisplayHint() {
        // Only show hint if it's MY turn
        if (!this.isMyTurn) {
            return;
        }
        
        // Check if this round should have a hint
        try {
            const response = await fetch('api/game/hint.php?room_code=' + this.roomCode);
            const result = await response.json();
            
            // Only show hint once per round PER PLAYER
            if (result.success && result.hint && result.current_round > this.lastHintRoundShown) {
                this.lastHintRoundShown = result.current_round;
                this.displayHintAnimation(result.hint);
            }
        } catch (e) {
            console.error('[Hint] Error fetching hint:', e);
        }
    }

    displayHintAnimation(hint) {
        const hintOverlay = document.getElementById('hint-overlay');
        const rainHint = document.getElementById('rain-hint');
        const heartsHint = document.getElementById('hearts-hint');
        
        if (!hintOverlay) {
            console.error('[Hint] hint-overlay element not found');
            return;
        }
        
        if (!rainHint || !heartsHint) {
            console.error('[Hint] rain-hint or hearts-hint element not found');
            return;
        }
        
        // Clear previous animations
        rainHint.classList.remove('show');
        heartsHint.classList.remove('show');
        hintOverlay.classList.remove('active');
        
        // Force reflow to reset animations
        void hintOverlay.offsetWidth;
        
        // Display appropriate animation
        if (hint.type === 'even') {
            // Rain animation
            this.generateRaindrops();
            hintOverlay.classList.add('active');
            // Small delay to ensure animation triggers
            setTimeout(() => {
                rainHint.classList.add('show');
            }, 10);
        } else if (hint.type === 'odd') {
            // Hearts animation
            this.generateHearts();
            hintOverlay.classList.add('active');
            // Small delay to ensure animation triggers
            setTimeout(() => {
                heartsHint.classList.add('show');
            }, 10);
        }
        
        // Hide after 5 seconds
        setTimeout(() => {
            hintOverlay.classList.remove('active');
            rainHint.classList.remove('show');
            heartsHint.classList.remove('show');
        }, 5000);
    }

    clearHintAnimation() {
        // Immediately clear any lingering hint animations
        const hintOverlay = document.getElementById('hint-overlay');
        const rainHint = document.getElementById('rain-hint');
        const heartsHint = document.getElementById('hearts-hint');
        
        if (hintOverlay) {
            hintOverlay.classList.remove('active');
        }
        if (rainHint) {
            rainHint.classList.remove('show');
        }
        if (heartsHint) {
            heartsHint.classList.remove('show');
        }
    }

    generateRaindrops() {
        const rainHint = document.getElementById('rain-hint');
        
        // Clear existing raindrops
        rainHint.querySelectorAll('.rain-drop').forEach(drop => drop.remove());
        
        // Generate 40-60 raindrops for full screen coverage
        const dropCount = Math.floor(Math.random() * 20 + 40);
        for (let i = 0; i < dropCount; i++) {
            const drop = document.createElement('div');
            drop.className = 'rain-drop';
            drop.style.left = Math.random() * 100 + '%';
            // Start well above screen
            drop.style.top = Math.random() * 100 - 100 + 'px';
            // Duration should be long enough to fall full screen
            const duration = Math.random() * 2 + 3; // 3-5 seconds
            drop.style.animationDuration = duration + 's';
            // Stagger drops falling at different times
            drop.style.animationDelay = Math.random() * 3 + 's';
            rainHint.appendChild(drop);
        }
    }

    generateHearts() {
        const heartsHint = document.getElementById('hearts-hint');
        
        // Clear existing hearts
        heartsHint.querySelectorAll('.heart').forEach(heart => heart.remove());
        
        // Generate 30-40 hearts for full screen coverage
        const heartCount = Math.floor(Math.random() * 10 + 30);
        for (let i = 0; i < heartCount; i++) {
            const heart = document.createElement('div');
            heart.className = 'heart';
            heart.textContent = '💗';
            heart.style.left = Math.random() * 100 + '%';
            // Start at bottom and float up
            heart.style.bottom = Math.random() * 100 - 100 + 'px';
            // Duration should match full screen float
            heart.style.animationDuration = (Math.random() * 2 + 3) + 's'; // 3-5 seconds
            // Stagger hearts at different start times
            heart.style.animationDelay = Math.random() * 3 + 's';
            heart.style.opacity = Math.random() * 0.4 + 0.6; // 0.6-1.0 (more visible)
            heartsHint.appendChild(heart);
        }
    }

    updateDisabledNumbersUI() {
        // Hide number buttons for disabled numbers
        if (!this.disabledNumbers || this.disabledNumbers.length === 0) {
            // No disabled numbers, show all
            document.querySelectorAll('.number-btn').forEach(btn => {
                btn.classList.remove('disabled');
                btn.classList.remove('blast-effect');
            });
            return;
        }
        
        // Convert to integers for comparison
        const disabledInts = this.disabledNumbers.map(n => parseInt(n));
        
        let disabledCount = 0;
        document.querySelectorAll('.number-btn').forEach(btn => {
            const num = parseInt(btn.dataset.number);
            if (disabledInts.includes(num)) {
                // Check if this number wasn't already disabled (new disable)
                if (!btn.classList.contains('disabled')) {
                    // Play blast animation and sound
                    this.playNumberBlastEffect(btn, num);
                }
                btn.classList.add('disabled');
                disabledCount++;
            } else {
                btn.classList.remove('disabled');
                btn.classList.remove('blast-effect');
            }
        });
    }

    playNumberBlastEffect(btn, num) {
        // Add blast animation class
        btn.classList.add('blast-effect');
        
        // Play sound effect
        this.playDisabledSound();
        
        // Create particle explosion effect
        const rect = btn.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        
        // Create 8-12 particles radiating outward
        const particleCount = 10;
        for (let i = 0; i < particleCount; i++) {
            const angle = (i / particleCount) * Math.PI * 2;
            const distance = 80 + Math.random() * 60;
            const tx = Math.cos(angle) * distance;
            const ty = Math.sin(angle) * distance;
            
            const particle = document.createElement('div');
            particle.className = `particle ${Math.random() > 0.5 ? 'spark' : 'dust'}`;
            particle.style.left = centerX + 'px';
            particle.style.top = centerY + 'px';
            particle.style.setProperty('--tx', tx + 'px');
            particle.style.setProperty('--ty', ty + 'px');
            
            document.body.appendChild(particle);
            
            // Remove particle after animation
            setTimeout(() => particle.remove(), 800);
        }
    }

    playDisabledSound() {
        // Create blast sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create multiple sounds for variety
            const soundType = Math.floor(Math.random() * 3);
            
            if (soundType === 0) {
                // Explosion pop
                this.playExplosionSound(audioContext);
            } else if (soundType === 1) {
                // Laser zap
                this.playLaserSound(audioContext);
            } else {
                // Woosh blast
                this.playWooshSound(audioContext);
            }
        } catch (e) {
            // Audio synthesis not available
        }
    }

    playExplosionSound(audioContext) {
        // Explosion: quick freq drop with noise
        const now = audioContext.currentTime;
        const duration = 0.3;
        
        // Create noise
        const bufferSize = audioContext.sampleRate * duration;
        const buffer = audioContext.createBuffer(1, bufferSize, audioContext.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1;
        }
        
        const noise = audioContext.createBufferSource();
        noise.buffer = buffer;
        
        const noiseGain = audioContext.createGain();
        noiseGain.gain.setValueAtTime(0.3, now);
        noiseGain.gain.exponentialRampToValueAtTime(0.01, now + duration);
        
        noise.connect(noiseGain);
        noiseGain.connect(audioContext.destination);
        noise.start(now);
        noise.stop(now + duration);
    }

    playLaserSound(audioContext) {
        // Laser: downward frequency sweep
        const now = audioContext.currentTime;
        const duration = 0.15;
        
        const osc = audioContext.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(1200, now);
        osc.frequency.exponentialRampToValueAtTime(400, now + duration);
        
        const gain = audioContext.createGain();
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + duration);
        
        osc.connect(gain);
        gain.connect(audioContext.destination);
        
        osc.start(now);
        osc.stop(now + duration);
    }

    playWooshSound(audioContext) {
        // Woosh: sweeping noise
        const now = audioContext.currentTime;
        const duration = 0.2;
        
        const bufferSize = audioContext.sampleRate * duration;
        const buffer = audioContext.createBuffer(1, bufferSize, audioContext.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1;
        }
        
        const noise = audioContext.createBufferSource();
        noise.buffer = buffer;
        
        // Filter to make it sound like swoosh
        const filter = audioContext.createBiquadFilter();
        filter.type = 'highpass';
        filter.frequency.setValueAtTime(200, now);
        filter.frequency.exponentialRampToValueAtTime(2000, now + duration);
        
        const gain = audioContext.createGain();
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + duration);
        
        noise.connect(filter);
        filter.connect(gain);
        gain.connect(audioContext.destination);
        
        noise.start(now);
        noise.stop(now + duration);
    }

    displayCategories(categories) {
        // Handle new structured format with free and paid groups
        const freeContainer = document.getElementById('free-categories');
        const paidContainer = document.getElementById('paid-categories-container');
        
        if (!freeContainer || !paidContainer) return;
        
        // Clear previous content
        freeContainer.innerHTML = '';
        paidContainer.innerHTML = '';
        
        // Display free category label only (no button)
        // FREE is represented by selecting a number 1-20 using the number buttons
        if (categories.free) {
            const freeLabel = document.createElement('div');
            freeLabel.className = 'category-label';
            freeLabel.textContent = '💎 FREE - Select a number (1-20)';
            freeContainer.appendChild(freeLabel);
        }
        
        // Display paid categories with grouping
        if (categories.paid && categories.paid.length > 0) {
            const firstPaid = categories.paid[0];
            const paidType = firstPaid.type || 'range';
            
            // Determine group title and icon
            let groupTitle = '';
            if (paidType === 'parity') {
                groupTitle = '🔢 Parity';
            } else if (paidType === 'range') {
                groupTitle = '📊 Ranges';
            } else {
                groupTitle = '💎 Options';
            }
            
            // Create paid group container
            const paidGroup = document.createElement('div');
            paidGroup.className = 'paid-group';
            
            const groupTitleEl = document.createElement('div');
            groupTitleEl.className = 'group-title';
            groupTitleEl.textContent = groupTitle;
            paidGroup.appendChild(groupTitleEl);
            
            // Create options grid for paid categories
            const optionsGrid = document.createElement('div');
            optionsGrid.className = 'category-options';
            
            categories.paid.forEach(category => {
                const btn = this.createCategoryButton(category, false);
                optionsGrid.appendChild(btn);
            });
            
            paidGroup.appendChild(optionsGrid);
            paidContainer.appendChild(paidGroup);
        }
    }

    createCategoryButton(category, isFree) {
        const button = document.createElement('button');
        button.className = 'category-option';
        button.setAttribute('data-category', category.name);
        
        const label = document.createElement('div');
        label.className = 'category-option-label';
        label.textContent = category.label;
        
        const description = document.createElement('div');
        description.className = 'category-option-description';
        description.textContent = category.description;
        
        const cost = document.createElement('div');
        cost.className = `category-option-cost ${isFree ? 'free' : 'paid'}`;
        cost.textContent = isFree ? '✓ Free' : `${category.cost} 💎`;
        
        button.appendChild(label);
        button.appendChild(description);
        button.appendChild(cost);
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectCategory(category);
        });
        
        return button;
    }

    selectCategory(category) {
        if (!this.isMyTurn || !this.gameStarted) return;
        
        // Check if user has enough gems for paid category
        if (category.cost > 0) {
            const currentUserGems = this.gameState.current_user_gems || 0;
            if (currentUserGems < category.cost) {
                this.showCategoryMessage(`Insufficient gems! Need ${category.cost}, have ${currentUserGems}`, false);
                return;
            }
        }
        
        this.selectedCategory = category;
        
        // IMPORTANT: Keep FREE number selection intact when selecting GEM category
        // Both FREE and GEM are selected simultaneously in same round
        // Don't modify number buttons - they stay enabled for FREE selection
        
        // Update UI - highlight selected category button
        document.querySelectorAll('.category-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Find and highlight the clicked button
        const categoryName = category.name;
        const selectedBtn = document.querySelector(`.category-option[data-category="${categoryName}"]`);
        if (selectedBtn) {
            selectedBtn.classList.add('selected');
        }
        
        // Update the selected display at bottom
        document.getElementById('selectedCategory').textContent = category.label;
        document.getElementById('categoryFee').textContent = category.cost;
        
        // Show success message
        if (category.cost > 0) {
            this.showCategoryMessage(`Selected ${category.label} - ${category.cost} 💎 will be deducted on submit`, true);
        } else {
            this.showCategoryMessage(`Selected ${category.label}`, true);
        }
        
        if (this.audioManager) {
            this.audioManager.playSound('click');
        }
    }

    getCategoryValidNumbers(categoryName) {
        // Return array of valid numbers for category
        if (categoryName === '1-10') {
            return Array.from({length: 10}, (_, i) => i + 1);
        } else if (categoryName === 'odd') {
            return [1, 3, 5, 7, 9];
        } else if (categoryName === 'even') {
            return [2, 4, 6, 8, 10];
        } else if (categoryName.includes('-')) {
            // Range category like '1-5', '6-10'
            const [low, high] = categoryName.split('-').map(x => parseInt(x));
            return Array.from({length: high - low + 1}, (_, i) => low + i);
        }
        return [];
    }

    isNumberValidForCategory(number, categoryName) {
        // Check if a number is valid for a specific category
        const validNumbers = this.getCategoryValidNumbers(categoryName);
        return validNumbers.includes(parseInt(number));
    }

    showCategoryMessage(message, isSuccess) {
        const messageEl = document.getElementById('category-message');
        if (!messageEl) return;
        
        messageEl.textContent = message;
        messageEl.classList.remove('hidden');
        if (isSuccess) {
            messageEl.classList.add('success');
        } else {
            messageEl.classList.remove('success');
        }
        
        setTimeout(() => {
            messageEl.classList.add('hidden');
        }, 3000);
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
                        
                        // Determine if FREE or GEM category and format accordingly
                        let freeContent = '—';
                        let gemContent = '—';
                        let freeClass = '';
                        let gemClass = '';
                        
                        // Check if it's a FREE guess (no cost) or GEM guess
                        if (round.category_cost === 0) {
                            // FREE category submission
                            freeContent = round.guessed_number;
                            if (round.is_correct) {
                                freeClass = 'result-correct';
                                freeContent += ' ✓';
                            } else {
                                freeClass = 'result-wrong';
                                freeContent += ' ✗';
                            }
                        } else {
                            // GEM category submission
                            gemContent = round.selected_category;
                            if (round.is_correct) {
                                gemClass = 'result-correct';
                                gemContent += ' ✓';
                            } else {
                                gemClass = 'result-wrong';
                                gemContent += ' ✗';
                            }
                        }
                        
                        row.innerHTML = `
                            <td>${round.round}</td>
                            <td>${round.player}</td>
                            <td class="${freeClass}">${freeContent}</td>
                            <td class="${gemClass}">${gemContent}</td>
                            <td>${round.secret_number}</td>
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

    clearHistoryTable() {
        // Clear the history table and reset counter
        const tbody = document.getElementById('history-body');
        if (tbody) {
            tbody.innerHTML = '';
            this.historyRowCount = 0;
        }
    }

    async checkAndDisplayHint() {
        if (!this.isMyTurn || !this.gameStarted) return;

        try {
            const result = await this.requestManager.getJSON('api/game/hint.php', {
                room_code: this.roomCode
            });

            if (result.success && result.hint) {
                // Show hint popup
                this.displayHintPopup(result.hint);
            }
        } catch (e) {
            console.error('[Hint] Error fetching hint:', e);
        }
    }

    displayHintPopup(hint) {
        const popup = document.getElementById('hint-popup');
        const iconEl = document.getElementById('hint-icon');
        
        if (!popup || !iconEl) return;

        // Set icon and color based on hint type
        if (hint.type === 'even') {
            iconEl.textContent = '🍬';  // Toffee icon for even
            popup.style.filter = 'drop-shadow(0 0 15px rgba(255, 215, 0, 0.5))';
        } else {
            iconEl.textContent = '❤️';  // Heart for odd
            popup.style.filter = 'drop-shadow(0 0 15px rgba(255, 105, 180, 0.5))';
        }

        // Show popup
        popup.classList.remove('hidden');
        popup.classList.add('show');

        // Hide after 4 seconds
        setTimeout(() => {
            popup.classList.remove('show');
            popup.classList.add('hidden');
        }, 4000);
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

        let timeRemaining = 15;
        const timerDisplay = document.getElementById('turn-time');
        timerDisplay.textContent = '15s';
        const timerBar = document.getElementById('timerBar');
        if (timerBar) timerBar.style.width = '0%';

        const updateTimer = () => {
            if (timeRemaining > 0) {
                timerDisplay.textContent = `${timeRemaining}s`;
                const progress = ((15 - timeRemaining) / 15) * 100;
                if (timerBar) {
                    timerBar.style.width = progress + '%';
                }
                timeRemaining--;
                this.turnTimer = setTimeout(updateTimer, 1000);
            } else {
                // Time's up - check if user selected something
                if (!this.selectedNumber && !this.selectedCategory) {
                    // Nothing selected - skip this turn and advance to next player
                    timerDisplay.textContent = '0s';
                    if (timerBar) {
                        timerBar.style.width = '100%';
                    }
                    
                    // Disable play area
                    this.enablePlayArea(false);
                    
                    // Call skip endpoint to advance turn
                    this.skipTurn();
                    return;
                }
                
                // User selected something - auto-submit
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
        // NEW: Support simultaneous FREE and GEM category evaluation
        // FREE: Optional number selection from 1-20
        // GEM: Optional category selection (ODD/EVEN/Range)
        
        const freeNumber = this.selectedNumber;
        const gemCategory = this.selectedCategory?.name;
        
        // NEW: User MUST select at least one category
        // No auto-fill with default values
        if (!freeNumber && !gemCategory) {
            console.error('[AutoSubmit] User must select FREE number or GEM category');
            this.showCategoryMessage('Please select a number or gem category', false);
            return;
        }
        
        let finalFreeNumber = freeNumber;
        let finalGemCategory = gemCategory;

        try {
            // Submit both FREE and GEM together
            const result = await this.requestManager.postJSON('api/game/guess.php', {
                room_code: this.roomCode,
                free_guess: finalFreeNumber || null,
                gem_category: finalGemCategory || null
            });

            if (result.success) {
                // Show result with guessed number and real number
                document.getElementById('guessedNumber').textContent = result.guess.guessed_number;
                document.getElementById('realNumber').textContent = result.guess.secret_number;
                
                // Build outcome message showing both category results
                let outcomeText = '';
                
                // Check FREE category result
                if (result.guess.free_is_correct) {
                    outcomeText += '✓ FREE Correct! +10 💎\n';
                } else if (finalFreeNumber) {
                    outcomeText += '✗ FREE Wrong\n';
                }
                
                // Check GEM category result
                if (result.guess.gem_is_correct) {
                    outcomeText += `✓ ${result.guess.gem_category} Correct! +20 💎`;
                } else if (finalGemCategory) {
                    outcomeText += `✗ ${result.guess.gem_category} Wrong`;
                }
                
                document.getElementById('outcome').textContent = outcomeText;
                document.getElementById('result').classList.remove('hidden');

                // Calculate and display total rewards
                let totalReward = 0;
                if (result.guess.free_is_correct) totalReward += 10;
                if (result.guess.gem_is_correct) totalReward -= 10;  // Cost
                if (result.guess.gem_is_correct) totalReward += 20;
                
                // Show gem notification with total reward
                if (totalReward !== 0) {
                    this.showGemNotification(totalReward, totalReward < 0);
                }

                // Update player gems IMMEDIATELY in local state
                const myId = parseInt(window.currentUser.id);
                const newBalance = result.guess.gems_balance || (this.playerGems[myId] || 0);
                this.playerGems[myId] = newBalance;
                
                // Update top bar gems display in real-time immediately
                const gemsDisplay = document.getElementById('top-gems-display');
                if (gemsDisplay) {
                    gemsDisplay.textContent = `💎 ${newBalance}`;
                }
                
                // Also verify with backend to ensure accuracy
                if (window.updateTopBarGems) {
                    setTimeout(() => window.updateTopBarGems(), 500);
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
                    this.selectedCategory = null;  // Reset category for next turn
                    document.querySelectorAll('.number-btn').forEach(btn => btn.classList.remove('active'));
                    document.getElementById('selectedNumber').textContent = '—';
                    document.getElementById('selectedCategory').textContent = '—';
                    document.getElementById('categoryFee').textContent = '—';
                    
                    // Poll for state update
                    this.loadGameState();
                }, 2000);
            }
        } catch (e) {
            const errorMsg = e?.error || e?.message || JSON.stringify(e);
            this.showCategoryMessage(`Auto-submit failed: ${errorMsg}`, false);
        }
    }

    async skipTurn() {
        // Skip this player's turn and advance to next player
        try {
            const result = await this.requestManager.postJSON('api/game/skip.php', {
                room_code: this.roomCode
            });

            if (result.success) {
                // Wait 2 seconds then load next player state
                setTimeout(() => {
                    this.selectedNumber = null;
                    this.selectedCategory = null;
                    document.querySelectorAll('.number-btn').forEach(btn => btn.classList.remove('active'));
                    document.getElementById('selectedNumber').textContent = '—';
                    document.getElementById('selectedCategory').textContent = '—';
                    document.getElementById('categoryFee').textContent = '—';
                    
                    // Load game state for next player
                    this.loadGameState();
                }, 1000);
            }
        } catch (e) {
            const errorMsg = e?.error || e?.message || JSON.stringify(e);
            console.error('[Skip] Failed - Error:', errorMsg);
            this.showCategoryMessage(`Turn skip failed: ${errorMsg}`, false);
        }
    }

    selectNumber(num) {
        if (!this.isMyTurn || !this.gameStarted) return;

        const numInt = parseInt(num);

        // Check if number is disabled (convert to int for comparison)
        const disabledInts = this.disabledNumbers.map(n => parseInt(n));
        if (disabledInts.includes(numInt)) {
            this.showCategoryMessage(`Number ${num} is disabled in this difficulty level`, false);
            return;
        }

        // NEW: Allow selecting any number 1-10 (FREE category)
        // Numbers are INDEPENDENT from GEM category selection
        // GEM categories (ODD/EVEN/Range) don't constrain number selection
        
        // Basic validation: number must be 1-10
        if (numInt < 1 || numInt > 10) {
            this.showCategoryMessage('Number must be between 1 and 10', false);
            return;
        }

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
        // Stop ALL polling and timers
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        if (this.gameTimer) clearTimeout(this.gameTimer);
        if (this.countdownTimer) clearTimeout(this.countdownTimer);
        if (this.turnTimer) clearTimeout(this.turnTimer);

        // Save last game code for history access
        if (this.roomCode) {
            localStorage.setItem('lastGameCode', this.roomCode);
        }

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
        
        // Re-apply disabled numbers UI after enabling/disabling
        this.updateDisabledNumbersUI();
        
        if (enable) {
            playArea.classList.remove('hidden');
        } else {
            playArea.classList.add('hidden');
        }
    }

    showGemNotification(gems, isCategoryPayment = false) {
        const notification = document.createElement('div');
        notification.className = 'gem-notification';
        const sign = gems >= 0 ? '+' : '';
        const color = gems >= 0 ? 'success' : 'error';
        notification.innerHTML = `<span class="gem-notif-${color}">${sign}${gems} 💎</span>`;
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
