<?php
require_once 'db.php';
session_start();

$game_id = $_GET['game_id'] ?? 0;
$city_id = $_GET['city_id'] ?? null;
$type = $_GET['type'] ?? 'public';

if (!$game_id || !in_array($type, ['public', 'secret'])) {
    echo '';
    exit;
}

$query = "
    SELECT o.id, o.content, u.username, o.admin_reply
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.game_id = ? AND o.type = ? AND o.round = (
        SELECT round_number FROM rounds WHERE game_id = ? AND is_active = 1 LIMIT 1
    )";
$params = [$game_id, $type, $game_id];

if ($city_id && is_numeric($city_id)) {
    $query .= " AND o.city_id = ?";
    $params[] = $city_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as $order) {
    // 清理特殊字符（换行符、分隔符等）
    $content = str_replace(["\r\n", "\n", "\r", "|"], [" ", " ", " ", " "], $order['content'] ?? '无内容');
    $username = str_replace(["\r\n", "\n", "\r", "|"], [" ", " ", " ", " "], $order['username'] ?? '未知');
    $admin_reply = str_replace(["\r\n", "\n", "\r", "|"], [" ", " ", " ", " "], $order['admin_reply'] ?? '');
    
    echo implode('|', [
        $order['id'],
        htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($admin_reply, ENT_QUOTES, 'UTF-8')
    ]) . "\n";
}
?>