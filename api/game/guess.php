<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $roomCode = $data['room_code'] ?? null;
    $guessedNumber = $data['guess'] ?? null;
    $selectedCategory = $data['category'] ?? '1-20';

    if (!$roomCode || $guessedNumber === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Room code and guess required']);
        exit;
    }

    try {
        // Get room
        $roomStmt = $db->prepare('SELECT * FROM game_sessions WHERE room_code = ?');
        $roomStmt->execute([$roomCode]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found']);
            exit;
        }

        // Get category cost
        $categoryCost = ($selectedCategory === '1-20') ? 0 : 10;

        // Check if user has enough gems for paid category
        $userStmt = $db->prepare('SELECT total_gems FROM users WHERE id = ?');
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userGems = $user['total_gems'] ?? 0;

        if ($categoryCost > 0 && $userGems < $categoryCost) {
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient gems. This category costs ' . $categoryCost . ' gems.']);
            exit;
        }

        // Deduct gems if paid category (BEFORE generating secret number)
        if ($categoryCost > 0) {
            $deductStmt = $db->prepare('UPDATE users SET total_gems = total_gems - ? WHERE id = ?');
            $deductStmt->execute([$categoryCost, $_SESSION['user_id']]);
        }

        // Get guess count FIRST (needed for round calculation and adaptive patterns)
        $guessCountStmt = $db->prepare('SELECT COUNT(*) as count FROM guesses WHERE session_id = ?');
        $guessCountStmt->execute([$room['id']]);
        $guessCount = $guessCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Calculate current round
        $currentRound = ceil(($guessCount + 1) / 2);

        // Check if disabled numbers need updating based on cycle
        $newDisabled = calculateDisabledNumbers($guessCount);
        $previousDisabledJson = $room['disabled_numbers'];
        $previousDisabled = $previousDisabledJson ? json_decode($previousDisabledJson, true) : [];
        
        // Determine if current round is disabled
        $isDisabledRound = ($newDisabled !== null);
        
        // Compare: should we update the database?
        $shouldUpdate = false;
        
        if ($newDisabled === null && !empty($previousDisabled)) {
            $shouldUpdate = true;
            $disabledNumbers = [];
        } elseif ($newDisabled !== null && empty($previousDisabled)) {
            $shouldUpdate = true;
            $disabledNumbers = $newDisabled;
        } elseif ($newDisabled !== null && !empty($previousDisabled)) {
            $previousRound = $room['round_disabled_at'] ?? 0;
            if ($previousRound != $currentRound) {
                $shouldUpdate = true;
                $disabledNumbers = $newDisabled;
            }
        }
        
        // Update database if needed
        if ($shouldUpdate) {
            if ($disabledNumbers && count($disabledNumbers) > 0) {
                $disabledJson = json_encode($disabledNumbers);
                $updateStmt = $db->prepare('UPDATE game_sessions SET disabled_numbers = ?, round_disabled_at = ? WHERE id = ?');
                $updateStmt->execute([$disabledJson, $currentRound, $room['id']]);
            } else {
                $updateStmt = $db->prepare('UPDATE game_sessions SET disabled_numbers = NULL, round_disabled_at = 0 WHERE id = ?');
                $updateStmt->execute([$room['id']]);
                $disabledNumbers = [];
            }
        } else {
            $disabledNumbers = !empty($previousDisabled) ? $previousDisabled : [];
        }

        // Get disabled numbers and available numbers for this session
        $availableNumbers = getAvailableNumbers($disabledNumbers);

        // Get last secret number for smart randomization
        $lastGuessStmt = $db->prepare('
            SELECT secret_number FROM guesses
            WHERE session_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ');
        $lastGuessStmt->execute([$room['id']]);
        $lastGuess = $lastGuessStmt->fetch(PDO::FETCH_ASSOC);
        $lastSecretNumber = $lastGuess ? $lastGuess['secret_number'] : null;

        // Build game state for adaptive pattern generation
        $gameState = [
            'guess_count' => $guessCount,
            'current_round' => $currentRound,
            'is_disabled_round' => $isDisabledRound,
            'disabled_numbers' => $disabledNumbers
        ];

        // Generate smart random secret number using ADAPTIVE pattern
        $secretNumber = generateSmartRandomNumber($lastSecretNumber, $availableNumbers, 'adaptive', $gameState);

        // Check if guess is correct
        $isCorrect = ($guessedNumber == $secretNumber) ? 1 : 0;

        // Calculate gem reward
        $gemReward = 0;
        if ($isCorrect) {
            $gemReward = ($selectedCategory === '1-20') ? 10 : 20;
            // Award gems
            $awardStmt = $db->prepare('UPDATE users SET total_gems = total_gems + ? WHERE id = ?');
            $awardStmt->execute([$gemReward, $_SESSION['user_id']]);
        }

        // Record the guess with category info
        $guessStmt = $db->prepare('
            INSERT INTO guesses (session_id, player_id, secret_number, guessed_number, is_correct, selected_category, category_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $guessStmt->execute([$room['id'], $_SESSION['user_id'], $secretNumber, $guessedNumber, $isCorrect, $selectedCategory, $categoryCost]);

        // Get updated gem balance
        $userStmt = $db->prepare('SELECT total_gems FROM users WHERE id = ?');
        $userStmt->execute([$_SESSION['user_id']]);
        $updatedUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'guess' => [
                'guessed_number' => $guessedNumber,
                'secret_number' => $secretNumber,
                'is_correct' => $isCorrect,
                'category' => $selectedCategory,
                'category_cost' => $categoryCost,
                'gem_reward' => $gemReward,
                'gems_balance' => $updatedUser['total_gems'] ?? 0
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

