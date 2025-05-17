<?php
require_once 'check_auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lobby.php?error=Invalid request.');
    exit;
}

$game_id = $_POST['game_id'] ?? 0;
$city_id = $_POST['city_id'] ?? 0;
$round = $_POST['round'] ?? 0;
$content = trim($_POST['content'] ?? '');
$type = $_POST['type'] ?? 'public';
$user_id = $_SESSION['user_id'];

if (!$game_id || !$city_id || !$round || !$content || !in_array($type, ['public', 'secret'])) {
    header('Location: index.php?game_id=' . $game_id . '&error=Invalid order data.');
    exit;
}

// Verify game and user authorization
$stmt = $pdo->prepare("SELECT order_points_cost FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    header('Location: lobby.php?error=Invalid game ID.');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
$stmt->execute([$game_id, $user_id]);
if (!$stmt->fetch() && $_SESSION['role'] !== 'super_admin') {
    header('Location: lobby.php?error=Unauthorized.');
    exit;
}

// Validate city_id
$stmt = $pdo->prepare("SELECT id FROM cities WHERE id = ? AND game_id = ?");
$stmt->execute([$city_id, $game_id]);
if (!$stmt->fetch()) {
    header('Location: index.php?game_id=' . $game_id . '&error=Invalid city ID.');
    exit;
}

// Validate round
$stmt = $pdo->prepare("SELECT round_number FROM rounds WHERE game_id = ? AND round_number = ?");
$stmt->execute([$game_id, $round]);
if (!$stmt->fetch()) {
    header('Location: index.php?game_id=' . $game_id . '&error=Invalid round number.');
    exit;
}

// Check user points
$points_cost = $game['order_points_cost'];
$stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_points = $stmt->fetchColumn() ?: 0;

if ($user_points < $points_cost) {
    header('Location: index.php?game_id=' . $game_id . '&error=Insufficient points.');
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (game_id, city_id, user_id, round, content, type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$game_id, $city_id, $user_id, $round, $content, $type]);
    $order_id = $pdo->lastInsertId();

    // Record pending points deduction
    $stmt = $pdo->prepare("
        INSERT INTO pending_orders (order_id, user_id, game_id, points_cost)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$order_id, $user_id, $game_id, $points_cost]);

    $pdo->commit();
    header('Location: index.php?game_id=' . $game_id . '&success=Order submitted.');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order submission failed: " . $e->getMessage());
    header('Location: index.php?game_id=' . $game_id . '&error=Failed to submit order.');
    exit;
}
