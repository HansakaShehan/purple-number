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
    $guessedNumber = $data['guess'] ?? null;

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

        // Generate a new secret number for this round (1-10)
        // Each round gets a fresh secret number - no reuse across turns
        $secretNumber = rand(1, 10);

        // Check if guess is correct
        $isCorrect = ($guessedNumber == $secretNumber) ? 1 : 0;

        // Record the guess
        $guessStmt = $db->prepare('
            INSERT INTO guesses (session_id, player_id, secret_number, guessed_number, is_correct)
            VALUES (?, ?, ?, ?, ?)
        ');
        $guessStmt->execute([$room['id'], $_SESSION['user_id'], $secretNumber, $guessedNumber, $isCorrect]);

        echo json_encode([
            'success' => true,
            'guess' => [
                'guessed_number' => $guessedNumber,
                'secret_number' => $secretNumber,
                'is_correct' => $isCorrect
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
