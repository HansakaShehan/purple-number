<?php
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }

    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be at least 3 characters']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit;
    }

    try {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $passwordHash]);

        $userId = $db->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = 0;

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'is_admin' => 0
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(409);
        echo json_encode(['error' => 'Username already exists']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
