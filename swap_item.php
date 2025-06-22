<?php
require 'check_auth.php';
require 'db.php';

header('Content-Type: application/json');

$game_id = $_POST['game_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$slot1 = $_POST['slot1'] ?? null;
$slot2 = $_POST['slot2'] ?? null;

if (!$game_id || !$user_id || $slot1 === null || $slot2 === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, slot, is_equipped FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND slot IN (?, ?)");
    $stmt->execute([$game_id, $user_id, $slot1, $slot2]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $item1 = null;
    $item2 = null;
    foreach ($items as $i) {
        if ($i['slot'] == $slot1) $item1 = $i;
        if ($i['slot'] == $slot2) $item2 = $i;
    }

    if ($item1) {
        $stmt = $pdo->prepare("UPDATE autorpg_player_inventory SET slot = ? WHERE id = ?");
        $stmt->execute([$slot2, $item1['id']]);
    }
    if ($item2) {
        $stmt = $pdo->prepare("UPDATE autorpg_player_inventory SET slot = ? WHERE id = ?");
        $stmt->execute([$slot1, $item2['id']]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Swap item error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>