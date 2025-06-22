<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Validate user session
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        throw new Exception('User not authenticated.');
    }
    $user_id = (int)$_SESSION['user_id'];
    error_log("User ID: $user_id | File: " . __FILE__ . " | Line: " . __LINE__);

    // Initialize variables
    $user_points = 0;
    $created_tasks = [];
    $owned_equity = [];
    $task_history = [];

    // Fetch user points
    $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user_points = (float)($stmt->fetchColumn() ?: 0);

    // Fetch created tasks
    $stmt = $pdo->prepare('
        SELECT t.id, t.code, t.task_name, t.task_state, t.rating, t.total_shares, t.own_shares,
               t.latest_price, te.shares_owned
        FROM tasks t
        LEFT JOIN task_equity te ON t.id = te.task_id AND te.user_id = ?
        WHERE t.creator_id = ?
    ');
    $stmt->execute([$user_id, $user_id]);
    $created_tasks_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Raw Created Tasks: " . json_encode($created_tasks_raw) . " | File: " . __FILE__ . " | Line: " . __LINE__);

    // Remove duplicates by task_id
    $created_tasks = [];
    foreach ($created_tasks_raw as $task) {
        $task_id = $task['id'];
        if (!isset($created_tasks[$task_id])) {
            $created_tasks[$task_id] = $task;
        }
    }
    $created_tasks = array_values($created_tasks);

    // Calculate equity percentage and current value for created tasks
    foreach ($created_tasks as &$task) {
        $task['shares_owned'] = (int)($task['shares_owned'] ?: 0);
        $task['latest_price'] = (float)($task['latest_price'] ?: 0);
        $task['equity_percentage'] = $task['total_shares'] > 0 ? ($task['shares_owned'] / $task['total_shares'] * 100) : 0;
        $task['current_value'] = $task['shares_owned'] * $task['latest_price'];
    }
    unset($task); // Unset reference to avoid issues
    error_log("Processed Created Tasks: " . json_encode($created_tasks) . " | File: " . __FILE__ . " | Line: " . __LINE__);

    // Fetch owned equity
    $stmt = $pdo->prepare('
        SELECT t.id, t.code, t.task_name, t.latest_price, t.total_shares, te.shares_owned
        FROM task_equity te
        JOIN tasks t ON te.task_id = t.id
        WHERE te.user_id = ?
    ');
    $stmt->execute([$user_id]);
    $owned_equity_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Raw Owned Equity: " . json_encode($owned_equity_raw) . " | File: " . __FILE__ . " | Line: " . __LINE__);

    // Remove duplicates by task_id
    $owned_equity = [];
    foreach ($owned_equity_raw as $equity) {
        $task_id = $equity['id'];
        if (!isset($owned_equity[$task_id])) {
            $owned_equity[$task_id] = $equity;
        }
    }
    $owned_equity = array_values($owned_equity);

    // Calculate equity percentage and current value for owned equity
    foreach ($owned_equity as &$equity) {
        $equity['shares_owned'] = (int)($equity['shares_owned'] ?: 0);
        $equity['latest_price'] = (float)($equity['latest_price'] ?: 0);
        $equity['equity_percentage'] = $equity['total_shares'] > 0 ? ($equity['shares_owned'] / $equity['total_shares'] * 100) : 0;
        $equity['current_value'] = $equity['shares_owned'] * $equity['latest_price'];
    }
    unset($equity); // Unset reference to avoid issues
    error_log("Processed Owned Equity: " . json_encode($owned_equity) . " | File: " . __FILE__ . " | Line: " . __LINE__);

    // Fetch task history
    $stmt = $pdo->prepare('
        SELECT th.created_at, th.points_spent, t.id AS task_id, t.code, t.task_name
        FROM task_history th
        JOIN tasks t ON th.task_id = t.id
        WHERE th.user_id = ?
        ORDER BY th.created_at DESC
    ');
    $stmt->execute([$user_id]);
    $task_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Error in my_tasks.php: ' . $e->getMessage() . ' | File: ' . __FILE__ . ' | Line: ' . __LINE__);
    $error = 'Error: ' . htmlspecialchars($e->getMessage());
    $user_points = 0;
    $created_tasks = [];
    $owned_equity = [];
    $task_history = [];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>我的任务</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        body, html { margin: 0; padding: 0; overflow-x: hidden; width: 100vw; }
        .container { max-width: 100%; overflow-x: hidden; }
        .toggle-section { cursor: pointer; transition: background-color 0.2s; }
        .toggle-section:hover { background-color: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-2">
    <div class="container mx-auto max-w-[90%]">
        <h1 class="text-lg font-bold mb-2 text-center">我的任务</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($error); unset($error); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-green-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <!-- User Points Section -->
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1 toggle-section p-2 bg-gray-200 rounded" onclick="toggleSection('user-points')">用户积分</h2>
            <div id="user-points" class="bg-white p-3 rounded-lg shadow hidden">
                <p class="text-xs">当前积分: <?php echo number_format($user_points, 2); ?></p>
            </div>
        </div>

        <!-- Created Tasks Section -->
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1 toggle-section p-2 bg-gray-200 rounded" onclick="toggleSection('created-tasks')">创建的任务</h2>
            <div id="created-tasks" class="grid gap-2 hidden">
                <?php if (empty($created_tasks)): ?>
                    <p class="text-xs text-center">未创建任何任务。</p>
                <?php else: ?>
                    <?php foreach ($created_tasks as $task): ?>
                        <div class="bg-white p-3 rounded-lg shadow">
                            <p class="text-xs">代码: <?php echo htmlspecialchars($task['code']); ?></p>
                            <p class="text-xs">任务名称: <a href="task_detail.php?task_id=<?php echo $task['id']; ?>" class="text-blue-500 hover:underline"><?php echo htmlspecialchars($task['task_name']); ?></a></p>
                            <p class="text-xs">状态: <?php echo htmlspecialchars($task['task_state']); ?></p>
                            <p class="text-xs">评级: <?php echo htmlspecialchars($task['rating']); ?></p>
                            <p class="text-xs">总任务数: <?php echo htmlspecialchars(number_format($task['total_shares'])); ?></p>
                            <p class="text-xs">自持任务数: <?php echo htmlspecialchars(number_format($task['own_shares'])); ?></p>
                            <p class="text-xs">参与比例: <?php echo number_format($task['equity_percentage'], 1); ?>%</p>
                            <p class="text-xs">当前价值: <?php echo number_format($task['current_value'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Owned Equity Section -->
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1 toggle-section p-2 bg-gray-200 rounded" onclick="toggleSection('owned-equity')">持有的任务</h2>
            <div id="owned-equity" class="grid gap-2 hidden">
                <?php if (empty($owned_equity)): ?>
                    <p class="text-xs text-center">未持有任何任务。</p>
                <?php else: ?>
                    <?php foreach ($owned_equity as $equity): ?>
                        <div class="bg-white p-3 rounded-lg shadow">
                            <p class="text-xs">代码: <?php echo htmlspecialchars($equity['code']); ?></p>
                            <p class="text-xs">任务名称: <a href="task_detail.php?task_id=<?php echo $equity['id']; ?>" class="text-blue-500 hover:underline"><?php echo htmlspecialchars($equity['task_name']); ?></a></p>
                            <p class="text-xs">总任务数: <?php echo htmlspecialchars(number_format($equity['total_shares'])); ?></p>
                            <p class="text-xs">持有任务数: <?php echo htmlspecialchars(number_format($equity['shares_owned'])); ?></p>
                            <p class="text-xs">参与比例: <?php echo number_format($equity['equity_percentage'], 1); ?>%</p>
                            <p class="text-xs">当前价值: <?php echo number_format($equity['current_value'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Task History Section -->
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1 toggle-section p-2 bg-gray-200 rounded" onclick="toggleSection('task-history')">任务历史</h2>
            <div id="task-history" class="grid gap-2 hidden">
                <?php if (empty($task_history)): ?>
                    <p class="text-xs text-center">暂无交易历史。</p>
                <?php else: ?>
                    <?php foreach ($task_history as $history): ?>
                        <div class="bg-white p-3 rounded-lg shadow">
                            <p class="text-xs">任务代码: <?php echo htmlspecialchars($history['code']); ?></p>
                            <p class="text-xs">任务名称: <a href="task_detail.php?task_id=<?php echo $history['task_id']; ?>" class="text-blue-500 hover:underline"><?php echo htmlspecialchars($history['task_name']); ?></a></p>
                            <p class="text-xs">花费积分: <?php echo number_format($history['points_spent'], 2); ?></p>
                            <p class="text-xs">创建时间: <?php echo htmlspecialchars($history['created_at']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-2 flex gap-2 justify-center">
            <a href="lobby.php" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 text-xs">返回大厅</a>
        </div>
    </div>
    <script>
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.classList.toggle('hidden');
        }
    </script>
</body>
</html>