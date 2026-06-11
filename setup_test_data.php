<?php
require_once 'db.php';
require_once 'config.php';

$db = Database::getInstance()->getPDO();

echo "=== CLEANUP & TEST SETUP ===\n\n";

// Option 1: Clean database
echo "Option 1: Clearing all old test data...\n";
$db->exec("DELETE FROM guesses");
$db->exec("DELETE FROM game_sessions");
$db->exec("DELETE FROM users");
echo "✓ All tables cleared\n\n";

// Create two test users
echo "Option 2: Creating test users...\n";
$user1Pass = password_hash('password123', PASSWORD_BCRYPT);
$user2Pass = password_hash('password123', PASSWORD_BCRYPT);

$stmt = $db->prepare('INSERT INTO users (username, password_hash, total_gems) VALUES (?, ?, ?)');
$stmt->execute(['player1', $user1Pass, 100]);
$userId1 = $db->lastInsertId();

$stmt->execute(['player2', $user2Pass, 100]);
$userId2 = $db->lastInsertId();

echo "✓ Created Player 1 (ID: $userId1) - username: player1, password: password123\n";
echo "✓ Created Player 2 (ID: $userId2) - username: player2, password: password123\n\n";

// Create a test room
echo "Option 3: Creating test game room...\n";
$roomCode = 'TEST' . rand(1000, 9999);
$stmt = $db->prepare('INSERT INTO game_sessions (room_code, player1_id, status) VALUES (?, ?, ?)');
$stmt->execute([$roomCode, $userId1, 'waiting']);
echo "✓ Created room: $roomCode\n";
echo "   Player 1 (ID: $userId1) - room creator\n";
echo "   Waiting for Player 2 to join...\n\n";

// Verification
echo "=== VERIFICATION ===\n";
$users = $db->query('SELECT id, username, total_gems FROM users')->fetchAll(PDO::FETCH_ASSOC);
echo "Users in DB (" . count($users) . "):\n";
foreach ($users as $u) {
    echo "  - ID: " . $u['id'] . " | Username: " . $u['username'] . " | Gems: " . $u['total_gems'] . "\n";
}

echo "\nGame Sessions:\n";
$sessions = $db->query('SELECT id, room_code, player1_id, player2_id, status FROM game_sessions')->fetchAll(PDO::FETCH_ASSOC);
foreach ($sessions as $s) {
    $p2 = $s['player2_id'] ? $s['player2_id'] : 'NULL';
    echo "  - Room: " . $s['room_code'] . " | P1: " . $s['player1_id'] . " | P2: " . $p2 . " | Status: " . $s['status'] . "\n";
}

echo "\n=== HOW TO TEST ===\n";
echo "1. Open browser to http://localhost:8000/\n";
echo "2. Login as 'player1' with password 'password123'\n";
echo "3. Open the room $roomCode in the lobby\n";
echo "4. In another browser tab/window, login as 'player2' with password 'password123'\n";
echo "5. Try to join room $roomCode\n";
echo "6. Check console for any errors\n";
?>
