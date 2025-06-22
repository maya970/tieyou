<?php
require 'check_auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = $_POST['game_id'] ?? null;
    $layer = $_POST['layer'] ?? null;

    if ($game_id && is_numeric($layer)) {
        try {
            $pdo->beginTransaction();
            // Check if layer exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM autorpg_layers WHERE game_id = ? AND layer = ?");
            $stmt->execute([$game_id, $layer]);
            if ($stmt->fetchColumn() == 0) {
                // Insert new layer
                $stmt = $pdo->prepare("INSERT INTO autorpg_layers (game_id, layer) VALUES (?, ?)");
                $stmt->execute([$game_id, $layer]);

                // Insert 10x10 tiles for new layer
                $stmt = $pdo->prepare("INSERT INTO autorpg_map_tiles (game_id, layer, x, y, img_url, terrain_type, passable) VALUES (?, ?, ?, ?, ?, ?, ?)");
                for ($x = 0; $x < 10; $x++) {
                    for ($y = 0; $y < 10; $y++) {
                        $terrain = in_array([$x, $y], [[0,0], [0,9], [9,0], [9,9]]) ? 'door' : 'grass';
                        $img_url = "/assets/tiles/$terrain.png";
                        $passable = 1;
                        $stmt->execute([$game_id, $layer, $x, $y, $img_url, $terrain, $passable]);
                    }
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error creating layer: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
}
?>