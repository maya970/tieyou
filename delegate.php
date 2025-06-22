<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token.');
        }

        // Get and sanitize inputs
        $task_name = trim($_POST['task_name'] ?? '');
        $total_shares = intval($_POST['total_shares'] ?? 0);
        $own_shares = intval($_POST['own_shares'] ?? 0);
        $share_price = floatval($_POST['share_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $form = $_POST['form'] ?? '';
        $created_at = date('Y-m-d H:i:s');

        // Validate inputs
        if (empty($task_name)) {
            throw new Exception('Task name is required.');
        }
        if (strlen($task_name) > 50) {
            throw new Exception('Task name must be ¡Ü50 characters.');
        }
        if ($total_shares <= 0) {
            throw new Exception('Total shares must be positive.');
        }
        if ($own_shares <= 0) {
            throw new Exception('Own shares must be positive.');
        }
        if ($share_price <= 0) {
            throw new Exception('Share price must be positive.');
        }
        if (!in_array($form, ['Sole', 'Crowdfunding', 'Hybrid'])) {
            throw new Exception('Invalid form selected.');
        }
        if ($form === 'Sole' && $own_shares !== $total_shares) {
            throw new Exception('Sole form requires purchasing 100% of shares.');
        }
        if ($own_shares > $total_shares) {
            throw new Exception('Own shares cannot exceed total shares.');
        }

        // Check user points
        $total_cost = $own_shares * $share_price;
        $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_points = $stmt->fetchColumn();

        if ($user_points === false) {
            throw new Exception('User not found.');
        }
        if ($user_points < $total_cost) {
            throw new Exception("Insufficient points ($user_points < $total_cost).");
        }

        // Generate task code
        $rating = 'F';
        $stmt = $pdo->prepare("SELECT MAX(code) FROM tasks WHERE code LIKE ?");
        $stmt->execute(["$rating%"]);
        $last_code = $stmt->fetchColumn();
        $seq = $last_code ? intval(substr($last_code, 1, 5)) + 1 : 1;
        $code = sprintf("%s%05d-%s", $rating, $seq, substr($form, 0, 1));

        // Calculate opening price and latest price (using share_price)
        $opening_price = $share_price;
        $latest_price = $share_price;

        // Begin transaction
        $pdo->beginTransaction();

        // Insert task with fixed task_state 'Approval'
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                code, rating, task_name, opening_price, latest_price, total_shares, own_shares,
                form, description, creator_id, task_state, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approval', ?)
        ");
        $stmt->execute([
            $code, $rating, $task_name, $opening_price, $latest_price, $total_shares, $own_shares,
            $form, $description, $_SESSION['user_id'], $created_at
        ]);
        $task_id = $pdo->lastInsertId();
        $debug_messages[] = "Task ID: $task_id created with code: $code";

        // Insert into task_history
        $stmt = $pdo->prepare("
            INSERT INTO task_history (user_id, task_id, points_spent, created_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $task_id, $total_cost, $created_at]);
        $debug_messages[] = "Task history recorded for task ID: $task_id, points spent: $total_cost";

        // Insert into task_equity (updated to remove equity_percentage)
        $stmt = $pdo->prepare("
            INSERT INTO task_equity (user_id, task_id, shares_owned, created_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $task_id, $own_shares, $created_at]);
        $debug_messages[] = "Task equity recorded for task ID: $task_id, shares owned: $own_shares";

        // Deduct points
        $stmt = $pdo->prepare("UPDATE users SET points = points - ? WHERE id = ?");
        $stmt->execute([$total_cost, $_SESSION['user_id']]);
        $debug_messages[] = "Points deducted: $total_cost";

        $pdo->commit();
        $_SESSION['success'] = 'Task created successfully.';
        header('Location: my_tasks.php');
        exit;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_msg = $e->getMessage();
    $_SESSION['error'] = "Error: $error_msg";
    $debug_messages[] = "Error: $error_msg";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Delegate Task</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        body, html { margin: 0; padding: 0; overflow-x: hidden; width: 100vw; }
        .container { max-width: 100%; overflow-x: hidden; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col p-2">
    <div class="container mx-auto max-w-[90%] flex-grow">
        <h1 class="text-lg font-bold mb-2 text-center">New Task</h1>
        <?php if (!empty($debug_messages)): ?>
            <div class="bg-yellow-100 p-4 mb-4 rounded text-sm">
                <p><strong>Debug Output:</strong></p>
                <ul>
                    <?php foreach ($debug_messages as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-600 mb-4 text-center text-sm"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-green-600 mb-4 text-center text-sm"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <form method="POST" class="bg-white p-4 rounded-lg shadow-lg">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Task Name</label>
                <input type="text" name="task_name" required maxlength="50" class="w-full p-2 border rounded text-sm mt-1" value="<?php echo htmlspecialchars($_POST['task_name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Total Shares</label>
                <input type="number" name="total_shares" required min="1" class="w-full p-2 border rounded text-sm mt-1" value="<?php echo htmlspecialchars($_POST['total_shares'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Own Shares</label>
                <input type="number" name="own_shares" required min="1" class="w-full p-2 border rounded text-sm mt-1" value="<?php echo htmlspecialchars($_POST['own_shares'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Price per Share</label>
                <input type="number" name="share_price" required step="0.01" min="0.01" class="w-full p-2 border rounded text-sm mt-1" value="<?php echo htmlspecialchars($_POST['share_price'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Form</label>
                <select name="form" required class="w-full p-2 border rounded text-sm mt-1">
                    <option value="Sole" <?php echo ($_POST['form'] ?? '') === 'Sole' ? 'selected' : ''; ?>>Sole</option>
                    <option value="Crowdfunding" <?php echo ($_POST['form'] ?? '') === 'Crowdfunding' ? 'selected' : ''; ?>>Crowdfunding</option>
                    <option value="Hybrid" <?php echo ($_POST['form'] ?? '') === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Description (Optional)</label>
                <input type="text" name="description" maxlength="255" class="w-full p-2 border rounded text-sm mt-1" value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 text-sm mt-2">Submit</button>
        </form>
        <div class="mt-3 flex justify-center">
            <a href="lobby.php" class="bg-gray-500 text-white py-2 px-3 rounded hover:bg-gray-600 text-sm">Back</a>
        </div>
    </div>
</body>
</html>