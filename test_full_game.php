<?php
require_once 'config.php';
require_once 'api/game/helpers.php';

echo "=== Full Disabled Numbers Integration Test ===\n\n";

try {
    // Find a game session
    $stmt = $db->prepare('SELECT * FROM game_sessions WHERE status = "in_progress" ORDER BY created_at DESC LIMIT 1');
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo "No in-progress games found. Creating test scenario...\n\n";
        // We'll simulate the data flow
    } else {
        echo "Found game session: " . $session['room_code'] . "\n";
        echo "Status: " . $session['status'] . "\n";
        echo "Round Disabled At: " . ($session['round_disabled_at'] ?? 'null') . "\n\n";
        
        // Get all guesses for this session
        $stmt = $db->prepare('SELECT * FROM guesses WHERE session_id = ? ORDER BY created_at ASC');
        $stmt->execute([$session['id']]);
        $guesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Game Progress:\n";
        echo "--------------\n";
        $roundMap = [];
        foreach ($guesses as $i => $guess) {
            $guessCount = $i;
            $round = ceil(($guessCount + 1) / 2);
            echo "Guess " . ($i + 1) . " (Round $round): ";
            echo "Player " . $guess['player_id'] . " guessed " . $guess['guessed_number'];
            echo " (secret was " . $guess['secret_number'] . ")";
            echo " - " . ($guess['is_correct'] ? "CORRECT ✓" : "WRONG ✗") . "\n";
            
            // Check if this is when disabled numbers should be calculated
            if ($round >= 3 && $i == 4) { // 5th guess total = round 3
                echo "  → Round 3 detected! Disabled numbers should be calculated at this point.\n";
            }
        }
        
        echo "\n";
        echo "Current Session State:\n";
        echo "---------------------\n";
        
        // Get current disabled numbers from session
        $disabled = getDisabledNumbers($session['id'], $db);
        echo "Disabled Numbers (from DB): " . json_encode($disabled) . "\n";
        echo "Round Disabled At: " . $session['round_disabled_at'] . "\n";
        
        // Get available numbers
        $available = getAvailableNumbers($disabled);
        echo "Available Numbers: " . json_encode($available) . "\n";
        
        echo "\nValidation:\n";
        echo "-----------\n";
        
        // Check if any guesses were made with disabled numbers
        $hasDisabledGuesses = false;
        foreach ($guesses as $i => $guess) {
            if (in_array($guess['secret_number'], $disabled)) {
                echo "✗ FAIL: Secret number " . $guess['secret_number'] . " was disabled but used as secret!\n";
                $hasDisabledGuesses = true;
            }
        }
        
        if (!$hasDisabledGuesses && count($disabled) > 0) {
            echo "✓ PASS: No secret numbers were from disabled set\n";
        }
        
        // Check if disabled numbers are in valid range
        foreach ($disabled as $num) {
            if ($num < 1 || $num > 20) {
                echo "✗ FAIL: Disabled number $num is outside 1-20 range\n";
            }
        }
        if (count($disabled) > 0 && min($disabled) >= 1 && max($disabled) <= 20) {
            echo "✓ PASS: All disabled numbers are in 1-20 range\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
