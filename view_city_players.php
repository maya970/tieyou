<?php
require_once 'db.php';

$game_id = $_GET['game_id'] ?? 0;
$city_id = $_GET['city_id'] ?? 0;

if (!$game_id || !$city_id) {
    echo '';
    exit;
}

$stmt = $pdo->prepare("SELECT player_tag FROM city_players WHERE game_id = ? AND city_id = ?");
$stmt->execute([$game_id, $city_id]);
$players = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo implode("\n", array_map('htmlspecialchars', $players));