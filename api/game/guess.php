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
    $freeGuess = $data['free_guess'] ?? null;  // NEW: FREE category guess (1-10)
    $gemCategory = $data['gem_category'] ?? null;  // NEW: GEM category (ODD/EVEN/range)

    if (!$roomCode || ($freeGuess === null && $gemCategory === null)) {
        http_response_code(400);
        echo json_encode(['error' => 'Room code and at least free_guess or gem_category required']);
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

        // Calculate costs
        $freeCost = 0;  // FREE category is always free
        $gemCost = ($gemCategory) ? 10 : 0;  // GEM categories cost 10 gems
        $totalCost = $freeCost + $gemCost;

        // Check if user has enough gems for GEM category
        $userStmt = $db->prepare('SELECT total_gems FROM users WHERE id = ?');
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userGems = $user['total_gems'] ?? 0;

        if ($totalCost > 0 && $userGems < $totalCost) {
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient gems. Need ' . $totalCost . ', have ' . $userGems]);
            exit;
        }

        // Deduct gems if GEM category selected (BEFORE generating secret)
        if ($gemCost > 0) {
            $deductStmt = $db->prepare('UPDATE users SET total_gems = total_gems - ? WHERE id = ?');
            $deductStmt->execute([$gemCost, $_SESSION['user_id']]);
        }

        // Validate FREE guess if provided
        if ($freeGuess !== null) {
            $freeGuess = (int)$freeGuess;
            if ($freeGuess < 1 || $freeGuess > 10) {
                http_response_code(400);
                echo json_encode(['error' => 'FREE guess must be between 1 and 10']);
                exit;
            }
        }

        // Validate GEM category if provided
        if ($gemCategory !== null) {
            $validGemCategories = getValidGemCategoryNames();
            if (!in_array($gemCategory, $validGemCategories, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid GEM category']);
                exit;
            }

            $disabledGemCategories = getDisabledGemCategories($db);
            if (in_array($gemCategory, $disabledGemCategories, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'This gem category is currently disabled by admin']);
                exit;
            }
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

        // NEW: No constraint validation needed
        // FREE guess (1-10) is independent from GEM category
        // Both are validated above individually

        // Get disabled numbers and available numbers for this session
        $availableNumbers = getAvailableNumbers($disabledNumbers);

        // For dual evaluation, use available numbers (not constrained to specific category)
        // Secret can be any available number 1-10
        if (empty($availableNumbers)) {
            $availableNumbers = range(1, 10);
        }

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

        // Check if this is a hint round and if player has hint bias
        $currentPlayerHint = null;
        if ($currentRound % 5 === 0) {
            // Get hint for current player (cached from session or generate new)
            $hintKey = 'hint_round_' . $currentRound . '_player_' . $_SESSION['user_id'];
            if (!isset($_SESSION[$hintKey])) {
                // 60% chance to get hint
                if (rand(1, 100) <= 60) {
                    $_SESSION[$hintKey] = rand(1, 100) <= 50 ? 'even' : 'odd';
                }
            }
            $currentPlayerHint = $_SESSION[$hintKey] ?? null;
        }

        // Bias available numbers based on hint (80% probability)
        $biasedNumbers = $availableNumbers;
        if ($currentPlayerHint) {
            if ($currentPlayerHint === 'even') {
                $evenNumbers = array_filter($availableNumbers, fn($n) => $n % 2 === 0);
                $oddNumbers = array_filter($availableNumbers, fn($n) => $n % 2 === 1);
                
                // 80% chance to pick from even, 20% from odd
                if (rand(1, 100) <= 80 && !empty($evenNumbers)) {
                    $biasedNumbers = $evenNumbers;
                } else {
                    $biasedNumbers = $oddNumbers;
                }
            } elseif ($currentPlayerHint === 'odd') {
                $evenNumbers = array_filter($availableNumbers, fn($n) => $n % 2 === 0);
                $oddNumbers = array_filter($availableNumbers, fn($n) => $n % 2 === 1);
                
                // 80% chance to pick from odd, 20% from even
                if (rand(1, 100) <= 80 && !empty($oddNumbers)) {
                    $biasedNumbers = $oddNumbers;
                } else {
                    $biasedNumbers = $evenNumbers;
                }
            }
        }

        // Generate ONE secret number (used for both FREE and GEM evaluation)
        // Use biased numbers if hint is active, otherwise use all available
        $secretNumber = generateSmartRandomNumber($lastSecretNumber, $biasedNumbers, 'adaptive', $gameState);

        // NEW: Evaluate BOTH FREE and GEM categories independently
        $freeIsCorrect = 0;
        $gemIsCorrect = 0;
        $totalGemReward = 0;

        // Check FREE guess (1-20)
        if ($freeGuess !== null) {
            $freeIsCorrect = ($freeGuess == $secretNumber) ? 1 : 0;
            if ($freeIsCorrect) {
                $totalGemReward += 10;  // FREE reward: +10
                $awardStmt = $db->prepare('UPDATE users SET total_gems = total_gems + ? WHERE id = ?');
                $awardStmt->execute([10, $_SESSION['user_id']]);
            }
        }

        // Check GEM category
        if ($gemCategory !== null) {
            $gemIsCorrect = isInCategory($secretNumber, $gemCategory) ? 1 : 0;
            if ($gemIsCorrect) {
                $totalGemReward += 20;  // GEM reward: +20
                $awardStmt = $db->prepare('UPDATE users SET total_gems = total_gems + ? WHERE id = ?');
                $awardStmt->execute([20, $_SESSION['user_id']]);
            }
        }

        // Record the guess with BOTH FREE and GEM info
        // Store as JSON for flexibility
        $guessData = json_encode([
            'free_guess' => $freeGuess,
            'gem_category' => $gemCategory
        ]);
        
        $guessStmt = $db->prepare('
            INSERT INTO guesses (session_id, player_id, secret_number, guessed_number, is_correct, selected_category, category_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        // For backward compatibility, use free_guess as primary guessed_number, and gem_category as selected_category
        $primaryGuess = $freeGuess ?? ($gemCategory ?? 0);
        $primaryCategory = $gemCategory ?? ($freeGuess ? '1-10' : null);
        $primaryCost = ($gemCategory) ? 10 : 0;
        $primaryIsCorrect = ($freeGuess !== null && $freeIsCorrect) ? 1 : (($gemCategory !== null && $gemIsCorrect) ? 1 : 0);
        
        $guessStmt->execute([$room['id'], $_SESSION['user_id'], $secretNumber, $primaryGuess, $primaryIsCorrect, $primaryCategory, $primaryCost]);

        // Get updated gem balance
        $userStmt = $db->prepare('SELECT total_gems FROM users WHERE id = ?');
        $userStmt->execute([$_SESSION['user_id']]);
        $updatedUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'guess' => [
                'free_guess' => $freeGuess,
                'free_is_correct' => $freeIsCorrect,
                'gem_category' => $gemCategory,
                'gem_is_correct' => $gemIsCorrect,
                'guessed_number' => $primaryGuess,
                'secret_number' => $secretNumber,
                'is_correct' => $primaryIsCorrect,
                'category_cost' => $primaryCost,
                'gem_reward' => $totalGemReward,
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

