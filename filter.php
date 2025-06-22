<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $filters = [];
    $params = [];
    $query = "SELECT t.*, u.username AS creator, ((t.latest_price - t.opening_price) / t.opening_price * 100) as change_percentage
              FROM tasks t JOIN users u ON t.creator_id = u.id WHERE t.task_state = 'Incomplete'";
    
    if (!empty($_GET['code'])) {
        $filters[] = "t.code LIKE ?";
        $params[] = "%" . $_GET['code'] . "%";
    }
    if (!empty($_GET['rating'])) {
        $filters[] = "t.rating = ?";
        $params[] = $_GET['rating'];
    }
    if (!empty($_GET['task_name'])) {
        $filters[] = "t.task_name LIKE ?";
        $params[] = "%" . $_GET['task_name'] . "%";
    }
    if (!empty($_GET['form'])) {
        $filters[] = "t.form = ?";
        $params[] = $_GET['form'];
    }
    if (!empty($_GET['description'])) {
        $filters[] = "t.description LIKE ?";
        $params[] = "%" . $_GET['description'] . "%";
    }
    
    if ($filters) {
        $query .= " AND " . implode(" AND ", $filters);
    }
    
    $sort = $_GET['sort'] ?? 'latest_price';
    $order = $_GET['order'] ?? 'DESC';
    $query .= " ORDER BY t.$sort $order LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in filter.php: " . $e->getMessage());
    $error = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Filter Tasks</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        body, html { margin: 0; padding: 0; overflow-x: hidden; width: 100vw; }
        .container { max-width: 100%; overflow-x: hidden; }
        .table-container { width: 100vw; margin-left: calc(-50vw + 50%); }
        table { table-layout: fixed; width: 100%; }
        th, td { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        tr { cursor: pointer; }
        tr:hover { background-color: #4a5568; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="container mx-auto max-w-[90%] px-2 flex-grow">
        <h1 class="text-lg font-bold mb-2 text-center text-lg pt-2">Filter Tasks</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($error); unset($error); ?></p>
        <?php endif; ?>
        <form method="GET" class="bg-white p-3 rounded-lg shadow mb-2">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs">Code</label>
                    <input type="text" name="code" value="<?php echo htmlspecialchars($_GET['code'] ?? ''); ?>" class="w-full p-1 border rounded text-xs">
                </div>
                <div>
                    <label class="block text-xs">Rating</label>
                    <select name="rating" class="w-full p-1 border rounded text-xs">
                        <option value="">All</option>
                        <option value="EX">EX</option>
                        <option value="SSS">SSS</option>
                        <option value="S">S</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs">Task Name</label>
                    <input type="text" name="task_name" value="<?php echo htmlspecialchars($_GET['task_name'] ?? ''); ?>" class="w-full p-1 border rounded text-xs">
                </div>
                <div>
                    <label class="block text-xs">Form</label>
                    <select name="form" class="w-full p-1 border rounded text-xs">
                        <option value="">All</option>
                        <option value="Sole">Sole</option>
                        <option value="Crowdfunding">Crowdfunding</option>
                        <option value="Joint Venture">Joint Venture</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs">Description</label>
                    <input type="text" name="description" value="<?php echo htmlspecialchars($_GET['description'] ?? ''); ?>" class="w-full p-1 border rounded text-xs">
                </div>
                <div>
                    <label class="block text-xs">Sort By</label>
                    <select name="sort" class="w-full p-1 border rounded text-xs">
                        <option value="latest_price" <?php echo ($_GET['sort'] ?? '') === 'latest_price' ? 'selected' : ''; ?>>Latest Price</option>
                        <option value="change_percentage" <?php echo ($_GET['sort'] ?? '') === 'change_percentage' ? 'selected' : ''; ?>>Change Percentage</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs">Order</label>
                    <select name="order" class="w-full p-1 border rounded text-xs">
                        <option value="ASC" <?php echo ($_GET['order'] ?? '') === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo ($_GET['order'] ?? '') === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 text-xs mt-2">Filter</button>
        </form>
        <div class="table-container overflow-x-hidden">
            <table class="min-w-full bg-gray-800 text-white text-xs sm:text-sm">
                <thead>
                    <tr>
                        <th class="py-1 px-1">代码</th>
                        <th class="py-1 px-1">评级</th>
                        <th class="py-1 px-1">任务名称</th>
                        <th class="py-1 px-1">最新价</th>
                        <th class="py-1 px-1">涨跌幅</th>
                        <th class="py-1 px-1">形式</th>
                        <th class="py-1 px-1">简述</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="text-center" onclick="window.location.href='task_detail.php?task_id=<?php echo $task['id']; ?>'">
                            <td class="py-1 px-1"><?php echo htmlspecialchars($task['code']); ?></td>
                            <td class="py-1 px-1"><?php echo htmlspecialchars($task['rating']); ?></td>
                            <td class="py-1 px-1"><?php echo htmlspecialchars($task['task_name']); ?></td>
                            <td class="py-1 px-1"><?php echo htmlspecialchars($task['latest_price']); ?></td>
                            <td class="<?php echo $task['change_percentage'] < 0 ? 'text-red-500' : 'text-green-500'; ?> py-1 px-1"><?php echo number_format($task['change_percentage'], 2); ?>%</td>
                            <td class="py-1 px-1"><?php echo htmlspecialchars($task['form']); ?></td>
                            <td class="py-1 px-1"><?php echo htmlspecialchars($task['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-2 flex gap-2 justify-center">
            <a href="lobby.php" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 text-xs">Back to Lobby</a>
        </div>
    </div>
</body>
</html>