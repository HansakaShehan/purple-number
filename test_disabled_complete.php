<?php
require_once 'config.php';
require_once 'api/game/helpers.php';

echo "=== Disabled Numbers Complete Flow Test ===\n\n";

try {
    // Create a test game session
    $testRoomCode = 'TEST' . uniqid();
    $testUser1Id = 1;
    $testUser2Id = 2;
    
    echo "Creating test game session: $testRoomCode\n";
    $stmt = $db->prepare('
        INSERT INTO game_sessions (room_code, player1_id, player2_id, status)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$testRoomCode, $testUser1Id, $testUser2Id, 'in_progress']);
    $sessionId = $db->lastInsertId();
    echo "Created session ID: $sessionId\n\n";
    
    // Simulate 5 guesses (so we hit round 3)
    echo "Simulating 5 guesses to reach Round 3:\n";
    echo "-------------------------------------\n";
    
    for ($i = 0; $i < 5; $i++) {
        $guessCount = $i;
        $round = ceil(($guessCount + 1) / 2);
        $playerId = ($i % 2 == 0) ? $testUser1Id : $testUser2Id;
        $guessedNum = rand(1, 20);
        $secretNum = rand(1, 20);
        
        $stmt = $db->prepare('
            INSERT INTO guesses (session_id, player_id, guessed_number, secret_number, is_correct, selected_category, category_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$sessionId, $playerId, $guessedNum, $secretNum, 0, '1-20', 0]);
        
        echo "Guess " . ($i + 1) . " (Round $round): Player $playerId guessed $guessedNum (secret: $secretNum)\n";
        
        if ($round == 3 && $i == 4) {
            echo "  → Round 3 reached! Disabled numbers should be calculated now.\n";
        }
    }
    
    echo "\n";
    echo "Checking disabled numbers at Round 3:\n";
    echo "------------------------------------\n";
    
    // Now check what disabled numbers would be calculated
    $guessCount = 4; // After 5 guesses (index 0-4)
    $currentRound = ceil(($guessCount + 1) / 2);
    echo "Current Round: $currentRound\n";
    
    if ($currentRound >= 3) {
        $disabled = calculateDisabledNumbers($guessCount, 3);
        echo "Disabled numbers calculated: " . json_encode($disabled) . "\n";
        
        // Verify they're in valid range
        $validRange = true;
        foreach ($disabled as $num) {
            if ($num < 1 || $num > 20) {
                $validRange = false;
                echo "✗ Invalid number in range: $num\n";
            }
        }
        
        if ($validRange && count($disabled) >= 3 && count($disabled) <= 5) {
            echo "✓ PASS: Disabled numbers are valid (count: " . count($disabled) . ", all in 1-20)\n";
        }
        
        // Now update the session with disabled numbers
        echo "\nStoring disabled numbers to database...\n";
        $disabledJson = json_encode($disabled);
        $stmt = $db->prepare('UPDATE game_sessions SET disabled_numbers = ?, round_disabled_at = ? WHERE id = ?');
        $stmt->execute([$disabledJson, $currentRound, $sessionId]);
        echo "✓ Stored: $disabledJson\n";
        
        // Retrieve and verify
        echo "\nRetrieving disabled numbers from database...\n";
        $retrieved = getDisabledNumbers($sessionId, $db);
        echo "Retrieved: " . json_encode($retrieved) . "\n";
        
        if (json_encode($retrieved) === $disabledJson) {
            echo "✓ PASS: Retrieved data matches stored data\n";
        } else {
            echo "✗ FAIL: Retrieved data doesn't match stored data\n";
        }
        
        // Test generateSmartRandomNumber with disabled numbers
        echo "\nTesting secret number generation with disabled numbers:\n";
        $available = getAvailableNumbers($retrieved);
        $secretTest = generateSmartRandomNumber(null, $available);
        
        if (in_array($secretTest, $retrieved)) {
            echo "✗ FAIL: Generated secret $secretTest is in disabled set!\n";
        } else {
            echo "✓ PASS: Generated secret $secretTest is not in disabled set\n";
        }
    }
    
    // Cleanup
    echo "\n\nCleaning up test data...\n";
    $stmt = $db->prepare('DELETE FROM guesses WHERE session_id = ?');
    $stmt->execute([$sessionId]);
    $stmt = $db->prepare('DELETE FROM game_sessions WHERE id = ?');
    $stmt->execute([$sessionId]);
    echo "✓ Test data removed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
