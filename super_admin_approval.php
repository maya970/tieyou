<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

// Restrict to super_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    error_log("Access denied for user_id: " . ($_SESSION['user_id'] ?? 'unset') . " | File: " . __FILE__ . " | Line: " . __LINE__);
    $_SESSION['error'] = 'Access denied.';
    header('Location: lobby.php');
    exit;
}

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Validate session
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        error_log("Invalid or missing user_id in session | File: " . __FILE__ . " | Line: " . __LINE__);
        throw new Exception("User not authenticated");
    }
    $user_id = (int)$_SESSION['user_id'];

    // Initialize data arrays
    $tasks = [];
    $task_equity = [];
    $task_history = [];
    $task_acceptances = [];

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("Invalid CSRF token for user_id: $user_id | File: " . __FILE__ . " | Line: " . __LINE__);
            throw new Exception("Invalid CSRF token");
        }

        $pdo->beginTransaction();

        // Task update or delete
        if (isset($_POST['task_id']) && isset($_POST['action'])) {
            $task_id = (int)$_POST['task_id'];
            $action = $_POST['action'];

            if ($action === 'update') {
                $rating = in_array($_POST['rating'], ['EX', 'SSS', 'S', 'A', 'B', 'C', 'D', 'E', 'F']) ? $_POST['rating'] : 'F';
                $task_state = in_array($_POST['task_state'], ['Approval', 'Open', 'Perpetual', 'Completed']) ? $_POST['task_state'] : 'Approval';
                $total_shares = max(0, (int)$_POST['total_shares']);
                $own_shares = max(0, (int)$_POST['own_shares']);
                $task_name = isset($_POST['task_name']) ? substr(trim(htmlspecialchars($_POST['task_name'])), 0, 50) : '';
                $description = isset($_POST['description']) ? substr(trim(htmlspecialchars($_POST['description'])), 0, 255) : NULL;
                $form = in_array($_POST['form'], ['Sole', 'Crowdfunding', 'Hybrid']) ? $_POST['form'] : 'Sole';

                if (empty($task_name)) {
                    throw new Exception("Task name is required");
                }

                error_log("Updating task_id: $task_id with rating: $rating, task_state: $task_state, total_shares: $total_shares, own_shares: $own_shares, task_name: $task_name, description: $description, form: $form | File: " . __FILE__ . " | Line: " . __LINE__);
                $stmt = $pdo->prepare("
                    UPDATE tasks
                    SET rating = ?, task_state = ?, total_shares = ?, own_shares = ?, task_name = ?, description = ?, form = ?
                    WHERE id = ?
                ");
                $stmt->execute([$rating, $task_state, $total_shares, $own_shares, $task_name, $description, $form, $task_id]);

                // Update task code
                $stmt = $pdo->prepare("SELECT code, form FROM tasks WHERE id = ?");
                $stmt->execute([$task_id]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($task) {
                    $old_code = $task['code'];
                    $form_suffix = substr($form, 0, 1); // Use updated form
                    $seq = intval(substr($old_code, 1, 5));
                    $new_code = sprintf("%s%05d-%s", $rating, $seq, $form_suffix);
                    $stmt = $pdo->prepare("UPDATE tasks SET code = ? WHERE id = ?");
                    $stmt->execute([$new_code, $task_id]);
                    error_log("Updated task_id: $task_id code from $old_code to $new_code | File: " . __FILE__ . " | Line: " . __LINE__);
                }
            } elseif ($action === 'delete') {
                error_log("Deleting task_id: $task_id | File: " . __FILE__ . " | Line: " . __LINE__);
                $stmt = $pdo->prepare("DELETE FROM task_acceptances WHERE task_id = ?");
                $stmt->execute([$task_id]);
                $stmt = $pdo->prepare("DELETE FROM task_equity WHERE task_id = ?");
                $stmt->execute([$task_id]);
                $stmt = $pdo->prepare("DELETE FROM task_history WHERE task_id = ?");
                $stmt->execute([$task_id]);
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$task_id]);
            }
        }

        // Update task_equity shares
        if (isset($_POST['equity_id']) && isset($_POST['action']) && $_POST['action'] === 'update_equity') {
            $equity_id = (int)$_POST['equity_id'];
            $shares_owned = max(0, (int)$_POST['shares_owned']);
            error_log("Updating equity_id: $equity_id with shares_owned: $shares_owned | File: " . __FILE__ . " | Line: " . __LINE__);
            $stmt = $pdo->prepare("UPDATE task_equity SET shares_owned = ? WHERE id = ?");
            $stmt->execute([$shares_owned, $equity_id]);
        }

        // Handle task acceptances
        if (isset($_POST['acceptance_action'])) {
            $action = $_POST['acceptance_action'];
            $task_id = (int)$_POST['task_id'];

            if ($action === 'approve_selection') {
                $acceptance_id = (int)$_POST['acceptance_id'];
                $admin_status = in_array($_POST['admin_status'], ['Approved', 'Perpetual', 'Rejected']) ? $_POST['admin_status'] : 'Pending';
                error_log("Updating acceptance_id: $acceptance_id with admin_status: $admin_status | File: " . __FILE__ . " | Line: " . __LINE__);
                $stmt = $pdo->prepare("UPDATE task_acceptances SET admin_status = ? WHERE id = ?");
                $stmt->execute([$admin_status, $acceptance_id]);
            } elseif ($action === 'mark_completed') {
                $acceptance_ids = isset($_POST['acceptance_ids']) ? array_map('intval', $_POST['acceptance_ids']) : [];
                $period = ($task_state === 'Perpetual') ? max(1, (int)$_POST['completion_period']) : 0;
                error_log("Marking " . count($acceptance_ids) . " acceptances as completed for task_id: $task_id, period: $period | File: " . __FILE__ . " | Line: " . __LINE__);
                if (!empty($acceptance_ids)) {
                    $placeholders = implode(',', array_fill(0, count($acceptance_ids), '?'));
                    $stmt = $pdo->prepare("
                        UPDATE task_acceptances
                        SET completion_status = 'Completed', completion_period = ?, completed_at = NOW()
                        WHERE id IN ($placeholders)
                    ");
                    $stmt->execute(array_merge([$period], $acceptance_ids));
                }
            }
        }

        $pdo->commit();
        $_SESSION['success'] = 'Action completed successfully.';
        header('Location: super_admin_approval.php');
        exit;
    }

    // Fetch tasks
    try {
        error_log("Fetching tasks | File: " . __FILE__ . " | Line: " . __LINE__);
        $stmt = $pdo->prepare("
            SELECT t.*, u.username AS creator
            FROM tasks t
            JOIN users u ON t.creator_id = u.id
            ORDER BY t.created_at DESC
        ");
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Fetched " . count($tasks) . " tasks | File: " . __FILE__ . " | Line: " . __LINE__);
    } catch (Exception $e) {
        error_log("Error fetching tasks: " . $e->getMessage() . " | File: " . __FILE__ . " | Line: " . __LINE__);
        throw new Exception("Failed to fetch tasks: " . $e->getMessage());
    }

    // Fetch data for a specific task if queried
    if (isset($_GET['task_id']) && is_numeric($_GET['task_id'])) {
        $task_id = (int)$_GET['task_id'];

        // Fetch task_equity
        try {
            error_log("Fetching task_equity for task_id: $task_id | File: " . __FILE__ . " | Line: " . __LINE__);
            $stmt = $pdo->prepare("
                SELECT te.*, u.username AS user, t.code AS task_code
                FROM task_equity te
                JOIN users u ON te.user_id = u.id
                JOIN tasks t ON te.task_id = t.id
                WHERE te.task_id = ?
            ");
            $stmt->execute([$task_id]);
            $task_equity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fetched " . count($task_equity) . " task_equity records | File: " . __FILE__ . " | Line: " . __LINE__);
        } catch (Exception $e) {
            error_log("Error fetching task_equity: " . $e->getMessage() . " | File: " . __FILE__ . " | Line: " . __LINE__);
            throw new Exception("Failed to fetch task_equity: " . $e->getMessage());
        }

        // Fetch task_history
        try {
            error_log("Fetching task_history for task_id: $task_id | File: " . __FILE__ . " | Line: " . __LINE__);
            $stmt = $pdo->prepare("
                SELECT th.*, u.username AS user, t.code AS task_code
                FROM task_history th
                JOIN users u ON th.user_id = u.id
                JOIN tasks t ON th.task_id = t.id
                WHERE th.task_id = ?
            ");
            $stmt->execute([$task_id]);
            $task_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fetched " . count($task_history) . " task_history records | File: " . __FILE__ . " | Line: " . __LINE__);
        } catch (Exception $e) {
            error_log("Error fetching task_history: " . $e->getMessage() . " | File: " . __FILE__ . " | Line: " . __LINE__);
            throw new Exception("Failed to fetch task_history: " . $e->getMessage());
        }

        // Fetch task_acceptances
        try {
            error_log("Fetching task_acceptances for task_id: $task_id | File: " . __FILE__ . " | Line: " . __LINE__);
            $stmt = $pdo->prepare("
                SELECT ta.*, u.username AS user, t.code AS task_code
                FROM task_acceptances ta
                JOIN users u ON ta.user_id = u.id
                JOIN tasks t ON ta.task_id = t.id
                WHERE ta.task_id = ?
            ");
            $stmt->execute([$task_id]);
            $task_acceptances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fetched " . count($task_acceptances) . " task_acceptances | File: " . __FILE__ . " | Line: " . __LINE__);
        } catch (Exception $e) {
            error_log("Error fetching task_acceptances: " . $e->getMessage() . " | File: " . __FILE__ . " | Line: " . __LINE__);
            throw new Exception("Failed to fetch task_acceptances: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in super_admin_approval.php: " . $e->getMessage() . " | File: " . __FILE__ . " | Line: " . __LINE__);
    $error = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
    // Temporary debug output (remove in production)
    die($error);
    $tasks = [];
    $task_equity = [];
    $task_history = [];
    $task_acceptances = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>任务管理</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        body, html { margin: 0; padding: 0; overflow-x: hidden; width: 100vw; }
        .container { max-width: 100%; overflow-x: auto; }
        .toggle-section { cursor: pointer; }
        .toggle-section:hover { background-color: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-2">
    <div class="container mx-auto max-w-[90%]">
        <h1 class="text-lg font-bold mb-2 text-center">任务管理</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($error); unset($error); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-green-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <!-- Tasks Section -->
        <div class="mb-4">
            <h2 class="text-sm font-bold mb-1 toggle-section p-2 bg-gray-200 rounded" onclick="toggleSection('tasks')">任务列表</h2>
            <div id="tasks" class="grid gap-2 hidden">
                <?php if (empty($tasks)): ?>
                    <p class="text-xs text-center">暂无任务。</p>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="bg-white p-3 rounded-lg shadow">
                            <p class="text-xs">代码: <?php echo htmlspecialchars($task['code']); ?></p>
                            <p class="text-xs">任务名称: <?php echo htmlspecialchars($task['task_name']); ?></p>
                            <p class="text-xs">创建者: <?php echo htmlspecialchars($task['creator']); ?></p>
                            <p class="text-xs">最新价格: <?php echo number_format($task['latest_price'], 2); ?></p>
                            <p class="text-xs">形式: <?php echo htmlspecialchars($task['form']); ?></p>
                            <p class="text-xs">描述: <?php echo htmlspecialchars($task['description'] ?: '无描述'); ?></p>
                            <form method="POST" class="mt-2 grid grid-cols-2 gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <div>
                                    <label class="text-xs">任务名称:</label>
                                    <input type="text" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>" maxlength="50" required class="p-1 border rounded text-xs w-full">
                                </div>
                                <div>
                                    <label class="text-xs">评级:</label>
                                    <select name="rating" class="p-1 border rounded text-xs w-full">
                                        <option value="F" <?php echo $task['rating'] === 'F' ? 'selected' : ''; ?>>F</option>
                                        <option value="E" <?php echo $task['rating'] === 'E' ? 'selected' : ''; ?>>E</option>
                                        <option value="D" <?php echo $task['rating'] === 'D' ? 'selected' : ''; ?>>D</option>
                                        <option value="C" <?php echo $task['rating'] === 'C' ? 'selected' : ''; ?>>C</option>
                                        <option value="B" <?php echo $task['rating'] === 'B' ? 'selected' : ''; ?>>B</option>
                                        <option value="A" <?php echo $task['rating'] === 'A' ? 'selected' : ''; ?>>A</option>
                                        <option value="S" <?php echo $task['rating'] === 'S' ? 'selected' : ''; ?>>S</option>
                                        <option value="SSS" <?php echo $task['rating'] === 'SSS' ? 'selected' : ''; ?>>SSS</option>
                                        <option value="EX" <?php echo $task['rating'] === 'EX' ? 'selected' : ''; ?>>EX</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs">状态:</label>
                                    <select name="task_state" class="p-1 border rounded text-xs w-full">
                                        <option value="Approval" <?php echo $task['task_state'] === 'Approval' ? 'selected' : ''; ?>>待审批</option>
                                        <option value="Open" <?php echo $task['task_state'] === 'Open' ? 'selected' : ''; ?>>开放</option>
                                        <option value="Perpetual" <?php echo $task['task_state'] === 'Perpetual' ? 'selected' : ''; ?>>持续</option>
                                        <option value="Completed" <?php echo $task['task_state'] === 'Completed' ? 'selected' : ''; ?>>已完成</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs">形式:</label>
                                    <select name="form" class="p-1 border rounded text-xs w-full">
                                        <option value="Sole" <?php echo $task['form'] === 'Sole' ? 'selected' : ''; ?>>独资</option>
                                        <option value="Crowdfunding" <?php echo $task['form'] === 'Crowdfunding' ? 'selected' : ''; ?>>众筹</option>
                                        <option value="Hybrid" <?php echo $task['form'] === 'Hybrid' ? 'selected' : ''; ?>>混合</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs">总股份:</label>
                                    <input type="number" name="total_shares" value="<?php echo $task['total_shares']; ?>" min="0" class="p-1 border rounded text-xs w-full">
                                </div>
                                <div>
                                    <label class="text-xs">自持股份:</label>
                                    <input type="number" name="own_shares" value="<?php echo $task['own_shares']; ?>" min="0" class="p-1 border rounded text-xs w-full">
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs">描述:</label>
                                    <textarea name="description" maxlength="255" class="p-1 border rounded text-xs w-full h-20"><?php echo htmlspecialchars($task['description'] ?: ''); ?></textarea>
                                </div>
                                <div class="col-span-2">
                                    <button type="submit" class="bg-green-500 text-white px-4 py-1 rounded hover:bg-green-600 text-xs">更新</button>
                                </div>
                            </form>
                            <form method="POST" class="mt-2 inline-block">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs" onclick="return confirm('确定要删除此任务及其所有相关数据吗？')">删除</button>
                            </form>
                            <a href="super_admin_approval.php?task_id=<?php echo $task['id']; ?>" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs ml-2">查看详情</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($task_equity) || !empty($task_history) || !empty($task_acceptances)): ?>
            <!-- Task Details Section -->
            <div class="mb-4">
                <h2 class="text-sm font-bold mb-1 toggle-section p-2 bg-gray-200 rounded" onclick="toggleSection('task-details')">任务详情 (任务ID: <?php echo htmlspecialchars($_GET['task_id']); ?>)</h2>
                <div id="task-details" class="grid gap-2 hidden">
                    <!-- Task Equity -->
                    <div class="mb-2">
                        <h3 class="text-xs font-bold">任务股权</h3>
                        <?php if (empty($task_equity)): ?>
                            <p class="text-xs text-center">暂无股权持有者。</p>
                        <?php else: ?>
                            <?php foreach ($task_equity as $equity): ?>
                                <div class="bg-white p-2 rounded-lg shadow mt-1">
                                    <p class="text-xs">用户: <?php echo htmlspecialchars($equity['user']); ?></p>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="equity_id" value="<?php echo $equity['id']; ?>">
                                        <input type="hidden" name="action" value="update_equity">
                                        <label class="text-xs">持有股份:</label>
                                        <input type="number" name="shares_owned" value="<?php echo $equity['shares_owned']; ?>" min="0" class="p-1 border rounded text-xs w-20">
                                        <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs ml-2">更新</button>
                                    </form>
                                    <p class="text-xs">创建时间: <?php echo $equity['created_at']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Task History -->
                    <div class="mb-2">
                        <h3 class="text-xs font-bold">任务历史</h3>
                        <?php if (empty($task_history)): ?>
                            <p class="text-xs text-center">暂无交易历史。</p>
                        <?php else: ?>
                            <?php foreach ($task_history as $history): ?>
                                <div class="bg-white p-2 rounded-lg shadow mt-1">
                                    <p class="text-xs">用户: <?php echo htmlspecialchars($history['user']); ?></p>
                                    <p class="text-xs">花费积分: <?php echo number_format($history['points_spent'], 2); ?></p>
                                    <p class="text-xs">创建时间: <?php echo $history['created_at']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Task Acceptances -->
                    <div class="mb-2">
                        <h3 class="text-xs font-bold">任务接受记录</h3>
                        <?php if (empty($task_acceptances)): ?>
                            <p class="text-xs text-center">暂无接受记录。</p>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($_GET['task_id']); ?>">
                                <input type="hidden" name="acceptance_action" value="mark_completed">
                                <?php if ($task['task_state'] === 'Perpetual'): ?>
                                    <label class="text-xs">完成周期:</label>
                                    <input type="number" name="completion_period" value="1" min="1" class="p-1 border rounded text-xs w-20 mb-2">
                                <?php endif; ?>
                                <table class="w-full text-xs border">
                                    <thead>
                                        <tr class="bg-gray-200">
                                            <th class="p-1">选择</th>
                                            <th class="p-1">用户</th>
                                            <th class="p-1">创建者意图</th>
                                            <th class="p-1">管理员状态</th>
                                            <th class="p-1">完成状态</th>
                                            <th class="p-1">周期</th>
                                            <th class="p-1">接受时间</th>
                                            <th class="p-1">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($task_acceptances as $acceptance): ?>
                                            <tr class="border-t">
                                                <td class="p-1 text-center">
                                                    <input type="checkbox" name="acceptance_ids[]" value="<?php echo $acceptance['id']; ?>" <?php echo $acceptance['completion_status'] === 'Completed' ? 'disabled' : ''; ?>>
                                                </td>
                                                <td class="p-1"><?php echo htmlspecialchars($acceptance['user']); ?></td>
                                                <td class="p-1"><?php echo htmlspecialchars($acceptance['creator_intent']); ?></td>
                                                <td class="p-1"><?php echo htmlspecialchars($acceptance['admin_status']); ?></td>
                                                <td class="p-1"><?php echo htmlspecialchars($acceptance['completion_status']); ?></td>
                                                <td class="p-1"><?php echo $acceptance['completion_period']; ?></td>
                                                <td class="p-1"><?php echo $acceptance['accepted_at']; ?></td>
                                                <td class="p-1">
                                                    <form method="POST" class="inline-block">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($_GET['task_id']); ?>">
                                                        <input type="hidden" name="acceptance_id" value="<?php echo $acceptance['id']; ?>">
                                                        <input type="hidden" name="acceptance_action" value="approve_selection">
                                                        <select name="admin_status" class="p-1 border rounded text-xs">
                                                            <option value="Pending" <?php echo $acceptance['admin_status'] === 'Pending' ? 'selected' : ''; ?>>待处理</option>
                                                            <option value="Approved" <?php echo $acceptance['admin_status'] === 'Approved' ? 'selected' : ''; ?>>已通过</option>
                                                            <option value="Rejected" <?php echo $acceptance['admin_status'] === 'Rejected' ? 'selected' : ''; ?>>已拒绝</option>
                                                        </select>
                                                        <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs ml-1">更新</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs mt-2">标记选中为已完成</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-2 text-center">
            <a href="lobby.php" class="bg-gray-500 text-white rounded hover:bg-gray-600 text-xs inline-block p-2">返回大厅</a>
            <a href="super_admin_approval.php" class="bg-gray-500 text-white rounded hover:bg-gray-600 text-xs inline-block p-2">返回任务管理</a>
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