<?php
session_start();
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

// Get leaderboard data
try {
    $pdo = Database::getInstance()->getPDO();
    
    // First, get all users who have completed games
    $query = "
        SELECT DISTINCT u.id, u.username, u.total_gems
        FROM users u
        INNER JOIN game_sessions gs ON (u.id = gs.player1_id OR u.id = gs.player2_id)
        WHERE gs.status = 'completed'
        ORDER BY u.id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $leaderboard = [];
    $rank = 1;
    
    // Calculate stats for each user
    foreach ($users as $user) {
        $uid = $user['id'];
        
        // Count games - simpler query without DISTINCT
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_games
            FROM game_sessions
            WHERE status = 'completed' AND (player1_id = ? OR player2_id = ?)
        ");
        $stmt->execute([$uid, $uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $games = (int)($result['total_games'] ?? 0);
        
        // Count wins
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as wins
            FROM game_sessions
            WHERE status = 'completed' AND winner_id = ?
        ");
        $stmt->execute([$uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $wins = (int)($result['wins'] ?? 0);
        
        // Count correct guesses
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as correct_guesses
            FROM guesses
            WHERE player_id = ? AND is_correct = 1
        ");
        $stmt->execute([$uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $correct = (int)($result['correct_guesses'] ?? 0);
        
        // Count total guesses
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_guesses
            FROM guesses
            WHERE player_id = ?
        ");
        $stmt->execute([$uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_guesses = (int)($result['total_guesses'] ?? 0);
        
        $leaderboard[] = [
            'rank' => $rank++,
            'id' => $uid,
            'username' => $user['username'],
            'total_gems' => (int)$user['total_gems'],
            'total_games' => (int)$games,
            'wins' => (int)$wins,
            'correct_guesses' => (int)$correct,
            'total_guesses' => (int)$total_guesses,
            'win_rate' => $games > 0 ? round(($wins / $games) * 100, 1) : 0,
            'accuracy' => $total_guesses > 0 ? round(($correct / $total_guesses) * 100, 1) : 0
        ];
    }
    
    // Sort by wins DESC, then games DESC
    usort($leaderboard, function($a, $b) {
        if ($b['wins'] !== $a['wins']) return $b['wins'] - $a['wins'];
        if ($b['total_games'] !== $a['total_games']) return $b['total_games'] - $a['total_games'];
        return $b['correct_guesses'] - $a['correct_guesses'];
    });
    
    // Re-rank after sorting
    $rank = 1;
    foreach ($leaderboard as &$row) {
        $row['rank'] = $rank++;
    }
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Leaderboard error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load leaderboard: ' . $e->getMessage()
    ]);
}
