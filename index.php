<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Purple Guess — Multiplayer</title>
    <link rel="stylesheet" href="assets/style.css?v=1.3">
    <script src="assets/translations.js?v=1.1"></script>
</head>
<body>
    <div class="app-container">
        <!-- TOP BAR -->
        <header class="top-bar">
            <div class="top-bar-brand">
                <h1 class="app-logo" id="logo-home" style="cursor: pointer;">🎲 Purple Guess</h1>
            </div>
            <div class="top-bar-actions">
                <span id="top-gems-display" class="topbar-gems" style="display: none;"></span>
                <span id="top-user-display" class="user-badge"></span>
            </div>
        </header>

        <!-- SCREENS CONTAINER -->
        <div class="screens-wrapper">
        <!-- LOGIN SCREEN -->
        <div id="login-screen" class="screen active">
            <header class="hero">
                <h1 class="title" data-i18n="app.title">Purple Guess</h1>
                <p class="subtitle" data-i18n="app.subtitle">Multiplayer number guessing battle</p>
            </header>

            <section class="card">
                <div class="auth-container">
                    <div class="auth-tabs">
                        <button class="tab-btn active" data-tab="login-form" data-i18n="login.tab">Login</button>
                        <button class="tab-btn" data-tab="register-form" data-i18n="register.tab">Register</button>
                    </div>

                    <!-- Login Form -->
                    <form id="login-form" class="auth-form active">
                        <input type="text" id="login-username" data-i18n-placeholder="login.username" placeholder="Username" required>
                        <input type="password" id="login-password" autocomplete="current-password" data-i18n-placeholder="login.password" placeholder="Password" required>
                        <button type="submit" class="btn primary full-width" data-i18n="login.button">Login</button>
                        <div id="login-error" class="error-message"></div>
                    </form>

                    <!-- Register Form -->
                    <form id="register-form" class="auth-form">
                        <input type="text" id="register-username" data-i18n-placeholder="register.username" placeholder="Username (3+ chars)" required>
                        <input type="password" id="register-password" autocomplete="new-password" data-i18n-placeholder="register.password" placeholder="Password (6+ chars)" required>
                        <button type="submit" class="btn primary full-width" data-i18n="register.button">Register</button>
                        <div id="register-error" class="error-message"></div>
                    </form>
                </div>
            </section>

            <footer class="footer" data-i18n="footer.credit">Developed by HanSakaSheHan ❤️</footer>
        </div>

        <!-- LOBBY SCREEN -->
        <div id="lobby-screen" class="screen">
            <header class="hero">
                <h1 class="title" data-i18n="app.title">Purple Guess</h1>
                <div class="user-info">
                    <span id="user-display">User</span>
                    <button id="logout-btn" class="btn secondary small" data-i18n="lobby.logout">Logout</button>
                </div>
            </header>

            <section class="card">
                <div class="lobby-container">
                    <div class="lobby-section">
                        <h2 data-i18n="lobby.create">Create Room</h2>
                        <button id="create-room-btn" class="btn primary" data-i18n="lobby.createButton">Create New Room</button>
                        <div id="created-room" class="hidden">
                            <p><span data-i18n="lobby.roomCode">Room Code:</span> <strong id="room-code-display">—</strong></p>
                            <button id="copy-code-btn" class="btn secondary" data-i18n="lobby.copy">Copy Code</button>
                            <p class="muted" data-i18n="lobby.waiting">Waiting for opponent to join...</p>
                        </div>
                    </div>

                    <div class="lobby-section">
                        <h2 data-i18n="lobby.join">Join Room</h2>
                        <input type="text" id="join-code-input" data-i18n-placeholder="lobby.joinInput" placeholder="Enter room code" maxlength="4">
                        <button id="join-room-btn" class="btn primary" data-i18n="lobby.joinButton">Join Room</button>
                        <div id="join-error" class="error-message"></div>
                    </div>

                    <div id="admin-panel" class="lobby-section hidden">
                        <h2>⚙️ <span data-i18n="lobby.admin">Admin Settings</span></h2>
                        <label data-i18n="lobby.rounds">Total Rounds (5-100)</label>
                        <input type="number" id="rounds-count-input" min="5" max="100" value="20">
                        <button id="save-config-btn" class="btn primary" data-i18n="lobby.save">Save Settings</button>
                        <div id="admin-message" class="message"></div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border);">
                    <button id="view-leaderboard-btn" class="btn secondary">🏆 <span data-i18n="lobby.leaderboard">View Leaderboard</span></button>
                </div>
            </section>

            <footer class="footer" data-i18n="footer.credit">Developed by HanSakaSheHan ❤️</footer>
        </div>

        <!-- GAME SCREEN -->
        <div id="game-screen" class="screen">
            <header class="hero">
                <h1 class="title">Purple Guess — Battle</h1>
                <div class="game-header-stats">
                    <div class="game-timer">
                        <span id="game-time">5:00</span>
                    </div>
                    <div class="my-gems-display">
                        <span class="gem-label">💎 My Gems:</span>
                        <span id="my-gems" class="gem-count">0</span>
                    </div>
                </div>
            </header>

            <section class="game-card">
                <!-- Dual Player Display -->
                <div class="dual-players">
                    <div class="player-section player-1">
                        <div class="player-header">
                            <h3 id="player1-name">Player 1</h3>
                            <span id="player1-badge" class="turn-badge"></span>
                        </div>
                        <div class="player-stats">
                            <div class="stat"><span class="label">Correct:</span> <span id="player1-correct">0</span></div>
                            <div class="stat"><span class="label">Misses:</span> <span id="player1-misses">0</span></div>
                            <div class="stat"><span class="label">💎 Gems:</span> <span id="player1-gems">0</span></div>
                        </div>
                    </div>

                    <div class="vs-divider">VS</div>

                    <div class="player-section player-2">
                        <div class="player-header">
                            <h3 id="player2-name">Waiting...</h3>
                            <span id="player2-badge" class="turn-badge"></span>
                        </div>
                        <div class="player-stats">
                            <div class="stat"><span class="label">Correct:</span> <span id="player2-correct">0</span></div>
                            <div class="stat"><span class="label">💎 Gems:</span> <span id="player2-gems">0</span></div>
                            <div class="stat"><span class="label">Misses:</span> <span id="player2-misses">0</span></div>
                        </div>
                    </div>
                </div>

                <!-- Game Controls -->
                <div class="controls">
                    <div class="turn-timer">
                        <div class="timer" id="timer">
                            <div class="timer-bar" id="timerBar"></div>
                        </div>
                        <span id="turn-time">10s</span>
                    </div>
                    <button id="audioToggleBtn" class="btn secondary">🔊 Audio</button>
                </div>

                <!-- Countdown Display -->
                <div id="countdown-display" class="countdown-display hidden">5</div>

                <!-- Play Area -->
                <div class="play-area" id="play-area">
                    <!-- Category Selector -->
                    <div class="category-selector" id="category-selector">
                        <h4>Select Category</h4>
                        <div class="category-container">
                            <!-- Free Category (Always available) -->
                            <div class="category-group free-group">
                                <div class="group-title">🎯 Free</div>
                                <div class="category-options" id="free-categories">
                                    <!-- Generated dynamically by JavaScript -->
                                </div>
                            </div>

                            <!-- Paid Categories (Grouped) -->
                            <div id="paid-categories-container" class="paid-categories-container">
                                <!-- Generated dynamically by JavaScript -->
                            </div>
                        </div>
                        <div id="category-message" class="category-message hidden"></div>
                    </div>

                    <!-- Number Grid (1-20) -->
                    <div class="number-grid" id="number-grid">
                        <button class="number-btn" data-number="1">1</button>
                        <button class="number-btn" data-number="2">2</button>
                        <button class="number-btn" data-number="3">3</button>
                        <button class="number-btn" data-number="4">4</button>
                        <button class="number-btn" data-number="5">5</button>
                        <button class="number-btn" data-number="6">6</button>
                        <button class="number-btn" data-number="7">7</button>
                        <button class="number-btn" data-number="8">8</button>
                        <button class="number-btn" data-number="9">9</button>
                        <button class="number-btn" data-number="10">10</button>
                        <button class="number-btn" data-number="11">11</button>
                        <button class="number-btn" data-number="12">12</button>
                        <button class="number-btn" data-number="13">13</button>
                        <button class="number-btn" data-number="14">14</button>
                        <button class="number-btn" data-number="15">15</button>
                        <button class="number-btn" data-number="16">16</button>
                        <button class="number-btn" data-number="17">17</button>
                        <button class="number-btn" data-number="18">18</button>
                        <button class="number-btn" data-number="19">19</button>
                        <button class="number-btn" data-number="20">20</button>
                    </div>
                    <button id="submitBtn" class="btn" disabled>Send Guess</button>
                </div>
                <div class="selected-display">Selected: <span id="selectedNumber">—</span> | Category: <span id="selectedCategory">—</span> | Fee: <span id="categoryFee">—</span> 💎</div>

                <!-- Result Display -->
                <div id="result" class="result hidden">
                    <div class="reveal">Your guess: <span id="guessedNumber">-</span></div>
                    <div class="reveal">Real number: <span id="realNumber">-</span></div>
                    <div class="reveal time-display">⏱️ <span id="guessTime">0.0s</span></div>
                    <div id="outcome" class="outcome"></div>
                </div>

                <div id="not-your-turn" class="message-overlay hidden">
                    <p>Opponent's turn — Watching...</p>
                </div>

                <!-- Results History Table -->
                <div id="history-container" class="history-container">
                    <h3>Round History</h3>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Round</th>
                                <th>Player</th>
                                <th>Guess</th>
                                <th>Real #</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody id="history-body">
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="footer">Developed by HanSakaSheHan ❤️</footer>
        </main>

        <!-- BOTTOM BAR -->
        <footer class="bottom-bar">
            <div class="bottom-bar-stats">
                <div class="stat-item">
                    <span class="stat-label">Status</span>
                    <span id="bottom-status" class="stat-value">Ready</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Round</span>
                    <span id="bottom-round" class="stat-value">-</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- SOUND PREFERENCE MODAL (First time only) -->
    <div id="sound-preference-modal" class="modal hidden">
        <div class="modal-content">
            <h2>🔊 Game Sounds</h2>
            <p>Would you like to enable sound effects and background music?</p>
            <div class="modal-buttons">
                <button id="sound-enable-btn" class="btn primary">✓ Enable Sounds</button>
                <button id="sound-disable-btn" class="btn secondary">✗ Disable Sounds</button>
            </div>
            <p class="modal-hint">You can change this anytime during gameplay</p>
        </div>
    </div>

    <div id="results-screen" class="screen">
            <header class="hero">
                <h1 class="title">Game Over!</h1>
            </header>

            <section class="card results-container">
                <div id="winner-announcement" class="winner-box">
                    <h2>🏆 <span id="winner-text">Loading...</span></h2>
                </div>

                <div class="results-table">
                    <div class="result-row header">
                        <div>Player</div>
                        <div>Correct</div>
                        <div>Misses</div>
                        <div>💎 Gems</div>
                    </div>
                    <div class="result-row">
                        <div id="result-p1-name">Player 1</div>
                        <div id="result-p1-correct">0</div>
                        <div id="result-p1-misses">0</div>
                        <div id="result-p1-gems">0</div>
                    </div>
                    <div class="result-row">
                        <div id="result-p2-name">Player 2</div>
                        <div id="result-p2-correct">0</div>
                        <div id="result-p2-misses">0</div>
                        <div id="result-p2-gems">0</div>
                    </div>
                </div>

                <!-- Gem Breakdown Section -->
                <div id="gem-breakdown" class="gem-breakdown-section hidden">
                    <h3>💎 Gem Breakdown</h3>
                    <div class="breakdown-grid">
                        <div id="p1-breakdown" class="breakdown-box">
                            <h4 id="p1-breakdown-name">Player 1</h4>
                            <div class="breakdown-items">
                                <div class="breakdown-item">
                                    <span class="label">Free Category Wins:</span>
                                    <span id="p1-free-wins">0</span> × 10 = <span id="p1-free-total">0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="label">Paid Category Wins:</span>
                                    <span id="p1-paid-wins">0</span> × 20 = <span id="p1-paid-rewards">0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="label">Paid Category Costs:</span>
                                    <span id="p1-paid-used">0</span> × 10 = <span id="p1-paid-costs">0</span>
                                </div>
                                <div class="breakdown-item total">
                                    <span class="label">Net Gems:</span>
                                    <span id="p1-net-gems">0</span>
                                </div>
                            </div>
                        </div>
                        <div id="p2-breakdown" class="breakdown-box">
                            <h4 id="p2-breakdown-name">Player 2</h4>
                            <div class="breakdown-items">
                                <div class="breakdown-item">
                                    <span class="label">Free Category Wins:</span>
                                    <span id="p2-free-wins">0</span> × 10 = <span id="p2-free-total">0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="label">Paid Category Wins:</span>
                                    <span id="p2-paid-wins">0</span> × 20 = <span id="p2-paid-rewards">0</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="label">Paid Category Costs:</span>
                                    <span id="p2-paid-used">0</span> × 10 = <span id="p2-paid-costs">0</span>
                                </div>
                                <div class="breakdown-item total">
                                    <span class="label">Net Gems:</span>
                                    <span id="p2-net-gems">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Game History Section -->
                <div id="game-history" class="game-history-section">
                    <h3>📋 Game History</h3>
                    <div class="history-table-container">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Round</th>
                                    <th>Player</th>
                                    <th>Guess</th>
                                    <th>Answer</th>
                                    <th>Result</th>
                                    <th>Category</th>
                                    <th>Gem Change</th>
                                </tr>
                            </thead>
                            <tbody id="game-history-body">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="results-actions">
                    <button id="play-again-btn" class="btn primary">Play Again</button>
                    <button id="lobby-btn" class="btn secondary">Back to Lobby</button>
                </div>
            </section>

            <footer class="footer">Developed by HanSakaSheHan ❤️</footer>
        </div>

        <!-- HISTORY SCREEN -->
        <div id="history-screen" class="screen">
            <header class="hero">
                <h1 class="title">📋 Game History</h1>
                <p class="subtitle">Your past games</p>
            </header>

            <section class="card">
                <div id="history-empty" style="text-align: center; padding: 40px; color: var(--text-secondary); display: none !important;">
                    No games yet. Play your first game!
                </div>
                <div class="history-container" id="history-container" style="display: block !important; background: rgba(0, 255, 0, 0.3) !important; border: 3px solid lime !important; min-height: 200px !important;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 20%;">Opponents</th>
                                <th style="width: 10%;">Your Score</th>
                                <th style="width: 15%;">Opponent Score</th>
                                <th style="width: 10%;">Result</th>
                                <th style="width: 15%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="history-body">
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="action-buttons">
                <button id="history-back-btn" class="btn primary">← Back to Lobby</button>
            </div>

            <footer class="footer">Developed by HanSakaSheHan ❤️</footer>
        </div>

        <!-- LEADERBOARD SCREEN -->
        <div id="leaderboard-screen" class="screen">
            <header class="hero">
                <h1 class="title">🏆 Leaderboard</h1>
                <p class="subtitle">Top players ranked by wins</p>
            </header>

            <section class="card">
                <div class="leaderboard-container">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Rank</th>
                                <th style="width: 25%;">Player</th>
                                <th style="width: 10%;">Games</th>
                                <th style="width: 10%;">Wins</th>
                                <th style="width: 12%;">Win %</th>
                                <th style="width: 18%;">Accuracy</th>
                                <th style="width: 15%;">Correct</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboard-body">
                            <tr><td colspan="7" style="text-align: center; padding: 20px;">Loading leaderboard...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="action-buttons">
                <button id="leaderboard-back-btn" class="btn primary">← Back to Lobby</button>
            </div>

            <footer class="footer">Developed by HanSakaSheHan ❤️</footer>
        </div>

        </div>

        <!-- BOTTOM BAR -->
        <footer class="bottom-bar">
            <button id="home-btn" class="btn secondary" style="display: none;">🏠 Hom</button>
            <button id="bottom-history-btn" class="btn secondary" style="display: none;">📋 His</button>
            <button id="bottom-leaderboard-btn" class="btn secondary" style="display: none;">🏆 Leadboad</button>
            <!-- Hidden elements for JavaScript functionality -->
            <div id="bottom-status" style="display: none;"></div>
            <div id="bottom-round" style="display: none;"></div>
            <div id="bottom-score" style="display: none;"></div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="assets/app.js?v=1.4"></script>
    <script src="assets/screens/router.js?v=1.3"></script>
    <script src="assets/screens/auth.js?v=1.3"></script>
    <script src="assets/screens/lobby.js?v=1.3"></script>
    <script src="assets/screens/game.js?v=1.3"></script>
    <script src="assets/screens/results.js?v=1.3"></script>
    <script src="assets/screens/history.js?v=1.3"></script>
    <script src="assets/screens/leaderboard.js?v=1.3"></script>
</body>
</html>