<?php
require_once 'check_auth.php';
require_once 'db.php';

$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    header('Location: lobby.php?error=无效的游戏ID。');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM autorpg_games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    header('Location: lobby.php?error=游戏不存在。');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO autorpg_events (game_id, creator_id, title, description, terrain_type, preview_image, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $game_id,
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['terrain_type'],
            $_POST['preview_image']
        ]);
        $event_id = $pdo->lastInsertId();

        $options = [
            [
                'text' => $_POST['option1_text'],
                'attribute' => $_POST['option1_attribute'],
                'threshold' => $_POST['option1_threshold'],
                'success' => $_POST['option1_success'],
                'failure' => $_POST['option1_failure']
            ],
            [
                'text' => $_POST['option2_text'],
                'attribute' => $_POST['option2_attribute'],
                'threshold' => $_POST['option2_threshold'],
                'success' => $_POST['option2_success'],
                'failure' => $_POST['option2_failure']
            ]
        ];
        if ($_POST['option3_text']) {
            $options[] = [
                'text' => $_POST['option3_text'],
                'attribute' => $_POST['option3_attribute'],
                'threshold' => $_POST['option3_threshold'],
                'success' => $_POST['option3_success'],
                'failure' => $_POST['option3_failure']
            ];
        }

        $stmt = $pdo->prepare("
            INSERT INTO autorpg_event_options (event_id, option_text, attribute, threshold, success_outcome, failure_outcome)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($options as $option) {
            $stmt->execute([
                $event_id,
                $option['text'],
                $option['attribute'],
                $option['threshold'],
                $option['success'],
                $option['failure']
            ]);
        }

        $pdo->commit();
        header('Location: autorpg_index.php?game_id=' . $game_id . '&success=事件提交成功，等待管理员审批。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: autorpg_submit_event.php?game_id=' . $game_id . '&error=事件提交失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>提交事件 - <?php echo htmlspecialchars($game['name']); ?></title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">提交事件 - <?php echo htmlspecialchars($game['name']); ?></h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <form method="POST" class="bg-white p-4 rounded-lg shadow">
            <input type="text" name="title" required class="w-full p-2 mb-2 border rounded" placeholder="事件标题">
            <textarea name="description" required class="w-full p-2 mb-2 border rounded h-24" placeholder="事件描述"></textarea>
            <select name="terrain_type" class="w-full p-2 mb-2 border rounded">
                <option value="any">任意地形</option>
                <option value="plain">平原</option>
                <option value="forest">森林</option>
                <option value="mountain">山地</option>
                <option value="ocean">海洋</option>
            </select>
            <input type="text" name="preview_image" class="w-full p-2 mb-2 border rounded" placeholder="预览图片URL">
            <h3 class="text-lg font-semibold mb-2">选项 1</h3>
            <input type="text" name="option1_text" required class="w-full p-2 mb-2 border rounded" placeholder="选项文本（如：与劫匪战斗）">
            <select name="option1_attribute" class="w-full p-2 mb-2 border rounded">
                <option value="strength">力量</option>
                <option value="health">生命</option>
                <option value="stamina">体力</option>
            </select>
            <input type="number" name="option1_threshold" required class="w-full p-2 mb-2 border rounded" placeholder="属性阈值">
            <input type="text" name="option1_success" required class="w-full p-2 mb-2 border rounded" placeholder="成功结果">
            <input type="text" name="option1_failure" required class="w-full p-2 mb-2 border rounded" placeholder="失败结果">
            <h3 class="text-lg font-semibold mb-2">选项 2</h3>
            <input type="text" name="option2_text" required class="w-full p-2 mb-2 border rounded" placeholder="选项文本">
            <select name="option2_attribute" class="w-full p-2 mb-2 border rounded">
                <option value="strength">力量</option>
                <option value="health">生命</option>
                <option value="stamina">体力</option>
            </select>
            <input type="number" name="option2_threshold" required class="w-full p-2 mb-2 border rounded" placeholder="属性阈值">
            <input type="text" name="option2_success" required class="w-full p-2 mb-2 border rounded" placeholder="成功结果">
            <input type="text" name="option2_failure" required class="w-full p-2 mb-2 border rounded" placeholder="失败结果">
            <h3 class="text-lg font-semibold mb-2">选项 3（可选）</h3>
            <input type="text" name="option3_text" class="w-full p-2 mb-2 border rounded" placeholder="选项文本">
            <select name="option3_attribute" class="w-full p-2 mb-2 border rounded">
                <option value="strength">力量</option>
                <option value="health">生命</option>
                <option value="stamina">体力</option>
            </select>
            <input type="number" name="option3_threshold" class="w-full p-2 mb-2 border rounded" placeholder="属性阈值">
            <input type="text" name="option3_success" class="w-full p-2 mb-2 border rounded" placeholder="成功结果">
            <input type="text" name="option3_failure" class="w-full p-2 mb-2 border rounded" placeholder="失败结果">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">提交事件</button>
        </form>
        <a href="autorpg_index.php?game_id=<?php echo $game_id; ?>" class="mt-4 inline-block text-blue-500 hover:underline">返回游戏</a>
    </div>
</body>
</html>