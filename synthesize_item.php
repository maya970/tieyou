<?php
require 'check_auth.php';
require 'db.php';

header('Content-Type: application/json');

$game_id = $_POST['game_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$item1_id = $_POST['item1_id'] ?? null;
$item2_id = $_POST['item2_id'] ?? null;
$result_item_id = $_POST['result_item_id'] ?? null;

if (!$game_id || !$user_id || !$item1_id || !$item2_id || !$result_item_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND item_id = ? AND is_equipped = 0 LIMIT 1");
    $stmt->execute([$game_id, $user_id, $item1_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Item 1 not found');
    }
    $stmt->execute([$game_id, $user_id, $item2_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Item 2 not found');
    }

    $stmt = $pdo->prepare("DELETE FROM autorpg_player_inventory WHERE id IN (
        SELECT id FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND item_id = ? AND is_equipped = 0 LIMIT 1
    )");
    $stmt->execute([$game_id, $user_id, $item1_id]);
    $stmt->execute([$game_id, $user_id, $item2_id]);

    $stmt = $pdo->prepare("INSERT INTO autorpg_player_inventory (game_id, user_id, item_id, slot, is_equipped) 
                           SELECT ?, ?, ?, COALESCE(MIN(slot), 0), 0 
                           FROM autorpg_player_inventory 
                           WHERE game_id = ? AND user_id = ? AND is_equipped = 0 AND slot < 10");
    $stmt->execute([$game_id, $user_id, $result_item_id, $game_id, $user_id]);

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Synthesis error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>