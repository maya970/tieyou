<?php
require_once 'db.php';
session_start();

$game_id = $_GET['game_id'] ?? 0;
$round = $_GET['round'] ?? 0;

if (!$game_id || !$round) {
    echo '';
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.id, o.content, u.username, o.type, o.admin_reply
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.game_id = ? AND o.round = ?
    AND (o.type = 'public' OR o.user_id = ? OR ? IN ('game_admin', 'super_admin'))
");
$stmt->execute([$game_id, $round, $_SESSION['user_id'] ?? 0, $_SESSION['role'] ?? '']);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as $order) {
    echo implode('|', [
        $order['id'],
        htmlspecialchars($order['content']),
        htmlspecialchars($order['username'] ?: 'δ֪'),
        $order['type'],
        htmlspecialchars($order['admin_reply'] ?? '')
    ]) . "\n";
}
?>