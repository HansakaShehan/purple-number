<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Purple Guessing Game</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="app">
        <header class="hero">
            <h1 class="title">Purple Guess</h1>
            <p class="subtitle">Guess the number (1-10) — 10s rounds. Good luck!</p>
        </header>

        <section class="game-card">
            <div class="controls">
                <button id="startBtn" class="btn primary">Start Round</button>
                <button id="audioToggleBtn" class="btn secondary">Toggle Audio</button>
                <div class="timer" id="timer">
                    <div class="timer-bar" id="timerBar"></div>
                </div>
            </div>

            <div class="play-area">
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
                <input id="guessInput" type="hidden">
                <button id="submitBtn" class="btn" disabled>Send Guess</button>
            </div>
            <div class="selected-display">Selected: <span id="selectedNumber">—</span></div>

            <div id="result" class="result hidden">
                <div class="reveal">Real number: <span id="realNumber">-</span></div>
                <div id="outcome" class="outcome"></div>
            </div>

            <div class="scoreboard">
                <div>Correct: <span id="correctCount">0</span></div>
                <div>Misses: <span id="missCount">0</span></div>
            </div>
        </section>

        <footer class="footer">Built with ❤️ — Purple theme and animations</footer>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>