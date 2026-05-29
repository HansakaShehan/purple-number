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
                <div class="timer" id="timer">
                    <div class="timer-bar" id="timerBar"></div>
                </div>
            </div>

            <div class="play-area">
                <input id="guessInput" type="number" min="1" max="10" placeholder="Enter 1-10" disabled>
                <button id="submitBtn" class="btn" disabled>Send Guess</button>
            </div>

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