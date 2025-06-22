<?php
require 'db.php';
require 'check_auth.php';

header('Content-Type: application/json');

try {
    $game_id = $_POST['game_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $x = $_POST['x'] ?? null;
    $y = $_POST['y'] ?? null;
    $layer = $_POST['layer'] ?? null;

    if (!$game_id || !$user_id || !isset($x) || !isset($y) || !$layer) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    // Verify user is in the game
    $stmt = $pdo->prepare("SELECT id FROM autorpg_players WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Player not found']);
        exit;
    }

    // Update position
    $stmt = $pdo->prepare("UPDATE autorpg_players SET x = ?, y = ?, layer = ? WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$x, $y, $layer, $game_id, $user_id]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    error_log("Database error in update_position.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>