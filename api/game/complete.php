<?php
session_start();
// API endpoint to complete a game and update winner

require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $room_code = $input['room_code'] ?? null;

    if (!$room_code) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Room code required']);
        exit;
    }

    $pdo = Database::getInstance()->getPDO();

    // Get the game session
    $stmt = $pdo->prepare("SELECT id, player1_id, player2_id, total_rounds FROM game_sessions WHERE room_code = ?");
    $stmt->execute([$room_code]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Game not found']);
        exit;
    }

    $session_id = $game['id'];
    $player1_id = $game['player1_id'];
    $player2_id = $game['player2_id'];

    // Get correct guess counts for each player
    $stmt = $pdo->prepare("
        SELECT player_id, COUNT(*) as correct_count 
        FROM guesses 
        WHERE session_id = ? AND is_correct = 1 
        GROUP BY player_id
    ");
    $stmt->execute([$session_id]);
    
    $correctCounts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $correctCounts[$row['player_id']] = $row['correct_count'];
    }

    $player1_correct = $correctCounts[$player1_id] ?? 0;
    $player2_correct = $correctCounts[$player2_id] ?? 0;

    // Calculate gems (10 gems per correct guess)
    $player1_gems = $player1_correct * 10;
    $player2_gems = $player2_correct * 10;

    // Determine winner
    $winner_id = null;
    if ($player1_correct > $player2_correct) {
        $winner_id = $player1_id;
    } elseif ($player2_correct > $player1_correct) {
        $winner_id = $player2_id;
    }
    // If tie, winner_id remains null

    // Update game session with winner and status
    $stmt = $pdo->prepare("
        UPDATE game_sessions 
        SET status = 'completed', 
            winner_id = ?, 
            end_time = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$winner_id, $session_id]);

    // Update gems for both players
    if ($player1_gems > 0) {
        $stmt = $pdo->prepare("UPDATE users SET total_gems = total_gems + ? WHERE id = ?");
        $stmt->execute([$player1_gems, $player1_id]);
    }
    
    if ($player2_id && $player2_gems > 0) {
        $stmt = $pdo->prepare("UPDATE users SET total_gems = total_gems + ? WHERE id = ?");
        $stmt->execute([$player2_gems, $player2_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Game completed',
        'winner_id' => $winner_id,
        'player1_correct' => $player1_correct,
        'player2_correct' => $player2_correct,
        'player1_gems' => $player1_gems,
        'player2_gems' => $player2_gems,
        'is_tie' => $winner_id === null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error completing game: ' . $e->getMessage()
    ]);
}
?>
