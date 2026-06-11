<?php
require_once 'config.php';
require_once 'api/game/helpers.php';

echo "=== API Response Format Verification ===\n\n";

try {
    // Find a game session to test state.php format
    $stmt = $db->prepare('SELECT * FROM game_sessions LIMIT 1');
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo "Creating test session for API verification...\n";
        $testRoomCode = 'API' . uniqid();
        $stmt = $db->prepare('INSERT INTO game_sessions (room_code, player1_id, player2_id, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$testRoomCode, 1, 2, 'in_progress']);
        $session = $db->query("SELECT * FROM game_sessions WHERE room_code = '$testRoomCode'")->fetch(PDO::FETCH_ASSOC);
    }
    
    echo "Testing with session: " . $session['room_code'] . "\n\n";
    
    // Simulate what state.php does
    echo "Simulating state.php response structure:\n";
    echo "---------------------------------------\n";
    
    $disabledNumbers = getDisabledNumbers($session['id'], $db);
    
    // Verify the response structure
    $response = [
        'success' => true,
        'game' => [
            'room_code' => $session['room_code'],
            'disabled_numbers' => $disabledNumbers,
            'available_categories' => [
                'free' => [
                    'name' => '1-20',
                    'label' => 'Free Range',
                    'description' => 'All numbers 1-20',
                    'cost' => 0,
                    'reward' => 10,
                    'type' => 'free'
                ],
                'paid' => []
            ]
        ]
    ];
    
    echo "Response structure:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    // Verify types
    echo "\n";
    echo "Type Verification:\n";
    echo "- disabled_numbers is array: " . (is_array($disabledNumbers) ? "✓" : "✗") . "\n";
    echo "- disabled_numbers count: " . count($disabledNumbers) . "\n";
    
    // Verify when disabled numbers are present
    if (count($disabledNumbers) > 0) {
        echo "- Contains valid numbers: ";
        $allValid = true;
        foreach ($disabledNumbers as $num) {
            if (!is_int($num) || $num < 1 || $num > 20) {
                $allValid = false;
                echo "✗ Found invalid: $num\n";
            }
        }
        if ($allValid) {
            echo "✓ All " . count($disabledNumbers) . " numbers are valid\n";
        }
    } else {
        echo "- No disabled numbers (game not at round 3 yet)\n";
    }
    
    // Test JSON encoding/decoding
    echo "\n";
    echo "JSON Encoding Test:\n";
    $encoded = json_encode($disabledNumbers);
    $decoded = json_decode($encoded, true);
    echo "Encoded: $encoded\n";
    echo "Decoded: " . json_encode($decoded) . "\n";
    echo "Match: " . (json_encode($decoded) === $encoded ? "✓" : "✗") . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Verification Complete ===\n";
?>
