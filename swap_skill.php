<?php
require 'check_auth.php';
require 'db.php';

$game_id = $_POST['game_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$slot1 = $_POST['slot1'] ?? null;
$slot2 = $_POST['slot2'] ?? null;

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE autorpg_player_skills SET slot = ? WHERE game_id = ? AND user_id = ? AND slot = ?");
    $stmt->execute([999, $game_id, $user_id, $slot1]);
    $stmt->execute([$slot1, $game_id, $user_id, $slot2]);
    $stmt->execute([$slot2, $game_id, $user_id, 999]);
    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in swap_skill.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>