<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $task_id = $_POST['task_id'];
        $amount = $_POST['amount'];

        $stmt = $pdo->prepare("SELECT form, fixed_equity FROM tasks WHERE36 id = ? AND status = 'Approved'");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task && in_array($task['form'], ['Crowdfunding', 'Hybrid'])) {
            $stmt = $pdo->prepare("INSERT INTO task_purchases (task_id, user_id, amount, equity_percentage) VALUES (?, ?, ?, ?)");
            $equity_percentage = $task['form'] === 'Hybrid' ? ($amount / $task['fixed_equity'] * (100 - $task['fixed_equity'])) : 0;
            $stmt->execute([$task_id, $_SESSION['user_id'], $amount, $equity_percentage]);
            header('Location: my_tasks.php');
            exit;
        } else {
            $error = 'Invalid task or form.';
        }
    }

    $stmt = $pdo->query("SELECT t.*, u.username AS creator FROM tasks t JOIN users u ON t.creator_id = u.id WHERE t.status = 'Approved' AND t.form IN ('Crowdfunding', 'Hybrid')");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in purchase.php: " . $e->getMessage());
    $error = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Tasks</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-2">
    <div class="container mx-auto max-w-[90%]">
        <h1 class="text-lg font-bold mb-2 text-center">Purchase Tasks</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="grid gap-2">
            <?php foreach ($tasks as $task): ?>
                <div class="bg-white p-3 rounded-lg shadow">
                    <p class="text-xs">Code: <?php echo htmlspecialchars($task['code']); ?></p>
                    <p class="text-xs">Task Name: <?php echo htmlspecialchars($task['task_name']); ?></p>
                    <p class="text-xs">Price: <?php echo htmlspecialchars($task['latest_price']); ?></p>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <input type="number" name="amount" required step="0.01" class="p-1 border rounded text-xs w-full mb-1">
                        <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">Purchase</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-2 flex gap-2">
            <a href="lobby.php" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 text-xs">Back to Lobby</a>
        </div>
    </div>
</body>
</html>