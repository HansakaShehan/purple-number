<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    // Get all games where current user participated
    $stmt = $db->prepare('
        SELECT DISTINCT
            gs.id as session_id,
            gs.room_code,
            gs.created_at as game_date
        FROM game_sessions gs
        JOIN guesses g ON gs.id = g.session_id
        WHERE g.player_id = ?
        ORDER BY gs.created_at DESC
    ');
    
    $stmt->execute([$_SESSION['user_id']]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $gameList = [];
    foreach ($sessions as $session) {
        // Get all players in this session
        $playersStmt = $db->prepare('
            SELECT DISTINCT u.username
            FROM guesses g
            JOIN users u ON g.player_id = u.id
            WHERE g.session_id = ?
        ');
        $playersStmt->execute([$session['session_id']]);
        $players = $playersStmt->fetchAll(PDO::FETCH_COLUMN);
        $playerStr = implode(' vs ', $players);
        
        // Get user stats
        $userStmt = $db->prepare('
            SELECT 
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
                SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect
            FROM guesses
            WHERE session_id = ? AND player_id = ?
        ');
        $userStmt->execute([$session['session_id'], $_SESSION['user_id']]);
        $userStats = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        $userCorrect = (int)($userStats['correct'] ?? 0);
        $userIncorrect = (int)($userStats['incorrect'] ?? 0);
        
        // Get opponent stats
        $oppStmt = $db->prepare('
            SELECT 
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
                SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect
            FROM guesses
            WHERE session_id = ? AND player_id != ?
        ');
        $oppStmt->execute([$session['session_id'], $_SESSION['user_id']]);
        $oppStats = $oppStmt->fetch(PDO::FETCH_ASSOC);
        
        $oppCorrect = (int)($oppStats['correct'] ?? 0);
        $oppIncorrect = (int)($oppStats['incorrect'] ?? 0);
        
        // Determine result
        $result = '';
        if ($userCorrect > $oppCorrect) {
            $result = 'Won';
        } elseif ($userCorrect < $oppCorrect) {
            $result = 'Lost';
        } else {
            $result = 'Tied';
        }
        
        $gameList[] = [
            'room_code' => $session['room_code'],
            'players' => $playerStr,
            'date' => $session['game_date'],
            'user_correct' => $userCorrect,
            'user_incorrect' => $userIncorrect,
            'opponent_correct' => $oppCorrect,
            'opponent_incorrect' => $oppIncorrect,
            'result' => $result
        ];
    }

    echo json_encode([
        'success' => true,
        'games' => $gameList
    ]);
} catch (PDOException $e) {
    error_log('Game history error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
