<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $roomCode = $data['room_code'] ?? null;

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

        // Record a skip/pass as a guess with no selection
        // This advances the turn counter but doesn't award/deduct anything
        $skipStmt = $db->prepare('
            INSERT INTO guesses (session_id, player_id, secret_number, guessed_number, is_correct, selected_category, category_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        // Use 0 for all values to indicate a skip/pass
        $skipStmt->execute([$room['id'], $_SESSION['user_id'], 0, 0, 0, 'skip', 0]);

        // Get updated game state
        $guessCountStmt = $db->prepare('SELECT COUNT(*) as count FROM guesses WHERE session_id = ?');
        $guessCountStmt->execute([$room['id']]);
        $guessCount = $guessCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Determine next turn player
        $nextTurn = ($guessCount % 2 === 0) ? ($room['player2_id'] ?? $room['player1_id']) : $room['player1_id'];

        echo json_encode([
            'success' => true,
            'message' => 'Turn skipped, advancing to next player',
            'next_turn' => $nextTurn
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
