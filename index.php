<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Purple Guess — Multiplayer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="app">
        <!-- LOGIN SCREEN -->
        <div id="login-screen" class="screen active">
            <header class="hero">
                <h1 class="title">Purple Guess</h1>
                <p class="subtitle">Multiplayer number guessing battle</p>
            </header>

            <section class="card">
                <div class="auth-container">
                    <div class="auth-tabs">
                        <button class="tab-btn active" data-tab="login-form">Login</button>
                        <button class="tab-btn" data-tab="register-form">Register</button>
                    </div>

                    <!-- Login Form -->
                    <form id="login-form" class="auth-form active">
                        <input type="text" id="login-username" placeholder="Username" required>
                        <input type="password" id="login-password" placeholder="Password" required>
                        <button type="submit" class="btn primary full-width">Login</button>
                        <div id="login-error" class="error-message"></div>
                    </form>

                    <!-- Register Form -->
                    <form id="register-form" class="auth-form">
                        <input type="text" id="register-username" placeholder="Username (3+ chars)" required>
                        <input type="password" id="register-password" placeholder="Password (6+ chars)" required>
                        <button type="submit" class="btn primary full-width">Register</button>
                        <div id="register-error" class="error-message"></div>
                    </form>
                </div>
            </section>

            <footer class="footer">Built with ❤️ — Purple theme</footer>
        </div>

        <!-- LOBBY SCREEN -->
        <div id="lobby-screen" class="screen">
            <header class="hero">
                <h1 class="title">Purple Guess</h1>
                <div class="user-info">
                    <span id="user-display">User</span>
                    <button id="logout-btn" class="btn secondary small">Logout</button>
                </div>
            </header>

            <section class="card">
                <div class="lobby-container">
                    <div class="lobby-section">
                        <h2>Create Room</h2>
                        <button id="create-room-btn" class="btn primary">Create New Room</button>
                        <div id="created-room" class="hidden">
                            <p>Room Code: <strong id="room-code-display">—</strong></p>
                            <button id="copy-code-btn" class="btn secondary">Copy Code</button>
                            <p class="muted">Waiting for opponent to join...</p>
                        </div>
                    </div>

                    <div class="lobby-section">
                        <h2>Join Room</h2>
                        <input type="text" id="join-code-input" placeholder="Enter room code" maxlength="4">
                        <button id="join-room-btn" class="btn primary">Join Room</button>
                        <div id="join-error" class="error-message"></div>
                    </div>

                    <div id="admin-panel" class="lobby-section hidden">
                        <h2>⚙️ Admin Settings</h2>
                        <label>Total Rounds (5-100)</label>
                        <input type="number" id="rounds-count-input" min="5" max="100" value="20">
                        <button id="save-config-btn" class="btn primary">Save Settings</button>
                        <div id="admin-message" class="message"></div>
                    </div>
                </div>
            </section>

            <footer class="footer">Built with ❤️ — Purple theme</footer>
        </div>

        <!-- GAME SCREEN -->
        <div id="game-screen" class="screen">
            <header class="hero">
                <h1 class="title">Purple Guess — Battle</h1>
                <div class="game-timer">
                    <span id="game-time">5:00</span>
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
                    <div class="number-grid">
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
                    </div>
                    <button id="submitBtn" class="btn" disabled>Send Guess</button>
                </div>
                <div class="selected-display">Selected: <span id="selectedNumber">—</span></div>

                <!-- Result Display -->
                <div id="result" class="result hidden">
                    <div class="reveal">Your guess: <span id="guessedNumber">-</span></div>
                    <div class="reveal">Real number: <span id="realNumber">-</span></div>
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

            <footer class="footer">Built with ❤️ — Purple theme and animations</footer>
        </div>

        <!-- RESULTS SCREEN -->
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
                    </div>
                    <div class="result-row">
                        <div id="result-p1-name">Player 1</div>
                        <div id="result-p1-correct">0</div>
                        <div id="result-p1-misses">0</div>
                    </div>
                    <div class="result-row">
                        <div id="result-p2-name">Player 2</div>
                        <div id="result-p2-correct">0</div>
                        <div id="result-p2-misses">0</div>
                    </div>
                </div>

                <div class="results-actions">
                    <button id="play-again-btn" class="btn primary">Play Again</button>
                    <button id="lobby-btn" class="btn secondary">Back to Lobby</button>
                </div>
            </section>

            <footer class="footer">Built with ❤️ — Purple theme</footer>
        </div>
    </main>

    <!-- Scripts -->
    <script src="assets/app.js"></script>
    <script src="assets/screens/router.js"></script>
    <script src="assets/screens/auth.js"></script>
    <script src="assets/screens/lobby.js"></script>
    <script src="assets/screens/game.js"></script>
    <script src="assets/screens/results.js"></script>
</body>
</html>