<?php
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
