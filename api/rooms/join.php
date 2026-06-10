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
        $stmt = $db->prepare('SELECT * FROM game_sessions WHERE room_code = ?');
        $stmt->execute([$roomCode]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found']);
            exit;
        }

        if ($room['player1_id'] == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot join your own room']);
            exit;
        }

        if ($room['player2_id'] !== null) {
            http_response_code(409);
            echo json_encode(['error' => 'Room is full']);
            exit;
        }

        // Update room with player2
        $updateStmt = $db->prepare('UPDATE game_sessions SET player2_id = ?, status = ? WHERE room_code = ?');
        $updateStmt->execute([$_SESSION['user_id'], 'ready', $roomCode]);

        echo json_encode([
            'success' => true,
            'room' => [
                'code' => $room['room_code'],
                'player1_id' => $room['player1_id'],
                'player2_id' => $_SESSION['user_id'],
                'duration_seconds' => $room['duration_seconds'],
                'status' => 'ready'
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
