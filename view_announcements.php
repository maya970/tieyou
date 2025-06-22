<?php
require_once 'db.php';
session_start();

$game_id = $_GET['game_id'] ?? 0;
$round = $_GET['round'] ?? 0;

if (!$game_id || !$round) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, content, created_at
    FROM history
    WHERE game_id = ? AND type = 'announcement' AND round_number = ?
    ORDER BY created_at DESC
");
$stmt->execute([$game_id, $round]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($announcements);
?>