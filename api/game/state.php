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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $roomStmt = $db->prepare('
        SELECT g.*, u1.username as player1_username, u2.username as player2_username
        FROM game_sessions g
        JOIN users u1 ON g.player1_id = u1.id
        LEFT JOIN users u2 ON g.player2_id = u2.id
        WHERE g.room_code = ?
    ');
    $roomStmt->execute([$roomCode]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    // Get stats for both players
    $p1StatsStmt = $db->prepare('
        SELECT 
            COUNT(*) as total_guesses,
            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
            SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect_count
        FROM guesses
        WHERE session_id = ? AND player_id = ?
    ');
    $p1StatsStmt->execute([$room['id'], $room['player1_id']]);
    $p1Stats = $p1StatsStmt->fetch(PDO::FETCH_ASSOC);

    $p2Stats = null;
    if ($room['player2_id']) {
        $p2StatsStmt = $db->prepare('
            SELECT 
                COUNT(*) as total_guesses,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect_count
            FROM guesses
            WHERE session_id = ? AND player_id = ?
        ');
        $p2StatsStmt->execute([$room['id'], $room['player2_id']]);
        $p2Stats = $p2StatsStmt->fetch(PDO::FETCH_ASSOC);
    }

    // Determine whose turn it is - Player 2 (joining player) goes first
    $guessCountStmt = $db->query('SELECT COUNT(*) as count FROM guesses WHERE session_id = ' . $room['id']);
    $guessCount = $guessCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    $currentTurn = ($guessCount % 2 === 0) ? ($room['player2_id'] ?? $room['player1_id']) : $room['player1_id'];

    // Get last guess for display to watching player
    $lastGuessStmt = $db->prepare('
        SELECT guessed_number, secret_number, is_correct, player_id
        FROM guesses
        WHERE session_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $lastGuessStmt->execute([$room['id']]);
    $lastGuess = $lastGuessStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'game' => [
            'room_code' => $room['room_code'],
            'status' => $room['status'],
            'start_time' => $room['start_time'],
            'total_rounds' => $room['total_rounds'],
            'total_guesses' => $guessCount,
            'winner_id' => $room['winner_id'],
            'current_turn' => $currentTurn,
            'last_guess' => $lastGuess ? [
                'guessed_number' => (int)$lastGuess['guessed_number'],
                'secret_number' => (int)$lastGuess['secret_number'],
                'is_correct' => (int)$lastGuess['is_correct'],
                'player_id' => (int)$lastGuess['player_id']
            ] : null,
            'players' => [
                [
                    'id' => $room['player1_id'],
                    'username' => $room['player1_username'],
                    'correct' => $p1Stats['correct_count'] ?? 0,
                    'incorrect' => $p1Stats['incorrect_count'] ?? 0
                ],
                $room['player2_id'] ? [
                    'id' => $room['player2_id'],
                    'username' => $room['player2_username'],
                    'correct' => $p2Stats['correct_count'] ?? 0,
                    'incorrect' => $p2Stats['incorrect_count'] ?? 0
                ] : null
            ]
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
