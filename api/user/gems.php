<?php
session_start();
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated', 'success' => false]);
    exit;
}

try {
    $pdo = Database::getInstance()->getPDO();
    $stmt = $pdo->prepare('SELECT total_gems FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'gems' => (int)$user['total_gems']
        ]);
    } else {
        // User session exists but user not in database - session is invalid
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User session invalid. Please log in again.']);
        // Clear invalid session
        session_destroy();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
