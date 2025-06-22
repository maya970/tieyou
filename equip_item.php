<?php
require 'check_auth.php';
require 'db.php';

header('Content-Type: application/json');

$game_id = $_POST['game_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$slot = $_POST['slot'] ?? null;
$equip = $_POST['equip'] ?? null;

if (!$game_id || !$user_id || $slot === null || $equip === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($equip == 1) {
        $stmt = $pdo->prepare("SELECT id FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND slot = ? AND is_equipped = 0");
        $stmt->execute([$game_id, $user_id, $slot]);
        if (!$stmt->fetch()) {
            throw new Exception('Item not found or already equipped');
        }
        $stmt = $pdo->prepare("UPDATE autorpg_player_inventory SET is_equipped = 1, slot = (SELECT COALESCE(MIN(slot), 0) FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND is_equipped = 1 AND slot < 4) WHERE game_id = ? AND user_id = ? AND slot = ?");
        $stmt->execute([$game_id, $user_id, $game_id, $user_id, $slot]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND slot = ? AND is_equipped = 1");
        $stmt->execute([$game_id, $user_id, $slot]);
        if (!$stmt->fetch()) {
            throw new Exception('Item not found or not equipped');
        }
        $stmt = $pdo->prepare("UPDATE autorpg_player_inventory SET is_equipped = 0, slot = (SELECT COALESCE(MIN(slot), 0) FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND is_equipped = 0 AND slot < 10) WHERE game_id = ? AND user_id = ? AND slot = ?");
        $stmt->execute([$game_id, $user_id, $game_id, $user_id, $slot]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Equip item error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>