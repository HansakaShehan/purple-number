<?php
require_once 'db.php';
require_once 'config.php';

$db = Database::getInstance()->getPDO();

echo "=== DATABASE DIAGNOSTIC ===\n\n";

// Check users
$users = $db->query('SELECT id, username, total_gems FROM users')->fetchAll(PDO::FETCH_ASSOC);
echo "Users in DB (" . count($users) . "):\n";
foreach ($users as $u) {
    echo "  - ID: " . $u['id'] . " | Username: " . $u['username'] . " | Gems: " . $u['total_gems'] . "\n";
}

echo "\n";

// Check game sessions
$sessions = $db->query('SELECT id, room_code, player1_id, player2_id, status FROM game_sessions LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
echo "Game Sessions (" . count($sessions) . "):\n";
foreach ($sessions as $s) {
    $p2 = $s['player2_id'] ? $s['player2_id'] : 'NULL';
    echo "  - Room: " . $s['room_code'] . " | P1: " . $s['player1_id'] . " | P2: " . $p2 . " | Status: " . $s['status'] . "\n";
}

echo "\nPHP SESSION:\n";
echo "  - user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "  - username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "\nIssue: If a game_session has a player1_id or player2_id that doesn't exist in users table,\nthat's causing the foreign key constraint error when joining rooms.\n";
?>
