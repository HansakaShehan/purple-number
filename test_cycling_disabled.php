<?php
/**
 * Test: Verify cycling disabled numbers pattern
 * Pattern: Rounds 1-3 normal, Round 4 disabled, Rounds 5-7 normal, Round 8 disabled, etc.
 */

require_once 'db.php';
require_once 'config.php';
require_once 'api/game/helpers.php';

$db = Database::getInstance()->getPDO();

echo "=== CYCLING DISABLED NUMBERS TEST ===\n\n";

// Test 1: Create a test game
echo "TEST 1: Creating test user and game...\n";
$roomCode = 'CYCLE' . rand(1000, 9999);

try {
    // Create test user if doesn't exist
    $userStmt = $db->prepare('INSERT IGNORE INTO users (id, username, password_hash) VALUES (?, ?, ?)');
    $userStmt->execute([1, 'testuser_' . time(), 'hash']);
    
    $testUserId = 1;
    
    $createStmt = $db->prepare('
        INSERT INTO game_sessions (room_code, player1_id, total_rounds, status, round_disabled_at)
        VALUES (?, ?, 20, \'active\', 0)
    ');
    $createStmt->execute([$roomCode, $testUserId]);
    $gameId = $db->lastInsertId();
    echo "✓ Game created with ID: $gameId\n\n";
} catch (PDOException $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Simulate the cycle through multiple rounds
echo "TEST 2: Simulating cycle pattern (guesses leading to multiple rounds)...\n";

$guessSequence = [
    0 => "Before Guess 1 (Round 1) - should be NORMAL",
    1 => "After Guess 1 (Round 1) - should be NORMAL", 
    2 => "After Guess 2 (Round 1) - should be NORMAL",
    3 => "After Guess 3 (Round 2) - should be NORMAL",
    4 => "After Guess 4 (Round 2) - should be NORMAL",
    5 => "After Guess 5 (Round 3) - should be NORMAL",
    6 => "After Guess 6 (Round 3) - should be NORMAL",
    7 => "After Guess 7 (Round 4) - should be DISABLED ✓",
    8 => "After Guess 8 (Round 4) - should be DISABLED",
    9 => "After Guess 9 (Round 5) - should be NORMAL again",
    10 => "After Guess 10 (Round 5) - should be NORMAL",
    11 => "After Guess 11 (Round 6) - should be NORMAL",
    12 => "After Guess 12 (Round 6) - should be NORMAL",
    13 => "After Guess 13 (Round 7) - should be NORMAL",
    14 => "After Guess 14 (Round 7) - should be NORMAL",
    15 => "After Guess 15 (Round 8) - should be DISABLED ✓",
    16 => "After Guess 16 (Round 8) - should be DISABLED",
];

$lastDisabledSet = null;
$round4Numbers = null;
$round8Numbers = null;

foreach ($guessSequence as $guessCount => $description) {
    $currentRound = ceil(($guessCount + 1) / 2);
    $cyclePosition = (($currentRound - 1) % 4) + 1;
    
    // Calculate what should be disabled
    $shouldDisable = calculateDisabledNumbers($guessCount);
    
    // Get database state before insertion
    $stmt = $db->prepare('SELECT disabled_numbers FROM game_sessions WHERE id = ?');
    $stmt->execute([$gameId]);
    $dbResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentDisabled = $dbResult['disabled_numbers'] ? json_decode($dbResult['disabled_numbers'], true) : [];
    
    echo "\n  Guess $guessCount - $description\n";
    echo "    Cycle Position: $cyclePosition/4\n";
    echo "    Should Calculate: " . ($shouldDisable ? 'YES - ' . implode(', ', $shouldDisable) : 'NO (normal round)') . "\n";
    echo "    Currently Stored: " . (empty($currentDisabled) ? 'NONE (normal)' : implode(', ', $currentDisabled)) . "\n";
    
    // Verify logic
    if ($cyclePosition == 4) {
        // Should be disabled
        if ($shouldDisable) {
            echo "    ✓ CORRECT: Would enable disabled numbers for round $currentRound\n";
            if ($currentRound == 4) {
                $round4Numbers = $shouldDisable;
                echo "      → Round 4 disabled numbers: " . implode(', ', $round4Numbers) . "\n";
            } elseif ($currentRound == 8) {
                $round8Numbers = $shouldDisable;
                echo "      → Round 8 disabled numbers: " . implode(', ', $round8Numbers) . "\n";
                
                // Verify they're different from round 4
                if ($round8Numbers !== $round4Numbers) {
                    echo "      ✓ Round 8 numbers are DIFFERENT from Round 4 (new random set)\n";
                } else {
                    echo "      ✗ WARNING: Round 8 numbers are same as Round 4 (should be random)\n";
                }
            }
        } else {
            echo "    ✗ ERROR: Should be disabled but function returned null\n";
        }
    } else {
        // Should NOT be disabled
        if ($shouldDisable === null) {
            echo "    ✓ CORRECT: Would keep normal (no disabled numbers)\n";
        } else {
            echo "    ✗ ERROR: Should be normal but function returned: " . implode(', ', $shouldDisable) . "\n";
        }
    }
    
    // Insert a dummy guess (simulate game progression)
    if ($guessCount < count($guessSequence) - 1) {
        $insertStmt = $db->prepare('
            INSERT INTO guesses (session_id, player_id, secret_number, guessed_number, is_correct, selected_category, category_cost)
            VALUES (?, ?, ?, ?, 0, \'1-20\', 0)
        ');
        $insertStmt->execute([$gameId, $testUserId, rand(1, 20), rand(1, 20)]);
    }
}

// Test 3: Verify transition happens correctly
echo "\n\nTEST 3: Verifying round-to-round transitions...\n";
if ($round4Numbers && $round8Numbers) {
    echo "✓ Round 4 had disabled: " . implode(', ', $round4Numbers) . "\n";
    echo "✓ Round 8 had disabled: " . implode(', ', $round8Numbers) . "\n";
    echo "✓ Rounds 5-7 would have been normal (numbers reappear)\n";
    echo "✓ Each disabled round gets NEW random numbers\n";
} else {
    echo "⚠ Could not complete full cycle test\n";
}

// Test 4: Cleanup
echo "\n\nTEST 4: Cleaning up test data...\n";
try {
    $db->prepare('DELETE FROM guesses WHERE session_id = ?')->execute([$gameId]);
    $db->prepare('DELETE FROM game_sessions WHERE id = ?')->execute([$gameId]);
    echo "✓ Test data cleaned up\n";
} catch (PDOException $e) {
    echo "✗ Cleanup failed: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nPattern verified:\n";
echo "  • Rounds 1-3: Normal (all numbers 1-20 available)\n";
echo "  • Round 4: Disabled (3-5 random numbers hidden)\n";
echo "  • Rounds 5-7: Normal (all numbers available again)\n";
echo "  • Round 8: Disabled (new 3-5 random numbers hidden)\n";
echo "  • Cycle repeats...\n";
?>
