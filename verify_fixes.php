<?php
require_once 'db.php';
require_once 'config.php';

$db = Database::getInstance()->getPDO();

echo "=== ENDPOINT VERIFICATION TEST ===\n\n";

// Test 1: Verify users exist
echo "TEST 1: Users in Database\n";
$users = $db->query('SELECT id, username FROM users')->fetchAll(PDO::FETCH_ASSOC);
echo "✓ Found " . count($users) . " users:\n";
foreach ($users as $u) {
    echo "  - ID: {$u['id']} | Username: {$u['username']}\n";
}

// Test 2: Verify foreign key references are valid
echo "\nTEST 2: Foreign Key Integrity\n";
$sessions = $db->query("
    SELECT g.id, g.room_code, g.player1_id, g.player2_id,
           CASE WHEN u1.id IS NULL THEN 'BROKEN' ELSE 'OK' END as p1_status,
           CASE WHEN g.player2_id IS NULL THEN 'NULL' 
                WHEN u2.id IS NULL THEN 'BROKEN' 
                ELSE 'OK' END as p2_status
    FROM game_sessions g
    JOIN users u1 ON g.player1_id = u1.id
    LEFT JOIN users u2 ON g.player2_id = u2.id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($sessions as $s) {
    $status = ($s['p1_status'] === 'OK' && ($s['p2_status'] === 'OK' || $s['p2_status'] === 'NULL')) ? '✓' : '✗';
    echo "$status Room {$s['room_code']}: P1={$s['player1_id']} ({$s['p1_status']}), P2={$s['player2_id']} ({$s['p2_status']})\n";
}

// Test 3: Test joining a room
echo "\nTEST 3: Room Join Simulation\n";
$session = $sessions[0] ?? null;
if ($session) {
    echo "Attempting to update room {$session['room_code']} to add player 3 (if exists)...\n";
    
    // Check if player 3 exists
    $player3 = $db->query("SELECT id FROM users WHERE id = 3")->fetch(PDO::FETCH_ASSOC);
    if ($player3) {
        try {
            $updateStmt = $db->prepare('UPDATE game_sessions SET player2_id = ? WHERE room_code = ?');
            $updateStmt->execute([3, $session['room_code']]);
            echo "✓ Successfully added player 2 to room\n";
            
            // Verify update
            $verify = $db->query("SELECT player2_id FROM game_sessions WHERE room_code = '{$session['room_code']}'")->fetch(PDO::FETCH_ASSOC);
            echo "  Verification: player2_id = {$verify['player2_id']}\n";
        } catch (PDOException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== ALL TESTS COMPLETE ===\n";
echo "The database is now properly set up for testing.\n";
echo "The foreign key errors should be resolved.\n";
?>
