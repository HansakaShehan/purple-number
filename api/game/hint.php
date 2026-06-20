<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $roomCode = $_GET['room_code'] ?? null;

    if (!$roomCode) {
        http_response_code(400);
        echo json_encode(['error' => 'Room code required']);
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

        // Get guess count for CURRENT PLAYER to determine their round
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'User ID not in session']);
            exit;
        }
        
        $guessCountStmt = $db->prepare('SELECT COUNT(*) as count FROM guesses WHERE session_id = ? AND player_id = ?');
        $guessCountStmt->execute([$room['id'], $userId]);
        $guessCount = $guessCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Calculate current round for this player - this is their personal round number
        // Round 1 = after 1st guess, Round 2 = after 2nd guess, Round 3 = after 3rd guess, etc
        // But we want to check BEFORE they make the next guess, so add 1
        $currentRound = $guessCount + 1;

        // Check if this is a hint round (every 2 rounds for testing: 2, 4, 6, etc)
        // In production, change to: ($currentRound % 3 === 0) for rounds 3, 6, 9
        $isHintRound = ($currentRound % 2 === 0);

        $hint = null;

        if ($isHintRound) {
            // Use session to store player hints and persist across requests
            $hintKey = 'hint_round_' . $currentRound . '_player_' . $userId;
            
            // Check if hint already generated for this player this round
            if (!isset($_SESSION[$hintKey])) {
                // Random chance (100%) to get a hint - for testing
                $getHint = rand(1, 100) <= 100;

                if ($getHint) {
                    // Each player gets random hint type
                    $_SESSION[$hintKey] = rand(1, 100) <= 50 ? 'even' : 'odd';
                }
            }

            if (isset($_SESSION[$hintKey])) {
                $hintType = $_SESSION[$hintKey];
                $hint = [
                    'type' => $hintType,
                    'probability' => 80,
                    'message' => $hintType === 'even' ? 'Secret number is likely EVEN' : 'Secret number is likely ODD'
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'hint' => $hint,
            'current_round' => $currentRound,
            'is_hint_round' => $isHintRound,
            'guess_count' => $guessCount,
            'debug' => 'Player ' . $userId . ' at round ' . $currentRound . ' (guessCount=' . $guessCount . ')'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Hint.php error: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
