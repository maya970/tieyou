
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'game_admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    header('Location: lobby.php?error=无效的游戏ID。');
    exit;
}

// Verify game exists and user is creator or super admin
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT field_name, display_name FROM game_field_names WHERE game_id = ?");
$stmt->execute([$game_id]);
$field_names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
if (!$game || ($game['creator_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'super_admin')) {
    header('Location: lobby.php?error=您无权管理此游戏。');
    exit;
}

// Handle background image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['background_image'])) {
    try {
        $upload_dir = __DIR__ . '/uploads/map_backgrounds/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file = $_FILES['background_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('上传错误: ' . $file['error']);
        }

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('仅支持 JPEG、PNG 或 GIF 格式。');
        }

        if ($file['size'] > $max_size) {
            throw new Exception('文件大小不能超过 5MB。');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'game_' . $game_id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('无法保存文件。');
        }

        // Delete old image if exists
        if (!empty($game['background_image']) && file_exists(__DIR__ . $game['background_image'])) {
            unlink(__DIR__ . $game['background_image']);
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE games SET background_image = ? WHERE id = ?");
        $stmt->execute(['/uploads/map_backgrounds/' . $filename, $game_id]);

        header('Location: admin.php?game_id=' . $game_id . '&success=背景图片已更新。');
        exit;
    } catch (Exception $e) {
        header('Location: admin.php?game_id=' . $game_id . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Handle background image removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_background'])) {
    try {
        if (!empty($game['background_image']) && file_exists(__DIR__ . $game['background_image'])) {
            unlink(__DIR__ . $game['background_image']);
        }
        $stmt = $pdo->prepare("UPDATE games SET background_image = NULL WHERE id = ?");
        $stmt->execute([$game_id]);
        header('Location: admin.php?game_id=' . $game_id . '&success=背景图片已移除。');
        exit;
    } catch (Exception $e) {
        header('Location: admin.php?game_id=' . $game_id . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}

function evaluateFormula($formula, $city, $allowed_fields = [
    'population', 'resources', 'economy', 'military', 'culture', 'science',
    'infrastructure', 'health', 'education', 'stability', 'food_consumption', 'money_consumption'
]) {
    // 将字段名替换为实际值
    foreach ($allowed_fields as $field) {
        $value = $city[$field] ?? 0;
        $formula = str_replace($field, $value, $formula);
    }
    // 简单的算术表达式求值（仅支持 + - * /）
    try {
        // 使用 eval 执行公式（注意安全风险）
        $result = null;
        $safe_formula = preg_replace('/[^0-9+\-*.\/() ]/', '', $formula); // 清理非法字符
        eval('$result = ' . $safe_formula . ';');
        return is_numeric($result) ? $result : 0;
    } catch (Exception $e) {
        error_log("公式解析失败: $formula, 错误: " . $e->getMessage());
        return 0;
    }
}

// Handle city/tile creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_city'])) {
    $name = trim($_POST['name'] ?? '');
    $x = $_POST['x'] ?? null;
    $y = $_POST['y'] ?? null;
    $type = $_POST['type'] ?? 'city';

    if (empty($name)) {
        header('Location: admin.php?game_id=' . $game_id . '&error=名称不能为空。');
        exit;
    }
    if (!is_numeric($x) || !is_numeric($y)) {
        header('Location: admin.php?game_id=' . $game_id . '&error=坐标必须为数字。');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO cities (
                game_id, name, x, y, description, type, population, resources, growth_rate, updated_at,
                city_display_type, city_display_value, economy, military, military_growth, culture,
                culture_growth, science, science_growth, infrastructure, infrastructure_growth,
                health, health_growth, education, education_growth, stability, stability_growth,
                show_name, food_consumption, money_consumption
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $game_id,
            $name,
            $x,
            $y,
            trim($_POST['description'] ?? ''),
            $type,
            $type === 'city' ? ($_POST['population'] ?? 1000) : null,
            $type === 'city' ? ($_POST['resources'] ?? 500) : null,
            $type === 'city' ? ($_POST['growth_rate'] ?? 0) : null,
            $_POST['city_display_type'] ?? ($type === 'city' ? 'circle' : 'text'),
            trim($_POST['city_display_value'] ?? ''),
            $type === 'city' ? ($_POST['economy'] ?? 500) : null,
            $type === 'city' ? ($_POST['military'] ?? 100) : null,
            $type === 'city' ? ($_POST['military_growth'] ?? 0) : null,
            $type === 'city' ? ($_POST['culture'] ?? 100) : null,
            $type === 'city' ? ($_POST['culture_growth'] ?? 0) : null,
            $type === 'city' ? ($_POST['science'] ?? 100) : null,
            $type === 'city' ? ($_POST['science_growth'] ?? 0) : null,
            $type === 'city' ? ($_POST['infrastructure'] ?? 100) : null,
            $type === 'city' ? ($_POST['infrastructure_growth'] ?? 0) : null,
            $type === 'city' ? ($_POST['health'] ?? 100) : null,
            $type === 'city' ? ($_POST['health_growth'] ?? 0) : null,
            $type === 'city' ? ($_POST['education'] ?? 100) : null,
            $type === 'city' ? ($_POST['education_growth'] ?? 0) : null,
            $type === 'city' ? ($_POST['stability'] ?? 100) : null,
            $type === 'city' ? ($_POST['stability_growth'] ?? 0) : null,
            isset($_POST['show_name']) ? 1 : 0,
            in_array($type, ['ocean', 'mountain']) ? ($_POST['food_consumption'] ?? 0) : null,
            in_array($type, ['ocean', 'mountain']) ? ($_POST['money_consumption'] ?? 0) : null
        ]);
        header('Location: admin.php?game_id=' . $game_id . '&success=添加成功。');
        exit;
    } catch (Exception $e) {
        error_log("添加失败: " . $e->getMessage() . " | 输入: " . json_encode($_POST));
        header('Location: admin.php?game_id=' . $game_id . '&error=添加失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle city/tile search
$search_results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_city'])) {
    $search_name = trim($_POST['search_name'] ?? '');
    $search_x = $_POST['search_x'] ?? '';
    $search_y = $_POST['search_y'] ?? '';
    $search_type = $_POST['search_type'] ?? '';
    $search_player = trim($_POST['search_player'] ?? '');

    $query = "SELECT c.*, cp.player_tag FROM cities c LEFT JOIN city_players cp ON c.id = cp.city_id WHERE c.game_id = ?";
    $params = [$game_id];

    if ($search_name) {
        $query .= " AND c.name LIKE ?";
        $params[] = "%$search_name%";
    }
    if (is_numeric($search_x)) {
        $query .= " AND c.x = ?";
        $params[] = $search_x;
    }
    if (is_numeric($search_y)) {
        $query .= " AND c.y = ?";
        $params[] = $search_y;
    }
    if ($search_type) {
        $query .= " AND c.type = ?";
        $params[] = $search_type;
    }
    if ($search_player) {
        $query .= " AND cp.player_tag LIKE ?";
        $params[] = "%$search_player%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle order response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_order'])) {
    $order_id = $_POST['order_id'] ?? null;
    $response = trim($_POST['response'] ?? '');
    $points_cost = $_POST['points_cost'] ?? $game['order_points_cost'];

    if (!$order_id || !is_numeric($order_id) || empty($response)) {
        header('Location: admin.php?game_id=' . $game_id . '&error=指令ID或回复内容无效。');
        exit;
    }
    if (!is_numeric($points_cost) || $points_cost < 0) {
        header('Location: admin.php?game_id=' . $game_id . '&error=积分消耗必须为非负数。');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update order with response
        $stmt = $pdo->prepare("UPDATE orders SET admin_reply = ? WHERE id = ? AND game_id = ?");
        $stmt->execute([$response, $order_id, $game_id]);

        // Get order details
        $stmt = $pdo->prepare("SELECT user_id, city_id, type, content, round FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new Exception("订单不存在。");
        }
        $player_tag = $city_player_map[$order['city_id']] ?? '';

        // Check if pending_orders record exists
        $stmt = $pdo->prepare("SELECT id, points_cost FROM pending_orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $existing_pending = $stmt->fetch(PDO::FETCH_ASSOC);

        // Deduct points
        $stmt = $pdo->prepare("UPDATE users SET points = points - ? WHERE id = ?");
        $stmt->execute([$points_cost, $order['user_id']]);

        if ($existing_pending) {
            // Update existing pending_orders record if points_cost has changed
            if ($existing_pending['points_cost'] != $points_cost) {
                $stmt = $pdo->prepare("UPDATE pending_orders SET points_cost = ? WHERE order_id = ?");
                $stmt->execute([$points_cost, $order_id]);
            }
        } else {
            // Insert new pending_orders record
            $stmt = $pdo->prepare("INSERT INTO pending_orders (order_id, user_id, game_id, points_cost) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $order['user_id'], $game_id, $points_cost]);
        }

        // Record in history
        $stmt = $pdo->prepare("
            INSERT INTO history (game_id, type, content, player_tag, round_number, created_at)
            VALUES (?, 'response', ?, ?, ?, NOW())
        ");
        $content = "指令: {$order['content']}\n回复: $response (积分: $points_cost)";
        $stmt->execute([$game_id, $content, $player_tag, $order['round']]);

        $pdo->commit();
        header('Location: admin.php?game_id=' . $game_id . '&success=指令回复成功。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("指令回复失败: " . $e->getMessage());
        header('Location: admin.php?game_id=' . $game_id . '&error=指令回复失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $content = trim($_POST['announcement'] ?? '');
    if (empty($content)) {
        header('Location: admin.php?game_id=' . $game_id . '&error=公告内容不能为空。');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT round_number FROM rounds WHERE game_id = ? AND is_active = 1
        ");
        $stmt->execute([$game_id]);
        $round_number = $stmt->fetchColumn() ?: 1;

        $stmt = $pdo->prepare("
            INSERT INTO history (game_id, type, content, round_number, created_at)
            VALUES (?, 'announcement', ?, ?, NOW())
        ");
        $stmt->execute([$game_id, $content, $round_number]);
        header('Location: admin.php?game_id=' . $game_id . '&success=公告发布成功。');
        exit;
    } catch (Exception $e) {
        error_log("公告发布失败: " . $e->getMessage());
        header('Location: admin.php?game_id=' . $game_id . '&error=公告发布失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle player tag assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_player'])) {
    $city_id = $_POST['city_id'] ?? null;
    $player_tag = trim($_POST['player_tag'] ?? '');

    if (!$city_id || !is_numeric($city_id) || empty($player_tag)) {
        header("Location: admin.php?game_id=$game_id&error=城市和玩家名称为必填项。");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM cities WHERE id = ? AND game_id = ?");
        $stmt->execute([$city_id, $game_id]);
        if (!$stmt->fetch()) {
            header("Location: admin.php?game_id=$game_id&error=无效的城市。");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM city_players WHERE game_id = ? AND city_id = ?");
        $stmt->execute([$game_id, $city_id]);

        $stmt = $pdo->prepare("INSERT INTO city_players (game_id, city_id, player_tag) VALUES (?, ?, ?)");
        $stmt->execute([$game_id, $city_id, $player_tag]);

        header("Location: admin.php?game_id=$game_id&success=玩家分配成功。");
        exit;
    } catch (Exception $e) {
        error_log("玩家分配失败: " . $e->getMessage());
        header("Location: admin.php?game_id=$game_id&error=玩家分配失败: " . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle game settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $stmt = $pdo->prepare("UPDATE games SET name = ?, rules = ?, show_city_names = ?, order_points_cost = ? WHERE id = ?");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['rules']),
            isset($_POST['show_city_names']) ? 1 : 0,
            $_POST['order_points_cost'] ?? $game['order_points_cost'],
            $game_id
        ]);
        header('Location: admin.php?game_id=' . $game_id . '&success=游戏设置更新成功。');
        exit;
    } catch (Exception $e) {
        error_log("游戏设置更新失败: " . $e->getMessage());
        header('Location: admin.php?game_id=' . $game_id . '&error=更新游戏设置失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle round management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_round'])) {
    try {
        $pdo->beginTransaction();

        // Deactivate current round
        $stmt = $pdo->prepare("UPDATE rounds SET is_active = 0 WHERE game_id = ? AND is_active = 1");
        $stmt->execute([$game_id]);

        // Load formulas
        $stmt = $pdo->prepare("SELECT field_name, formula FROM game_formulas WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $formulas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Load all cities
        $stmt = $pdo->prepare("SELECT * FROM cities WHERE game_id = ? AND type = 'city'");
        $stmt->execute([$game_id]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update each city
        $stmt = $pdo->prepare("
            UPDATE cities
            SET
                population = ?,
                resources = ?,
                economy = ?,
                military = ?,
                culture = ?,
                science = ?,
                infrastructure = ?,
                health = ?,
                education = ?,
                stability = ?,
                growth_rate = ?,
                military_growth = ?,
                culture_growth = ?,
                science_growth = ?,
                infrastructure_growth = ?,
                health_growth = ?,
                education_growth = ?,
                stability_growth = ?,
                food_consumption = ?,
                money_consumption = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        foreach ($cities as $city) {
            // Calculate growth rate increments
            $growth_rate_increments = [
                'growth_rate' => evaluateFormula($formulas['population_growth'] ?? '0', $city),
                'military_growth' => evaluateFormula($formulas['military_growth'] ?? '0', $city),
                'culture_growth' => evaluateFormula($formulas['culture_growth'] ?? '0', $city),
                'science_growth' => evaluateFormula($formulas['science_growth'] ?? '0', $city),
                'infrastructure_growth' => evaluateFormula($formulas['infrastructure_growth'] ?? '0', $city),
                'health_growth' => evaluateFormula($formulas['health_growth'] ?? '0', $city),
                'education_growth' => evaluateFormula($formulas['education_growth'] ?? '0', $city),
                'stability_growth' => evaluateFormula($formulas['stability_growth'] ?? '0', $city),
            ];

            // Calculate new growth rates (cumulative)
            $new_growth_rates = [
                'growth_rate' => ($city['growth_rate'] ?? 0) + $growth_rate_increments['growth_rate'],
                'military_growth' => ($city['military_growth'] ?? 0) + $growth_rate_increments['military_growth'],
                'culture_growth' => ($city['culture_growth'] ?? 0) + $growth_rate_increments['culture_growth'],
                'science_growth' => ($city['science_growth'] ?? 0) + $growth_rate_increments['science_growth'],
                'infrastructure_growth' => ($city['infrastructure_growth'] ?? 0) + $growth_rate_increments['infrastructure_growth'],
                'health_growth' => ($city['health_growth'] ?? 0) + $growth_rate_increments['health_growth'],
                'education_growth' => ($city['education_growth'] ?? 0) + $growth_rate_increments['education_growth'],
                'stability_growth' => ($city['stability_growth'] ?? 0) + $growth_rate_increments['stability_growth'],
            ];

            // Calculate increments for economy, resources, food_consumption, and money_consumption
            $economy_increment = evaluateFormula($formulas['economy'] ?? '0', $city);
            $resources_increment = evaluateFormula($formulas['resources'] ?? '0', $city);
            $food_consumption_increment = evaluateFormula($formulas['food_consumption'] ?? '0', $city);
            $money_consumption_increment = evaluateFormula($formulas['money_consumption'] ?? '0', $city);

            // Calculate new values
            $new_values = [
                'population' => ($city['population'] ?? 0) + (($city['population'] ?? 0) * ($new_growth_rates['growth_rate'] / 100)),
                'resources' => ($city['resources'] ?? 0) + $resources_increment,
                'economy' => ($city['economy'] ?? 0) + $economy_increment,
                'military' => ($city['military'] ?? 0) + (($city['military'] ?? 0) * ($new_growth_rates['military_growth'] / 100)),
                'culture' => ($city['culture'] ?? 0) + (($city['culture'] ?? 0) * ($new_growth_rates['culture_growth'] / 100)),
                'science' => ($city['science'] ?? 0) + (($city['science'] ?? 0) * ($new_growth_rates['science_growth'] / 100)),
                'infrastructure' => ($city['infrastructure'] ?? 0) + (($city['infrastructure'] ?? 0) * ($new_growth_rates['infrastructure_growth'] / 100)),
                'health' => ($city['health'] ?? 0) + (($city['health'] ?? 0) * ($new_growth_rates['health_growth'] / 100)),
                'education' => ($city['education'] ?? 0) + (($city['education'] ?? 0) * ($new_growth_rates['education_growth'] / 100)),
                'stability' => ($city['stability'] ?? 0) + (($city['stability'] ?? 0) * ($new_growth_rates['stability_growth'] / 100)),
                'food_consumption' => ($city['food_consumption'] ?? 0) + $food_consumption_increment,
                'money_consumption' => ($city['money_consumption'] ?? 0) + $money_consumption_increment,
            ];

            // Update city
            $stmt->execute([
                $new_values['population'],
                $new_values['resources'],
                $new_values['economy'],
                $new_values['military'],
                $new_values['culture'],
                $new_values['science'],
                $new_values['infrastructure'],
                $new_values['health'],
                $new_values['education'],
                $new_values['stability'],
                $new_growth_rates['growth_rate'],
                $new_growth_rates['military_growth'],
                $new_growth_rates['culture_growth'],
                $new_growth_rates['science_growth'],
                $new_growth_rates['infrastructure_growth'],
                $new_growth_rates['health_growth'],
                $new_growth_rates['education_growth'],
                $new_growth_rates['stability_growth'],
                $new_values['food_consumption'],
                $new_values['money_consumption'],
                $city['id']
            ]);
        }

        // Get the next round number
        $stmt = $pdo->prepare("SELECT MAX(round_number) FROM rounds WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $new_round_number = ($stmt->fetchColumn() ?: 0) + 1;

        // Start new round
        $end_time = date('Y-m-d H:i:s', strtotime('+1 day'));
        $stmt = $pdo->prepare("
            INSERT INTO rounds (game_id, round_number, is_active, end_time, status)
            VALUES (?, ?, 1, ?, 'active')
        ");
        $stmt->execute([$game_id, $new_round_number, $end_time]);

        $pdo->commit();
        header('Location: admin.php?game_id=' . $game_id . '&success=新回合已开始，城市数值已更新。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("回合管理或城市数值更新失败: " . $e->getMessage());
        header('Location: admin.php?game_id=' . $game_id . '&error=回合管理或城市数值更新失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle field names update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_field_names'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO game_field_names (game_id, field_name, display_name)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE display_name = ?
        ");
        foreach ($_POST['field_names'] as $field_name => $display_name) {
            if (empty(trim($display_name))) {
                throw new Exception("字段名称 {$field_name} 不能为空。");
            }
            $stmt->execute([$game_id, $field_name, trim($display_name), trim($display_name)]);
        }
        $pdo->commit();
        header('Location: admin.php?game_id=' . $game_id . '&success=字段名称更新成功。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("字段名称更新失败: " . $e->getMessage());
        header('Location: admin.php?game_id=' . $game_id . '&error=字段名称更新失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle formulas update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_formulas'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO game_formulas (game_id, field_name, formula)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE formula = ?
        ");
        foreach ($_POST['formulas'] as $field_name => $formula) {
            if (empty(trim($formula))) {
                throw new Exception("公式 {$field_name} 不能为空。");
            }
            $stmt->execute([$game_id, $field_name, trim($formula), trim($formula)]);
        }
        $pdo->commit();
        header('Location: admin.php?game_id=' . $game_id . '&success=公式更新成功。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("公式更新失败: " . $e->getMessage());
        header('Location: admin.php?game_id=' . $game_id . '&error=公式更新失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Load orders and players
$stmt = $pdo->prepare("
    SELECT o.*, c.name AS city_name, u.username
    FROM orders o
    LEFT JOIN cities c ON o.city_id = c.id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.game_id = ? AND o.admin_reply IS NULL
    ORDER BY o.submitted_at DESC
");
$stmt->execute([$game_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT city_id, player_tag FROM city_players WHERE game_id = ?");
$stmt->execute([$game_id]);
$city_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
$city_player_map = array_column($city_players, 'player_tag', 'city_id');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理游戏 - <?php echo htmlspecialchars($game['name']); ?></title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">管理游戏: <?php echo htmlspecialchars($game['name']); ?></h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <!-- 动态按钮 -->
        <button onclick="window.location.href='dynamic_edit.php?game_id=<?php echo $game_id; ?>'" class="mt-4 bg-blue-500 text-white p-2 rounded hover:bg-blue-600 ml-4">动态编辑</button>
        <button onclick="window.location.href='history_manager.php?game_id=<?php echo $game_id; ?>'" class="mt-4 bg-blue-500 text-white p-2 rounded hover:bg-blue-600 ml-4">历史指令管理</button>

        <!-- Game Settings -->
        <h2 class="text-lg font-semibold mb-2">游戏设置</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="update_settings" value="1">
            <input type="text" name="name" value="<?php echo htmlspecialchars($game['name']); ?>" required class="w-full p-2 mb-2 border rounded" placeholder="游戏名称">
            <textarea name="rules" required class="w-full p-2 mb-2 border rounded h-24" placeholder="游戏规则"><?php echo htmlspecialchars($game['rules']); ?></textarea>
            <label class="block mb-2">
                <input type="checkbox" name="show_city_names" <?php echo $game['show_city_names'] ? 'checked' : ''; ?>>
                在地图上显示城市名称
<p>每次回复指令积分价格</p>
            </label>
            <input type="number" name="order_points_cost" value="<?php echo htmlspecialchars($game['order_points_cost']); ?>" class="w-full p-2 mb-2 border rounded" placeholder="指令回复积分消耗">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">更新设置</button>
        </form>

<!-- Background Image Management -->
<h2 class="text-lg font-semibold mb-2">设置地图背景图片</h2>
<div class="mb-8 bg-white p-4 rounded-lg shadow">
    <?php if (!empty($game['background_image'])): ?>
        <p>当前背景图片:</p>
        <img src="<?php echo htmlspecialchars($game['background_image']); ?>" alt="Map Background" class="max-w-xs mb-4">
        <form method="POST" class="mb-4">
            <button type="submit" name="remove_background" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">移除背景图片</button>
        </form>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label class="block text-sm font-medium mb-1">上传新背景图片 (JPEG, PNG, GIF, 最大 5MB, 推荐 800x600)</label>
        <input type="file" name="background_image" accept="image/jpeg,image/png,image/gif" required class="w-full p-2 mb-2 border rounded">
        <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">上传</button>
    </form>
</div>

     <!-- Add City/Tile -->
        <h2 class="text-lg font-semibold mb-2">添加城市/地块</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="add_city" value="1">
            <input type="text" name="name" placeholder="名称" required class="w-full p-2 mb-2 border rounded">
            <input type="number" name="x" placeholder="X 坐标" required class="w-full p-2 mb-2 border rounded">
            <input type="number" name="y" placeholder="Y 坐标" required class="w-full p-2 mb-2 border rounded">
            <select name="type" class="w-full p-2 mb-2 border rounded">
                <option value="city">城市</option>
                <option value="mountain">山峰</option>
                <option value="forest">森林</option>
                <option value="ocean">海洋</option>
            </select>
            <textarea name="description" placeholder="描述" class="w-full p-2 mb-2 border rounded h-16"></textarea>
            <select name="city_display_type" class="w-full p-2 mb-2 border rounded">
                <option value="circle">圆形</option>
                <option value="image">图片</option>
                <option value="text">文本</option>
                <option value="none">无</option>
            </select>
            <input type="text" name="city_display_value" placeholder="图片 URL 或文本（如：山）" class="w-full p-2 mb-2 border rounded">
   <div class="city-fields" style="display: none;">
    <input type="number" name="population" placeholder="<?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="resources" placeholder="<?php echo htmlspecialchars($field_names['resources'] ?? '物资储备'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="growth_rate" placeholder="<?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="economy" placeholder="<?php echo htmlspecialchars($field_names['economy'] ?? '财富指数'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="military" placeholder="<?php echo htmlspecialchars($field_names['military'] ?? '军力水平'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="military_growth" placeholder="<?php echo htmlspecialchars($field_names['military'] ?? '军力水平'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="culture" placeholder="<?php echo htmlspecialchars($field_names['culture'] ?? '文化影响力'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="culture_growth" placeholder="<?php echo htmlspecialchars($field_names['culture'] ?? '文化影响力'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="science" placeholder="<?php echo htmlspecialchars($field_names['science'] ?? '科技进展'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="science_growth" placeholder="<?php echo htmlspecialchars($field_names['science'] ?? '科技进展'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="infrastructure" placeholder="<?php echo htmlspecialchars($field_names['infrastructure'] ?? '基础建设'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="infrastructure_growth" placeholder="<?php echo htmlspecialchars($field_names['infrastructure'] ?? '基础建设'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="health" placeholder="<?php echo htmlspecialchars($field_names['health'] ?? '公共卫生'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="health_growth" placeholder="<?php echo htmlspecialchars($field_names['health'] ?? '公共卫生'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="education" placeholder="<?php echo htmlspecialchars($field_names['education'] ?? '教育水平'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="education_growth" placeholder="<?php echo htmlspecialchars($field_names['education'] ?? '教育水平'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
    <input type="number" name="stability" placeholder="<?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?>" class="w-full p-2 mb-2 border rounded">
    <input type="number" step="0.01" name="stability_growth" placeholder="<?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?>增长率 (%)" class="w-full p-2 mb-2 border rounded">
</div>
            <label class="block mb-2">
                <input type="checkbox" name="show_name" checked>
                显示名称
            </label>
            <button type="submit" class="bg-green-500 text-white p-2 rounded hover:bg-green-600">添加</button>
        </form>


        <!-- Search Cities/Tiles -->
        <h2 class="text-lg font-semibold mb-2">搜索城市/地块</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="search_city" value="1">
            <input type="text" name="search_name" placeholder="名称" class="w-full p-2 mb-2 border rounded">
            <input type="number" name="search_x" placeholder="X 坐标" class="w-full p-2 mb-2 border rounded">
            <input type="number" name="search_y" placeholder="Y 坐标" class="w-full p-2 mb-2 border rounded">
            <select name="search_type" class="w-full p-2 mb-2 border rounded">
                <option value="">所有类型</option>
                <option value="city">城市</option>
                <option value="mountain">军团</option>
                <option value="forest">特殊</option>
                <option value="ocean">障碍</option>
            </select>
            <input type="text" name="search_player" placeholder="玩家标签" class="w-full p-2 mb-2 border rounded">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">搜索</button>
        </form>
        <?php if (!empty($search_results)): ?>
            <div class="bg-white p-4 rounded-lg shadow mb-8">
                <h3 class="text-md font-semibold mb-2">搜索结果</h3>
                <ul class="list-disc pl-5">
                    <?php foreach ($search_results as $result): ?>
                        <li>
                            <a href="edit_city.php?game_id=<?php echo $game_id; ?>&city_id=<?php echo $result['id']; ?>" class="text-blue-500 hover:underline">
                                <?php echo htmlspecialchars("{$result['x']},{$result['y']} {$result['name']} ({$result['type']})"); ?>
                                <?php if ($result['player_tag']): ?>
                                    - 玩家: <?php echo htmlspecialchars($result['player_tag']); ?>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Manage Orders -->
        <h2 class="text-lg font-semibold mb-2">指令管理</h2>
        <div class="mb-8">
            <?php if (empty($orders)): ?>
                <p class="text-gray-500">无待回复的指令。</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white p-4 rounded-lg shadow mb-4">
                        <p><strong>玩家:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>城市:</strong> <?php echo htmlspecialchars($order['city_name'] ?: '无'); ?></p>
                        <p><strong>类型:</strong> <?php echo $order['type'] === 'public' ? '明令' : '暗令'; ?></p>
                        <p><strong>内容:</strong> <?php echo htmlspecialchars($order['content']); ?></p>
                        <p><strong>回合:</strong> <?php echo $order['round']; ?></p>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="respond_order" value="1">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <textarea name="response" required class="w-full p-2 mb-2 border rounded h-16" placeholder="回复内容"></textarea>
                            <input type="number" name="points_cost" value="<?php echo htmlspecialchars($game['order_points_cost']); ?>" class="w-full p-2 mb-2 border rounded" placeholder="积分消耗">
                            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">提交回复</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Post Announcement -->
        <h2 class="text-lg font-semibold mb-2">发布公告</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="post_announcement" value="1">
            <textarea name="announcement" required class="w-full p-2 mb-2 border rounded h-24" placeholder="公告内容"></textarea>
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">发布公告</button>
        </form>

        <!-- Assign Player Tags -->
        <h2 class="text-lg font-semibold mb-2">分配玩家到城市</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="assign_player" value="1">
            <select name="city_id" required class="w-full p-2 mb-2 border rounded">
                <option value="">选择城市</option>
                <?php
                $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE game_id = ? AND type = 'city'");
                $stmt->execute([$game_id]);
                $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cities as $city):
                ?>
                    <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="player_tag" placeholder="玩家名称（如：玩家1）" required class="w-full p-2 mb-2 border rounded">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">分配玩家</button>
        </form>

        <!-- Current Player Assignments -->
        <h2 class="text-lg font-semibold mb-2">当前玩家分配</h2>
        <div class="bg-white p-4 rounded-lg shadow mb-8">
            <?php
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, cp.player_tag
                FROM cities c
                LEFT JOIN city_players cp ON c.id = cp.city_id AND c.game_id = cp.game_id
                WHERE c.game_id = ? AND c.type = 'city'
                ORDER BY c.name
            ");
            $stmt->execute([$game_id]);
            $city_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (empty($city_assignments)): ?>
                <p class="text-gray-500">尚未创建城市。</p>
            <?php else: ?>
                <ul class="list-disc pl-5">
                    <?php foreach ($city_assignments as $assignment): ?>
                        <li>
                            <?php echo htmlspecialchars($assignment['name']); ?> - 玩家: 
                            <?php echo $assignment['player_tag'] ? htmlspecialchars($assignment['player_tag']) : '未分配'; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- 自定义字段名称 -->
        <h2 class="text-lg font-semibold mb-2">自定义字段名称</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="update_field_names" value="1">
            <label class="block text-sm font-medium mb-1">居民数量 (原: 人口+population字段)</label>
            <input type="text" name="field_names[population]" value="<?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">物资储备 (原: 资源+resources累加字段)</label>
            <input type="text" name="field_names[resources]" value="<?php echo htmlspecialchars($field_names['resources'] ?? '物资储备'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">财富指数 (原: 经济+economy累加字段)</label>
            <input type="text" name="field_names[economy]" value="<?php echo htmlspecialchars($field_names['economy'] ?? '财富指数'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">民兵守城动员指数 (原: 军事+military字段)</label>
            <input type="text" name="field_names[military]" value="<?php echo htmlspecialchars($field_names['military'] ?? '民兵守城动员指数'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">城市化指数 (原: 文化+culture字段)</label>
            <input type="text" name="field_names[culture]" value="<?php echo htmlspecialchars($field_names['culture'] ?? '城市化指数'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">税收指数 (原: 科技+science字段)</label>
            <input type="text" name="field_names[science]" value="<?php echo htmlspecialchars($field_names['science'] ?? '税收指数'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">农业指数 (原: 基础设施+infrastructure字段)</label>
            <input type="text" name="field_names[infrastructure]" value="<?php echo htmlspecialchars($field_names['infrastructure'] ?? '农业指数'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">土地承载极限 (原: 健康+health字段)</label>
            <input type="text" name="field_names[health]" value="<?php echo htmlspecialchars($field_names['health'] ?? '土地承载极限'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">文明等级 (原: 教育+education字段)</label>
            <input type="text" name="field_names[education]" value="<?php echo htmlspecialchars($field_names['education'] ?? '文明等级'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">社会稳定 (原: 稳定性+stability字段)</label>
            <input type="text" name="field_names[stability]" value="<?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">耗粮 (原: 耗粮+food_consumption字段)</label>
            <input type="text" name="field_names[food_consumption]" value="<?php echo htmlspecialchars($field_names['food_consumption'] ?? '耗粮'); ?>" class="w-full p-2 mb-2 border rounded">
            <label class="block text-sm font-medium mb-1">耗钱 (原: 耗钱+money_consumption字段)</label>
            <input type="text" name="field_names[money_consumption]" value="<?php echo htmlspecialchars($field_names['money_consumption'] ?? '耗钱'); ?>" class="w-full p-2 mb-2 border rounded">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">更新字段名称</button>
        </form>

        <!-- 字段公式设置 -->
        <h2 class="text-lg font-semibold mb-2">字段公式设置</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="update_formulas" value="1">
            <?php
            $stmt = $pdo->prepare("SELECT field_name, formula FROM game_formulas WHERE game_id = ?");
            $stmt->execute([$game_id]);
            $formulas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            ?>
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['economy'] ?? '财富指数'); ?>公式</label>
            <input type="text" name="formulas[economy]" value="<?php echo htmlspecialchars($formulas['economy'] ?? 'population * 0.1 + military * 0.05'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: population * 0.1 + military * 0.05">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['resources'] ?? '物资储备'); ?>公式</label>
            <input type="text" name="formulas[resources]" value="<?php echo htmlspecialchars($formulas['resources'] ?? 'infrastructure * 0.2 + science * 0.1'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: infrastructure * 0.2 + science * 0.1">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?>增长率公式</label>
            <input type="text" name="formulas[population_growth]" value="<?php echo htmlspecialchars($formulas['population_growth'] ?? 'health * 0.01 + education * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: health * 0.01 + education * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['military'] ?? '民兵守城动员指数'); ?>增长率公式</label>
            <input type="text" name="formulas[military_growth]" value="<?php echo htmlspecialchars($formulas['military_growth'] ?? 'economy * 0.005 + stability * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: economy * 0.005 + stability * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['culture'] ?? '城市化指数'); ?>增长率公式</label>
            <input type="text" name="formulas[culture_growth]" value="<?php echo htmlspecialchars($formulas['culture_growth'] ?? 'education * 0.015 + stability * 0.005'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: education * 0.015 + stability * 0.005">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['science'] ?? '税收指数'); ?>增长率公式</label>
            <input type="text" name="formulas[science_growth]" value="<?php echo htmlspecialchars($formulas['science_growth'] ?? 'education * 0.02 + infrastructure * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: education * 0.02 + infrastructure * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['infrastructure'] ?? '农业指数'); ?>增长率公式</label>
            <input type="text" name="formulas[infrastructure_growth]" value="<?php echo htmlspecialchars($formulas['infrastructure_growth'] ?? 'economy * 0.01 + resources * 0.005'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: economy * 0.01 + resources * 0.005">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['health'] ?? '土地承载极限'); ?>增长率公式</label>
            <input type="text" name="formulas[health_growth]" value="<?php echo htmlspecialchars($formulas['health_growth'] ?? 'infrastructure * 0.01 + stability * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: infrastructure * 0.01 + stability * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['education'] ?? '文明等级'); ?>增长率公式</label>
            <input type="text" name="formulas[education_growth]" value="<?php echo htmlspecialchars($formulas['education_growth'] ?? 'science * 0.015 + culture * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: science * 0.015 + culture * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?>增长率公式</label>
            <input type="text" name="formulas[stability_growth]" value="<?php echo htmlspecialchars($formulas['stability_growth'] ?? 'health * 0.01 + education * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: health * 0.01 + education * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['food_consumption'] ?? '耗粮'); ?>公式</label>
            <input type="text" name="formulas[food_consumption]" value="<?php echo htmlspecialchars($formulas['food_consumption'] ?? 'population * 0.01'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: population * 0.01">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['money_consumption'] ?? '耗钱'); ?>公式</label>
            <input type="text" name="formulas[money_consumption]" value="<?php echo htmlspecialchars($formulas['money_consumption'] ?? 'economy * 0.005'); ?>" class="w-full p-2 mb-2 border rounded" placeholder="例如: economy * 0.005">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">更新公式</button>
        </form>

        <!-- Manage Rounds -->
        <h2 class="text-lg font-semibold mb-2">管理回合</h2>
        <form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="end_round" value="1">
            <button type="submit" class="bg-purple-500 text-white p-2 rounded hover:bg-purple-600">结束当前回合并开始新回合</button>
        </form>

        <a href="index.php?game_id=<?php echo $game_id; ?>" class="mt-4 inline-block text-blue-500 hover:underline">返回游戏</a>
        <a href="lobby.php" class="mt-4 inline-block text-blue-500 hover:underline ml-4">返回大厅</a>
    </div>

    <script>
        const typeSelect = document.querySelector('select[name="type"]');
        const cityFields = document.querySelector('.city-fields');
        const nonCityFields = document.querySelector('.non-city-fields');

        function updateFields() {
            const type = typeSelect.value;
            cityFields.style.display = type === 'city' ? 'block' : 'none';
            nonCityFields.style.display = (type === 'ocean' || type === 'mountain') ? 'block' : 'none';
        }

        typeSelect.addEventListener('change', updateFields);
        updateFields(); // Initialize on page load
    </script>
</body>
</html>
