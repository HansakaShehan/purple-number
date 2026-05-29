<?php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$guess = null;
if (is_array($data) && array_key_exists('guess', $data)) {
    $guess = $data['guess'] === null ? null : intval($data['guess']);
} elseif (isset($_POST['user_guess'])) {
    $guess = intval($_POST['user_guess']);
}

$real = rand(1, 10);
$correct = ($guess !== null && $guess === $real);

$resp = [
    'real' => $real,
    'guess' => $guess,
    'correct' => $correct,
    'message' => $correct ? 'Correct' : 'Wrong'
];

echo json_encode($resp);
