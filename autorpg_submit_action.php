<?php
session_start();
$_SESSION['user_id'] = 1; // Hardcode for testing

try {
    $pdo = new PDO("mysql:host=localhost;dbname=autorpg", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die(json_encode(['error' => '数据库连接失败']));
}

header('Content-Type: application/json');

try {
    $game_id = $_POST['game_id'] ?? 1;
    $action = $_POST['action'] ?? null;
    error_log("Action: $action, Game ID: $game_id");

    if ($action !== 'move') {
        echo json_encode(['error' => '无效的操作']);
        exit;
    }

    // Hardcode player data for testing
    $player = ['x' => 5, 'y' => 5, 'layer' => 1, 'moves_left' => 10, 'id' => 1];

    $direction = $_POST['direction'];
    $new_x = $player['x'];
    $new_y = $player['y'];
    if ($direction === 'up') $new_y--;
    elseif ($direction === 'down') $new_y++;
    elseif ($direction === 'left') $new_x--;
    elseif ($direction === 'right') $new_x++;

    if ($new_x < 0 || $new_x >= 10 || $new_y < 0 || $new_y >= 10) {
        echo json_encode(['error' => '无法移动到地图外']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT type FROM autorpg_terrains WHERE game_id = ? AND layer = 1 AND x = ? AND y = ?");
    $stmt->execute([$game_id, $new_x, $new_y]);
    $terrain = $stmt->fetch();
    if ($terrain && ($terrain['type'] === 'sea' || $terrain['type'] === 'mountain')) {
        echo json_encode(['error' => '无法移动到' . ($terrain['type'] === 'sea' ? '海洋' : '山地')]);
        exit;
    }

    $player['x'] = $new_x;
    $player['y'] = $new_y;
    $player['moves_left']--;

    echo json_encode(['player' => $player]);
} catch (Exception $e) {
    error_log("Action error: " . $e->getMessage());
    echo json_encode(['error' => '服务器错误']);
}
?>