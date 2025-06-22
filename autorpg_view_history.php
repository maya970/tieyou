<?php
require_once 'check_auth.php';
require_once 'db.php';

header('Content-Type: application/json');

$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    echo json_encode(['error' => '无效的游戏ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM autorpg_history WHERE game_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$game_id, $_SESSION['user_id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($history);
?>