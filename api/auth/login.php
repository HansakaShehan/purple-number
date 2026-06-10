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

    try {
        $stmt = $db->prepare('SELECT id, username, password_hash, is_admin FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'is_admin' => $user['is_admin']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
