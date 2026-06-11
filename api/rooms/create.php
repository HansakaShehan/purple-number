<?php
session_start();
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getInstance()->getPDO();
        
        // Generate a unique room code (4 characters)
        $roomCode = strtoupper(substr(uniqid(), -4));
        
        // Get admin config for game rounds
        $configStmt = $pdo->prepare('SELECT rounds_count FROM admin_config WHERE id = 1');
        $configStmt->execute();
        $config = $configStmt->fetch(PDO::FETCH_ASSOC);
        $totalRounds = $config['rounds_count'] ?? 20;

        $stmt = $pdo->prepare('
            INSERT INTO game_sessions (room_code, player1_id, total_rounds, status)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$roomCode, $_SESSION['user_id'], $totalRounds, 'waiting']);

        echo json_encode([
            'success' => true,
            'room' => [
                'code' => $roomCode,
                'player1_id' => $_SESSION['user_id'],
                'status' => 'waiting'
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create room: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
