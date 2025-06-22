<?php
require 'db.php';

header('Content-Type: application/json');

try {
    $game_id = $_GET['game_id'] ?? null;
    $layer = $_GET['layer'] ?? null;
    $x = $_GET['x'] ?? null;
    $y = $_GET['y'] ?? null;

    if (!$game_id || !$layer || !isset($x) || !isset($y)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT gi.id, i.name, i.icon_url, i.description
                           FROM autorpg_ground_items gi
                           JOIN autorpg_items i ON gi.item_id = i.id
                           WHERE gi.game_id = ? AND gi.layer = ? AND gi.x = ? AND gi.y = ?");
    $stmt->execute([$game_id, $layer, $x, $y]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($items);
} catch (PDOException $e) {
    error_log("Database error in get_ground_items.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>