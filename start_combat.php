<?php
require 'check_auth.php';
require 'db.php';

$game_id = $_POST['game_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$x = $_POST['x'] ?? null;
$y = $_POST['y'] ?? null;
$layer = $_POST['layer'] ?? null;

try {
    $stmt = $pdo->prepare("SELECT id FROM autorpg_monster_suppression WHERE game_id = ? AND layer = ? AND x = ? AND y = ? AND expires_at > NOW()");
    $stmt->execute([$game_id, $layer, $x, $y]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'no_combat']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name FROM autorpg_monsters WHERE terrain_type = 'monster' ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $monster = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$monster) {
        echo json_encode(['status' => 'no_monster']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO autorpg_combats (game_id, attacker_monster_id, defender_id, distance) VALUES (?, ?, ?, 16)");
    $stmt->execute([$game_id, $monster['id'], $user_id]);
    $combat_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO autorpg_monster_suppression (game_id, layer, x, y, expires_at) 
                           VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $stmt->execute([$game_id, $layer, $x, $y]);

    echo json_encode([
        'status' => 'success',
        'combat' => [
            'id' => $combat_id,
            'attacker_monster_id' => $monster['id'],
            'monster_name' => $monster['name'],
            'distance' => 16
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database error in start_combat.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>