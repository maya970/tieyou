<?php
require 'check_auth.php';
require 'db.php';

if ($_SESSION['role'] !== 'game_admin' && $_SESSION['role'] !== 'super_admin') {
    header('Location: lobby.php?error=无权管理游戏。');
    exit;
}

$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    header('Location: lobby.php?error=无效的游戏ID。');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM autorpg_games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();
if (!$game || ($game['creator_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'super_admin')) {
    header('Location: lobby.php?error=您无权管理此游戏。');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_layer'])) {
    try {
        $layer = $_POST['layer'];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM autorpg_terrains WHERE game_id = ? AND layer = ?");
        $stmt->execute([$game_id, $layer]);
        $stmt = $pdo->prepare("DELETE FROM autorpg_overlays WHERE game_id = ? AND layer = ?");
        $stmt->execute([$game_id, $layer]);
        $stmt = $pdo->prepare("DELETE FROM autorpg_doors WHERE game_id = ? AND layer = ?");
        $stmt->execute([$game_id, $layer]);
        $stmt = $pdo->prepare("DELETE FROM autorpg_safe_zones WHERE game_id = ? AND layer = ?");
        $stmt->execute([$game_id, $layer]);
        $stmt = $pdo->prepare("DELETE FROM autorpg_ground_items WHERE game_id = ? AND layer = ? AND expires_at IS NOT NULL");
        $stmt->execute([$game_id, $layer]);

        for ($x = 0; $x < 10; $x++) {
            for ($y = 0; $y < 10; $y++) {
                $rand = rand(0, 100);
                $type = $rand < 70 ? 'plain' : ($rand < 90 ? 'sea' : 'mountain');
                $stmt = $pdo->prepare("INSERT INTO autorpg_terrains (game_id, layer, x, y, type) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$game_id, $layer, $x, $y, $type]);
                if ($type === 'plain' && rand(0, 100) < 30) {
                    $overlay = rand(0, 3) === 0 ? 'building' : (rand(0, 2) === 0 ? 'forest' : (rand(0, 1) === 0 ? 'hill' : 'swamp'));
                    $stmt = $pdo->prepare("INSERT INTO autorpg_overlays (game_id, layer, x, y, type) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$game_id, $layer, $x, $y, $overlay]);
                }
            }
        }

        $safeZoneCount = rand(0, 3);
        $safeZones = [];
        while (count($safeZones) < $safeZoneCount) {
            $x = rand(0, 9);
            $y = rand(0, 9);
            if (!in_array([$x, $y], $safeZones)) {
                $safeZones[] = [$x, $y];
                $stmt = $pdo->prepare("INSERT INTO autorpg_safe_zones (game_id, layer, x, y) VALUES (?, ?, ?, ?)");
                $stmt->execute([$game_id, $layer, $x, $y]);
            }
        }

        $positions = [[0, rand(0, 9)], [9, rand(0, 9)], [rand(0, 9), 0], [rand(0, 9), 9]];
        shuffle($positions);
        $types = $layer == 1 ? ['descent'] : ($layer < 100 ? ['descent', 'ascent', 'normal', 'normal'] : ['ascent', 'normal', 'normal', 'normal']);
        shuffle($types);
        for ($i = 0; $i < ($layer == 1 ? 1 : 4); $i++) {
            $target_layer = $types[$i] === 'descent' ? $layer + 1 : ($types[$i] === 'ascent' ? max(1, $layer - rand(1, 3)) : $layer);
            $stmt = $pdo->prepare("INSERT INTO autorpg_doors (game_id, layer, x, y, type, target_layer) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$game_id, $layer, $positions[$i][0], $positions[$i][1], $types[$i], $target_layer]);
        }

        $pdo->commit();
        header('Location: autorpg_admin.php?game_id=' . $game_id . '&success=层' . $layer . '已刷新。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: autorpg_admin.php?game_id=' . $game_id . '&error=层刷新失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理AutoRPG游戏 - <?php echo htmlspecialchars($game['name']); ?></title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">管理AutoRPG游戏: <?php echo htmlspecialchars($game['name']); ?></h1>
        <a href="lobby.php" class="text-blue-500 hover:underline mb-4 inline-block">← 返回大厅</a>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>
        <h2 class="text-lg font-semibold mb-2">刷新指定层</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="refresh_layer" value="1">
            <input type="number" name="layer" required min="1" max="100" class="w-full p-2 mb-2 border rounded" placeholder="层数 (1-100)">
            <button type="submit" class="bg-yellow-500 text-white p-2 rounded hover:bg-yellow-600">刷新层</button>
        </form>
    </div>
</body>
</html>