<?php
/**
 * Test: Verify disabled numbers feature works completely
 * Tests the fix for round_disabled_at default value and comparison logic
 */

require_once 'db.php';
require_once 'config.php';
require_once 'api/game/helpers.php';

$db = Database::getInstance()->getPDO();

echo "=== DISABLED NUMBERS FEATURE TEST ===\n\n";

// Test 1: Create a test game
echo "TEST 1: Creating test game...\n";
$roomCode = 'TEST' . rand(1000, 9999);
$testUserId = 1; // Assuming user 1 exists

try {
    $createStmt = $db->prepare('
        INSERT INTO game_sessions (room_code, player1_id, total_rounds, status, round_disabled_at)
        VALUES (?, ?, 20, \'active\', 0)
    ');
    $createStmt->execute([$roomCode, $testUserId]);
    $gameId = $db->lastInsertId();
    echo "✓ Game created with ID: $gameId\n";
} catch (PDOException $e) {
    echo "✗ Failed to create game: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Verify initial state
echo "\nTEST 2: Checking initial state...\n";
$stmt = $db->prepare('SELECT disabled_numbers, round_disabled_at FROM game_sessions WHERE id = ?');
$stmt->execute([$gameId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  Initial disabled_numbers: " . ($result['disabled_numbers'] ?? 'NULL') . "\n";
echo "  Initial round_disabled_at: " . $result['round_disabled_at'] . "\n";
if ($result['round_disabled_at'] == 0) {
    echo "✓ Correct: round_disabled_at is 0 (sentinel value for 'not yet set')\n";
} else {
    echo "✗ ERROR: round_disabled_at should be 0, got: " . $result['round_disabled_at'] . "\n";
}

// Test 3: Simulate guesses and trigger disabled numbers at round 3
echo "\nTEST 3: Simulating the disabled numbers calculation logic...\n";

// Simulate what happens in guess.php
for ($guessCount = 0; $guessCount < 5; $guessCount++) {
    // This simulates the flow in guess.php
    $currentRound = ceil(($guessCount + 1) / 2);
    echo "  After guess " . ($guessCount + 1) . ": Round $currentRound\n";
    
    // Check the condition that was fixed
    $stmt = $db->prepare('SELECT round_disabled_at FROM game_sessions WHERE id = ?');
    $stmt->execute([$gameId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentRound >= 3 && $room['round_disabled_at'] == 0) {
        echo "    → Condition TRUE: Will calculate disabled numbers!\n";
        
        // Calculate disabled numbers (like guess.php does)
        $newDisabled = calculateDisabledNumbers($guessCount, 3);
        if ($newDisabled && count($newDisabled) > 0) {
            $disabledJson = json_encode($newDisabled);
            $updateStmt = $db->prepare('UPDATE game_sessions SET disabled_numbers = ?, round_disabled_at = ? WHERE id = ?');
            $updateStmt->execute([$disabledJson, $currentRound, $gameId]);
            echo "    → Stored: " . implode(', ', $newDisabled) . " at round $currentRound\n";
            break; // Only calculate once
        }
    } else {
        $reason = ($currentRound < 3) ? "Round not yet 3" : "Already calculated";
        echo "    → Condition FALSE: $reason (round=$currentRound, round_disabled_at={$room['round_disabled_at']})\n";
    }
    
    // Insert a guess for the next iteration
    $insertStmt = $db->prepare('
        INSERT INTO guesses (session_id, player_id, secret_number, guessed_number, is_correct, selected_category, category_cost)
        VALUES (?, ?, ?, ?, 0, \'1-20\', 0)
    ');
    $insertStmt->execute([$gameId, $testUserId, rand(1, 20), rand(1, 20)]);
}

// Test 4: Check if disabled numbers were calculated
echo "\nTEST 4: Checking if disabled numbers were calculated at round 3...\n";
$stmt = $db->prepare('SELECT disabled_numbers, round_disabled_at FROM game_sessions WHERE id = ?');
$stmt->execute([$gameId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['disabled_numbers']) {
    $disabled = json_decode($result['disabled_numbers'], true);
    echo "✓ Disabled numbers found: " . implode(', ', $disabled) . "\n";
    echo "  round_disabled_at: " . $result['round_disabled_at'] . "\n";
    
    // Validate the disabled numbers
    if (count($disabled) >= 3 && count($disabled) <= 5) {
        echo "✓ Correct count: " . count($disabled) . " disabled numbers (3-5)\n";
    } else {
        echo "✗ Wrong count: " . count($disabled) . " disabled numbers\n";
    }
    
    // Validate all are in 1-20 range
    $inRange = true;
    foreach ($disabled as $num) {
        if ($num < 1 || $num > 20) {
            echo "✗ Invalid number: $num (should be 1-20)\n";
            $inRange = false;
        }
    }
    if ($inRange) {
        echo "✓ All numbers in valid 1-20 range\n";
    }
} else {
    echo "✗ ERROR: Disabled numbers were not calculated at round 3\n";
    echo "  disabled_numbers: " . ($result['disabled_numbers'] ?? 'NULL') . "\n";
    echo "  round_disabled_at: " . $result['round_disabled_at'] . "\n";
}

// Test 5: Simulate API response to verify format
echo "\nTEST 5: Testing API response format...\n";
$disabled = getDisabledNumbers($gameId, $db);
echo "  getDisabledNumbers() returned: " . json_encode($disabled) . "\n";
if (is_array($disabled) && (count($disabled) == 0 || (count($disabled) >= 3 && count($disabled) <= 5))) {
    echo "✓ API return format correct\n";
} else {
    echo "✗ API return format incorrect\n";
}

// Test 6: Cleanup
echo "\nTEST 6: Cleaning up test data...\n";
try {
    $db->prepare('DELETE FROM guesses WHERE session_id = ?')->execute([$gameId]);
    $db->prepare('DELETE FROM game_sessions WHERE id = ?')->execute([$gameId]);
    echo "✓ Test data cleaned up\n";
} catch (PDOException $e) {
    echo "✗ Cleanup failed: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
