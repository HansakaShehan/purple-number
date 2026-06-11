<?php
require 'db.php';
$pdo = Database::getInstance()->getPDO();
$stmt = $pdo->prepare('SELECT * FROM game_sessions WHERE room_code = ?');
$stmt->execute(['4ABF']);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($room, JSON_PRETTY_PRINT) . PHP_EOL;
?>
