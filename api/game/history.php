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
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get room info
    $roomStmt = $db->prepare('SELECT id FROM game_sessions WHERE room_code = ?');
    $roomStmt->execute([$roomCode]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    // Get all guesses for this session with player names and category info
    $guessesStmt = $db->prepare('
        SELECT 
            g.guessed_number,
            g.secret_number,
            g.is_correct,
            g.selected_category,
            g.category_cost,
            u.username,
            g.created_at
        FROM guesses g
        JOIN users u ON g.player_id = u.id
        WHERE g.session_id = ?
        ORDER BY g.created_at ASC
    ');
    $guessesStmt->execute([$room['id']]);
    $guesses = $guessesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format guesses with round numbers and category info
    $history = [];
    foreach ($guesses as $index => $guess) {
        $history[] = [
            'round' => $index + 1,
            'player' => $guess['username'],
            'guessed_number' => (int)$guess['guessed_number'],
            'secret_number' => (int)$guess['secret_number'],
            'is_correct' => (int)$guess['is_correct'],
            'selected_category' => $guess['selected_category'] ?? '1-10',
            'category_cost' => (int)($guess['category_cost'] ?? 0),
            'created_at' => $guess['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
