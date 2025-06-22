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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Invalid CSRF token.';
            error_log("Validation error: Invalid CSRF token.");
            header('Location: accept_task.php');
            exit;
        }

        if (isset($_POST['accept_task_id'])) {
            $task_id = $_POST['accept_task_id'];
            $stmt = $pdo->prepare("SELECT form, creator_id FROM tasks WHERE id = ? AND task_state = 'Incomplete'");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) {
                $_SESSION['error'] = 'Task not found or not available.';
                header('Location: accept_task.php');
                exit;
            }
            if ($task['form'] === 'Sole' && $task['creator_id'] != $_SESSION['user_id']) {
                $_SESSION['error'] = 'Sole tasks can only be accepted by the creator.';
                header('Location: accept_task.php');
                exit;
            }
            $stmt = $pdo->prepare("SELECT * FROM task_assignments WHERE task_id = ? AND user_id = ?");
            $stmt->execute([$task_id, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
                $stmt->execute([$task_id, $_SESSION['user_id']]);
            }
        } elseif (isset($_POST['complete_task_id'])) {
            $task_id = $_POST['complete_task_id'];
            $stmt = $pdo->prepare("UPDATE task_assignments SET completed = TRUE WHERE task_id = ? AND user_id = ?");
            $stmt->execute([$task_id, $_SESSION['user_id']]);
        } elseif (isset($_POST['approve_task_id'])) {
            $task_id = $_POST['approve_task_id'];
            $user_id = $_POST['user_id'];
            $role = $_SESSION['role'];
            $field = $role === 'super_admin' ? 'admin_approved' : 'initiator_approved';
            $stmt = $pdo->prepare("UPDATE task_assignments SET $field = TRUE WHERE task_id = ? AND user_id = ?");
            $stmt->execute([$task_id, $user_id]);

            $stmt = $pdo->prepare("SELECT initiator_approved, admin_approved, t.latest_price FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.task_id = ? AND ta.user_id = ?");
            $stmt->execute([$task_id, $user_id]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($assignment['initiator_approved'] && $assignment['admin_approved']) {
                $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                $stmt->execute([$assignment['latest_price'], $user_id]);
            }
        }
        header('Location: accept_task.php');
        exit;
    }

    $stmt = $pdo->query("SELECT t.*, u.username AS creator FROM tasks t JOIN users u ON t.creator_id = u.id WHERE t.task_state = 'Incomplete'");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT ta.*, t.code, t.task_name, t.creator_id, u2.username AS creator FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id JOIN users u2 ON t.creator_id = u2.id WHERE ta.user_id = ? OR t.creator_id = ? OR ? = 'super_admin'");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['role']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in accept_task.php: " . $e->getMessage());
    $error = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Tasks</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-2">
    <div class="container mx-auto max-w-[90%]">
        <h1 class="text-lg font-bold mb-2 text-center">Accept Tasks</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($error); unset($error); ?></p>
        <?php endif; ?>
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1">Available Tasks</h2>
            <div class="grid gap-2">
                <?php foreach ($tasks as $task): ?>
                    <div class="bg-white p-3 rounded-lg shadow">
                        <p class="text-xs">Code: <?php echo htmlspecialchars($task['code']); ?></p>
                        <p class="text-xs">Task Name: <?php echo htmlspecialchars($task['task_name']); ?></p>
                        <p class="text-xs">Price: <?php echo htmlspecialchars($task['latest_price']); ?></p>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="accept_task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">Accept</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1">My Assignments</h2>
            <div class="grid gap-2">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="bg-white p-3 rounded-lg shadow">
                        <p class="text-xs">Code: <?php echo htmlspecialchars($assignment['code']); ?></p>
                        <p class="text-xs">Task Name: <?php echo htmlspecialchars($assignment['task_name']); ?></p>
                        <p class="text-xs">Status: <?php echo $assignment['completed'] ? 'Completed' : 'In Progress'; ?></p>
                        <p class="text-xs">Initiator Approved: <?php echo $assignment['initiator_approved'] ? 'Yes' : 'No'; ?></p>
                        <p class="text-xs">Admin Approved: <?php echo $assignment['admin_approved'] ? 'Yes' : 'No'; ?></p>
                        <?php if (!$assignment['completed'] && $assignment['user_id'] == $_SESSION['user_id']): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="complete_task_id" value="<?php echo $assignment['task_id']; ?>">
                                <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">Complete</button>
                            </form>
                        <?php endif; ?>
                        <?php if (($assignment['creator_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'super_admin') && $assignment['completed']): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="approve_task_id" value="<?php echo $assignment['task_id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $assignment['user_id']; ?>">
                                <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">Approve</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mt-2 flex gap-2">
            <a href="lobby.php" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 text-xs">Back to Lobby</a>
        </div>
    </div>
</body>
</html>