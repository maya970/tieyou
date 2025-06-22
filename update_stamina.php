<?php
require 'check_auth.php';
require 'db.php';

header('Content-Type: application/json');

$game_id = $_POST['game_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$stamina = $_POST['stamina'] ?? null;
$action_count = $_POST['action_count'] ?? null;
$move_count = $_POST['move_count'] ?? null;
$last_move_hour = $_POST['last_move_hour'] ?? null;

if (!$game_id || !$user_id || $stamina === null || $action_count === null || $move_count === null || !$last_move_hour) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE autorpg_players SET stamina = ?, action_count = ?, move_count = ?, last_move_hour = ? WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$stamina, $action_count, $move_count, $last_move_hour, $game_id, $user_id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    error_log("Update stamina error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>