<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'game_admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

$game_id = $_GET['game_id'] ?? null;
$city_id = $_GET['city_id'] ?? null;
if (!$game_id || !is_numeric($game_id) || !$city_id || !is_numeric($city_id)) {
    header('Location: admin.php?game_id=' . $game_id . '&error=无效的游戏或城市ID。');
    exit;
}

// Verify game and city
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game || ($game['creator_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'super_admin')) {
    header('Location: admin.php?game_id=' . $game_id . '&error=您无权管理此游戏。');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cities WHERE id = ? AND game_id = ?");
$stmt->execute([$city_id, $game_id]);
$city = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$city) {
    header('Location: admin.php?game_id=' . $game_id . '&error=城市或地块不存在。');
    exit;
}

// Load custom field names
$stmt = $pdo->prepare("SELECT field_name, display_name FROM game_field_names WHERE game_id = ?");
$stmt->execute([$game_id]);
$field_names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle city/tile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_city'])) {
    $name = trim($_POST['name'] ?? '');
    $x = $_POST['x'] ?? null;
    $y = $_POST['y'] ?? null;
    $type = $_POST['type'] ?? $city['type'];
    $color = trim($_POST['color'] ?? '');

    if (empty($name)) {
        header('Location: edit_city.php?game_id=' . $game_id . '&city_id=' . $city_id . '&error=名称不能为空。');
        exit;
    }
    if (!is_numeric($x) || !is_numeric($y)) {
        header('Location: edit_city.php?game_id=' . $game_id . '&city_id=' . $city_id . '&error=坐标必须为数字。');
        exit;
    }
    if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        header('Location: edit_city.php?game_id=' . $game_id . '&city_id=' . $city_id . '&error=颜色格式无效，必须为六位十六进制代码（如 #0000FF）。');
        exit;
    }

    try {
// 替换原来的 UPDATE cities 的 SQL
$stmt = $pdo->prepare("
    UPDATE cities SET
        name = ?, x = ?, y = ?, description = ?, type = ?, population = ?, resources = ?, growth_rate = ?, updated_at = NOW(),
        city_display_type = ?, city_display_value = ?, economy = ?, military = ?, military_growth = ?, culture = ?, culture_growth = ?,
        science = ?, science_growth = ?, infrastructure = ?, infrastructure_growth = ?, health = ?, health_growth = ?, education = ?,
        education_growth = ?, stability = ?, stability_growth = ?, show_name = ?, color = ?, food_consumption = ?, money_consumption = ?
    WHERE id = ? AND game_id = ?
");
$stmt->execute([
    $name,
    $x,
    $y,
    trim($_POST['description'] ?? ''),
    $type,
    $_POST['population'] ?? null, // 不再限制为仅城市类型
    $_POST['resources'] ?? null,  // 不再限制为仅城市类型
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
    $color ?: null,
    $_POST['food_consumption'] ?? null,  // 新增字段
    $_POST['money_consumption'] ?? null, // 新增字段
    $city_id,
    $game_id
]);
        header('Location: admin.php?game_id=' . $game_id . '&success=更新成功。');
        exit;
    } catch (Exception $e) {
        error_log("更新失败: " . $e->getMessage());
        header('Location: edit_city.php?game_id=' . $game_id . '&city_id=' . $city_id . '&error=更新失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle city/tile deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_city'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM cities WHERE id = ? AND game_id = ?");
        $stmt->execute([$city_id, $game_id]);
        $stmt = $pdo->prepare("DELETE FROM city_players WHERE city_id = ? AND game_id = ?");
        $stmt->execute([$city_id, $game_id]);
        $pdo->commit();
        header('Location: admin.php?game_id=' . $game_id . '&success=删除成功。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("删除失败: " . $e->getMessage());
        header('Location: edit_city.php?game_id=' . $game_id . '&city_id=' . $city_id . '&error=删除失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑 <?php echo htmlspecialchars($city['name']); ?> - <?php echo htmlspecialchars($game['name']); ?></title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">编辑 <?php echo htmlspecialchars($city['name']); ?></h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

<form method="POST" class="mb-8 bg-white p-4 rounded-lg shadow">
    <input type="hidden" name="edit_city" value="1">
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">名称</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($city['name']); ?>" required class="w-full p-2 border rounded">
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">X 坐标</label>
        <input type="number" name="x" value="<?php echo htmlspecialchars($city['x']); ?>" required class="w-full p-2 border rounded">
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Y 坐标</label>
        <input type="number" name="y" value="<?php echo htmlspecialchars($city['y']); ?>" required class="w-full p-2 border rounded">
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">颜色（六位十六进制，如 #0000FF）</label>
        <input type="text" name="color" value="<?php echo htmlspecialchars($city['color'] ?? ''); ?>" class="w-full p-2 border rounded" placeholder="#0000FF">
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">类型</label>
        <select name="type" class="w-full p-2 border rounded">
            <option value="city" <?php echo $city['type'] === 'city' ? 'selected' : ''; ?>>城市</option>
            <option value="mountain" <?php echo $city['type'] === 'mountain' ? 'selected' : ''; ?>>军团</option>
            <option value="forest" <?php echo $city['type'] === 'forest' ? 'selected' : ''; ?>>未知</option>
            <option value="ocean" <?php echo $city['type'] === 'ocean' ? 'selected' : ''; ?>>障碍</option>
        </select>
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">描述</label>
        <textarea name="description" class="w-full p-2 border rounded h-16" placeholder=""><?php echo htmlspecialchars($city['description']); ?></textarea>
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">显示类型</label>
        <select name="city_display_type" class="w-full p-2 border rounded">
            <option value="circle" <?php echo $city['city_display_type'] === 'circle' ? 'selected' : ''; ?>>圆形</option>
            <option value="image" <?php echo $city['city_display_type'] === 'image' ? 'selected' : ''; ?>>图片</option>
            <option value="text" <?php echo $city['city_display_type'] === 'text' ? 'selected' : ''; ?>>文本</option>
            <option value="none" <?php echo $city['city_display_type'] === 'none' ? 'selected' : ''; ?>>无</option>
        </select>
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">显示值（图片 URL 或文本）</label>
        <input type="text" name="city_display_value" value="<?php echo htmlspecialchars($city['city_display_value']); ?>" class="w-full p-2 border rounded" placeholder="图片 URL 或文本">
    </div>
    <div class="city-fields">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?></label>
            <input type="number" name="population" value="<?php echo htmlspecialchars($city['population']); ?>" class="w-full p-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['resources'] ?? '物资储备'); ?></label>
            <input type="number" name="resources" value="<?php echo htmlspecialchars($city['resources']); ?>" class="w-full p-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">耗粮</label>
            <input type="number" step="0.01" name="food_consumption" value="<?php echo htmlspecialchars($city['food_consumption'] ?? '0.00'); ?>" class="w-full p-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">耗钱</label>
            <input type="number" step="0.01" name="money_consumption" value="<?php echo htmlspecialchars($city['money_consumption'] ?? '0.00'); ?>" class="w-full p-2 border rounded">
        </div>
        <?php if ($city['type'] === 'city'): ?>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="growth_rate" value="<?php echo htmlspecialchars($city['growth_rate']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['economy'] ?? '财富指数'); ?></label>
                <input type="number" name="economy" value="<?php echo htmlspecialchars($city['economy']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['military'] ?? '军力水平'); ?></label>
                <input type="number" name="military" value="<?php echo htmlspecialchars($city['military']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['military'] ?? '军力水平'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="military_growth" value="<?php echo htmlspecialchars($city['military_growth']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['culture'] ?? '文化影响力'); ?></label>
                <input type="number" name="culture" value="<?php echo htmlspecialchars($city['culture']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['culture'] ?? '文化影响力'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="culture_growth" value="<?php echo htmlspecialchars($city['culture_growth']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['science'] ?? '科技进展'); ?></label>
                <input type="number" name="science" value="<?php echo htmlspecialchars($city['science']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['science'] ?? '科技进展'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="science_growth" value="<?php echo htmlspecialchars($city['science_growth']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['infrastructure'] ?? '基础建设'); ?></label>
                <input type="number" name="infrastructure" value="<?php echo htmlspecialchars($city['infrastructure']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['infrastructure'] ?? '基础建设'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="infrastructure_growth" value="<?php echo htmlspecialchars($city['infrastructure_growth']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['health'] ?? '公共卫生'); ?></label>
                <input type="number" name="health" value="<?php echo htmlspecialchars($city['health']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['health'] ?? '公共卫生'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="health_growth" value="<?php echo htmlspecialchars($city['health_growth']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['education'] ?? '教育水平'); ?></label>
                <input type="number" name="education" value="<?php echo htmlspecialchars($city['education']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['education'] ?? '教育水平'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="education_growth" value="<?php echo htmlspecialchars($city['education_growth']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?></label>
                <input type="number" name="stability" value="<?php echo htmlspecialchars($city['stability']); ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1"><?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?>增长率 (%)</label>
                <input type="number" step="0.01" name="stability_growth" value="<?php echo htmlspecialchars($city['stability_growth']); ?>" class="w-full p-2 border rounded">
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">
            <input type="checkbox" name="show_name" <?php echo $city['show_name'] ? 'checked' : ''; ?>>
            显示名称
        </label>
    </div>
    <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 mr-2">更新</button>
</form>

        <form method="POST" class="mb-8">
            <input type="hidden" name="delete_city" value="1">
            <button type="submit" class="bg-red-500 text-white p-2 rounded hover:bg-red-600" onclick="return confirm('确定删除此城市/地块？');">删除</button>
        </form>

        <a href="admin.php?game_id=<?php echo $game_id; ?>" class="text-blue-500 hover:underline">返回管理</a>
    </div>

<script>
    document.querySelector('select[name="type"]').addEventListener('change', function() {
        const cityFields = document.querySelector('.city-fields');
        cityFields.style.display = 'block'; // 始终显示
    });
</script>
</body>
</html>