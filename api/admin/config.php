<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->query('SELECT rounds_count, turn_duration_seconds FROM admin_config WHERE id = 1');
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'config' => $config
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins can update
    if (($_SESSION['is_admin'] ?? 0) !== 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $roundsCount = $data['rounds_count'] ?? null;

    if ($roundsCount === null || $roundsCount < 5 || $roundsCount > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Rounds must be between 5 and 100']);
        exit;
    }

    try {
        $stmt = $db->prepare('UPDATE admin_config SET rounds_count = ? WHERE id = 1');
        $stmt->execute([$roundsCount]);

        echo json_encode([
            'success' => true,
            'config' => [
                'rounds_count' => $roundsCount
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
