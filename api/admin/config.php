<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../game/helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

function buildAdminConfigResponse($config) {
    $disabled = [];
    if (!empty($config['disabled_gem_categories'])) {
        $disabled = json_decode($config['disabled_gem_categories'], true) ?? [];
    }

    return [
        'rounds_count' => (int)($config['rounds_count'] ?? 20),
        'turn_duration_seconds' => (int)($config['turn_duration_seconds'] ?? 10),
        'disabled_gem_categories' => array_values(array_intersect($disabled, getValidGemCategoryNames())),
        'gem_categories' => getAllGemCategories()
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->query('SELECT rounds_count, turn_duration_seconds, disabled_gem_categories FROM admin_config WHERE id = 1');
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'config' => buildAdminConfigResponse($config ?: [])
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins can update
    if (($_SESSION['is_admin'] ?? 0) !== 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $roundsCount = $data['rounds_count'] ?? null;
    $disabledGemCategories = $data['disabled_gem_categories'] ?? null;

    if ($roundsCount === null || $roundsCount < 5 || $roundsCount > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Rounds must be between 5 and 100']);
        exit;
    }

    if ($disabledGemCategories !== null) {
        if (!is_array($disabledGemCategories)) {
            http_response_code(400);
            echo json_encode(['error' => 'disabled_gem_categories must be an array']);
            exit;
        }

        $validNames = getValidGemCategoryNames();
        foreach ($disabledGemCategories as $categoryName) {
            if (!in_array($categoryName, $validNames, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid gem category: ' . $categoryName]);
                exit;
            }
        }

        $disabledGemCategories = array_values(array_unique($disabledGemCategories));
    }

    try {
        if ($disabledGemCategories !== null) {
            $disabledJson = json_encode($disabledGemCategories);
            $stmt = $db->prepare('UPDATE admin_config SET rounds_count = ?, disabled_gem_categories = ? WHERE id = 1');
            $stmt->execute([$roundsCount, $disabledJson]);
        } else {
            $stmt = $db->prepare('UPDATE admin_config SET rounds_count = ? WHERE id = 1');
            $stmt->execute([$roundsCount]);
        }

        $readStmt = $db->query('SELECT rounds_count, turn_duration_seconds, disabled_gem_categories FROM admin_config WHERE id = 1');
        $config = $readStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'config' => buildAdminConfigResponse($config ?: [])
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
