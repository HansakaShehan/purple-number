<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate a unique room code (4 characters)
        $roomCode = strtoupper(substr(uniqid(), -4));
        
        // Get admin config for game rounds
        $configStmt = $db->query('SELECT rounds_count FROM admin_config WHERE id = 1');
        $config = $configStmt->fetch(PDO::FETCH_ASSOC);
        $totalRounds = $config['rounds_count'] ?? 20;

        $stmt = $db->prepare('
            INSERT INTO game_sessions (room_code, player1_id, total_rounds, status)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$roomCode, $_SESSION['user_id'], $totalRounds, 'waiting']);

        echo json_encode([
            'success' => true,
            'room' => [
                'code' => $roomCode,
                'player1_id' => $_SESSION['user_id'],
                'duration_seconds' => $duration,
                'status' => 'waiting'
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create room']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
