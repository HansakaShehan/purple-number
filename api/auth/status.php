<?php
require_once __DIR__ . '/../../config.php';

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'is_admin' => $_SESSION['is_admin'] ?? 0
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
