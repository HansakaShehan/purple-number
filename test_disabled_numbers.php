<?php
require_once 'config.php';
require_once 'api/game/helpers.php';

echo "=== Testing Disabled Numbers Feature ===\n\n";

// Test 1: calculateDisabledNumbers function
echo "Test 1: calculateDisabledNumbers() function\n";
echo "----------------------------------------\n";

// Simulate round 3 (guessCount = 4, which means 5 guesses total)
$guessCount = 4;
$currentRound = ceil(($guessCount + 1) / 2);
echo "Simulating: guessCount=$guessCount, currentRound=$currentRound\n";

$disabled = calculateDisabledNumbers($guessCount, 3);
echo "Disabled numbers: " . json_encode($disabled) . "\n";
echo "Count: " . count($disabled) . " (should be 3-5)\n";
echo "Result: " . (count($disabled) >= 3 && count($disabled) <= 5 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 2: getAvailableNumbers function
echo "Test 2: getAvailableNumbers() function\n";
echo "-------------------------------------\n";
$available = getAvailableNumbers($disabled);
echo "Available numbers: " . json_encode($available) . "\n";
echo "Count: " . count($available) . " (should be 15-17)\n";
echo "Verification: ";
$hasBoth = false;
foreach ($disabled as $num) {
    if (in_array($num, $available)) {
        $hasBoth = true;
        break;
    }
}
echo (!$hasBoth ? "✓ No overlap" : "✗ FAIL - overlap detected") . "\n\n";

// Test 3: generateSmartRandomNumber with disabled numbers
echo "Test 3: generateSmartRandomNumber() with disabled numbers\n";
echo "---------------------------------------------------\n";
for ($i = 0; $i < 5; $i++) {
    $secret = generateSmartRandomNumber(null, $available);
    $isDisabled = in_array($secret, $disabled);
    echo "Secret: $secret - " . ($isDisabled ? "✗ FAIL (is disabled!)" : "✓ PASS (not disabled)") . "\n";
}
echo "\n";

// Test 4: Round calculation
echo "Test 4: Round Calculation\n";
echo "------------------------\n";
$testCases = [
    [0, "1st guess", 1],
    [1, "2nd guess", 1],
    [2, "3rd guess", 2],
    [3, "4th guess", 2],
    [4, "5th guess", 3],
    [5, "6th guess", 3],
    [6, "7th guess", 4],
];

foreach ($testCases as [$count, $desc, $expectedRound]) {
    $round = ceil(($count + 1) / 2);
    $status = ($round === $expectedRound) ? "✓" : "✗";
    echo "$status $desc (count=$count): round=$round (expected=$expectedRound)\n";
}
echo "\n";

// Test 5: Database integration (if game exists)
echo "Test 5: Database Integration\n";
echo "----------------------------\n";
try {
    // Get the most recent game session
    $stmt = $db->prepare('SELECT * FROM game_sessions ORDER BY created_at DESC LIMIT 1');
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        echo "Latest Session: " . $session['room_code'] . "\n";
        echo "Status: " . $session['status'] . "\n";
        echo "Round Disabled At: " . ($session['round_disabled_at'] ?? 'null') . "\n";
        echo "Disabled Numbers JSON: " . ($session['disabled_numbers'] ?? 'null') . "\n";
        
        // Test retrieval
        $retrieved = getDisabledNumbers($session['id'], $db);
        echo "Retrieved via getDisabledNumbers(): " . json_encode($retrieved) . "\n";
        echo "Type: " . gettype($retrieved) . "\n";
        echo "Count: " . count($retrieved) . "\n";
    } else {
        echo "No game sessions found in database\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
