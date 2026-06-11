<?php
session_start();
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = Database::getInstance()->getPDO();
        
        // Get rooms that are waiting or ready
        $stmt = $pdo->prepare('
            SELECT 
                g.id,
                g.room_code,
                g.player1_id,
                g.player2_id,
                g.status,
                u1.username as player1_username
            FROM game_sessions g
            JOIN users u1 ON g.player1_id = u1.id
            WHERE g.status IN ("waiting", "ready")
            ORDER BY g.created_at DESC
            LIMIT 20
        ');
        $stmt->execute();
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'rooms' => $rooms
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
