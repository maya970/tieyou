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
    if (!isset($_GET['task_id']) || !is_numeric($_GET['task_id'])) {
        throw new Exception('Invalid task ID.');
    }

    $task_id = (int)$_GET['task_id'];
    $stmt = $pdo->prepare("
        SELECT t.*, u.username AS creator, SUM(tp.shares) as shares_sold,
               ((t.latest_price - t.opening_price) / t.opening_price * 100) as change_percentage
        FROM tasks t
        JOIN users u ON t.creator_id = u.id
        LEFT JOIN task_purchases tp ON t.id = tp.task_id
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception('Task not found.');
    }
} catch (Exception $e) {
    error_log("Error in task_detail.php: " . $e->getMessage());
    $error = 'Error: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Task Details</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        body, html { margin: 0; padding: 0; overflow-x: hidden; width: 100vw; }
        .container { max-width: 100%; overflow-x: hidden; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-2 flex-grow">
        <h1 class="text-lg font-bold mb-2 text-center pt-2">Task Details</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($error); ?></p>
        <?php else: ?>
            <div class="bg-white p-3 rounded-lg shadow mx-auto max-w-[90%] sm:max-w-md">
                <p class="text-xs sm:text-sm"><strong>Code:</strong> <?php echo htmlspecialchars($task['code']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Rating:</strong> <?php echo htmlspecialchars($task['rating']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Task Name:</strong> <?php echo htmlspecialchars($task['task_name']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Latest Price:</strong> <?php echo htmlspecialchars($task['latest_price']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Change Percentage:</strong> <span class="<?php echo $task['change_percentage'] < 0 ? 'text-red-500' : 'text-green-500'; ?>"><?php echo number_format($task['change_percentage'], 2); ?>%</span></p>
                <p class="text-xs sm:text-sm"><strong>Form:</strong> <?php echo htmlspecialchars($task['form']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Description:</strong> <?php echo htmlspecialchars($task['description']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Creator:</strong> <?php echo htmlspecialchars($task['creator']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Status:</strong> <?php echo htmlspecialchars($task['task_state']); ?></p>
                <p class="text-xs sm:text-sm"><strong>Total Shares:</strong> <?php echo $task['total_shares']; ?></p>
                <p class="text-xs sm:text-sm"><strong>Shares Sold:</strong> <?php echo $task['shares_sold'] ?? 0; ?></p>
                <?php if ($task['task_state'] === 'Perpetual'): ?>
                    <p class="text-xs sm:text-sm"><strong>Reward Percentage:</strong> <?php echo number_format($task['reward_percentage'], 2); ?>%</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="mt-2 flex gap-2 justify-center max-w-[90%] mx-auto">
            <a href="lobby.php" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 text-xs">Back to Lobby</a>
            <a href="delegate.php" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 text-xs">Delegate Task</a>
            <a href="filter.php" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 text-xs">Filter Tasks</a>
            <a href="my_tasks.php" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 text-xs">My Tasks</a>
        </div>
    </div>
</body>
</html>