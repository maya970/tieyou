<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_points = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->query("
        SELECT t.*, u.username AS creator,
            CASE t.rating
                WHEN 'EX' THEN 9
                WHEN 'SSS' THEN 8
                WHEN 'S' THEN 7
                WHEN 'A' THEN 6
                WHEN 'B' THEN 5
                WHEN 'C' THEN 4
                WHEN 'D' THEN 3
                WHEN 'E' THEN 2
                WHEN 'F' THEN 1
            END AS rating_score,
            ((t.latest_price - t.opening_price) / t.opening_price * 100) AS change_percentage
        FROM tasks t
        JOIN users u ON t.creator_id = u.id
        WHERE t.task_state IN ('Open', 'Completed', 'Perpetual')
        ORDER BY (t.latest_price / CASE t.rating
            WHEN 'EX' THEN 9
            WHEN 'SSS' THEN 8
            WHEN 'S' THEN 7
            WHEN 'A' THEN 6
            WHEN 'B' THEN 5
            WHEN 'C' THEN 4
            WHEN 'D' THEN 3
            WHEN 'E' THEN 2
            WHEN 'F' THEN 1
        END) ASC
    ");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in lobby.php: " . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
    $tasks = []; // Set empty tasks array to prevent Vue.js errors
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Game Lobby</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.min.js"></script>
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
    <div class="container mx-auto px-2 flex-grow relative">
        <h1 class="text-lg font-bold mb-2 text-center pt-2">Game Lobby</h1>
        <p class="mb-2 text-center text-xs">加QQ群325358084获得积分进行游戏</p>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-green-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-500 mb-2 text-center text-xs"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <div class="mb-2 bg-white p-2 rounded-lg shadow mx-auto max-w-[90%]">
            <p class="text-sm font-semibold text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p class="text-center text-xs">Your Points: <?php echo (int)$user_points; ?></p>
        </div>
        <div class="grid grid-cols-3 gap-2 mb-2 max-w-[90%] mx-auto">
            <a href="strategy_games.php" class="bg-blue-500 text-white p-2 rounded-lg text-center hover:bg-blue-600 text-xs">Strategy Games</a>
            <a href="autorpg_games.php" class="bg-green-500 text-white p-2 rounded-lg text-center hover:bg-green-600 text-xs">AutoRPG Games</a>
            <a href="novels.php" class="bg-purple-500 text-white p-2 rounded-lg text-center hover:bg-purple-600 text-xs">Novels</a>
        </div>
        <div class="grid grid-cols-2 gap-2 mb-2 max-w-[90%] mx-auto">
            <a href="delegate.php" class="bg-blue-500 text-white p-2 rounded-lg text-center hover:bg-blue-600 text-xs">Delegate Task</a>
            <a href="filter.php" class="bg-blue-500 text-white p-2 rounded-lg text-center hover:bg-blue-600 text-xs">Filter Tasks</a>
        </div>
  <!-- HTML modifications (within the #app div) -->
<div id="app" class="table-container">
    <div class="overflow-x-hidden">
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
                <tr v-for="task in paginatedTasks" :key="task.id" class="text-center" @click="goToTask(task.id)">
                    <td class="py-1 px-1">{{ task.code }}</td>
                    <td class="py-1 px-1">{{ task.rating }}</td>
                    <td class="py-1 px-1">{{ task.task_name }}</td>
                    <td class="py-1 px-1">{{ task.latest_price.toFixed(2) }}</td>
                    <td :class="task.change_percentage < 0 ? 'text-red-500' : 'text-green-500'" class="py-1 px-1">{{ task.change_percentage.toFixed(2) }}%</td>
                    <td class="py-1 px-1">{{ task.form }}</td>
                    <td class="py-1 px-1">{{ task.description }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="text-center my-2 flex justify-center items-center space-x-4">
        <button @click="refreshTasks" class="bg-blue-600 text-white px-4 py-1 rounded-lg hover:bg-blue-700 text-xs">Refresh Now</button>
        
        <span class="text-xs text-gray-600">Next auto-refresh in: {{ countdown }}s</span>
    </div>
</div>
  <!-- old HTML modifications (within the #app div) -->

            <div class="text-center my-2">
                <button @click="nextPage" class="bg-blue-600 text-white px-4 py-1 rounded-lg hover:bg-blue-700 text-xs">Next Page</button>
            </div>
        </div>
        <div class="fixed bottom-2 left-2 flex space-x-2 bg-gray-900 bg-opacity-75 p-2 rounded-lg z-10">
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <a href="super_admin_approval.php" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs">Super Admin</a>
            <?php endif; ?>
            <a href="my_tasks.php" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">My Tasks</a>
            <a href="purchase.php" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">Purchase</a>
            <a href="accept_task.php" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 text-xs">Accept Task</a>
            <a href="logout.php" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs">Logout</a>
        </div>
    </div>
    <script>
    const { createApp } = Vue;
    createApp({
        data() {
            return {
                tasks: <?php echo json_encode($tasks); ?>,
                currentPage: 0,
                rowsPerPage: 4,
                isHovering: false,
                countdown: 5,
            };
        },
        computed: {
            paginatedTasks() {
                const start = this.currentPage * this.rowsPerPage;
                return this.tasks.slice(start, start + this.rowsPerPage);
            },
        },
        methods: {
            async refreshTasks() {
                try {
                    const response = await fetch('/api/tasks.php');
                    if (!response.ok) throw new Error('Network response was not ok');
                    this.tasks = await response.json();
                    this.currentPage = 0; // Reset to first page on refresh
                    this.countdown = 5; // Reset countdown
                } catch (error) {
                    console.error('Error refreshing tasks:', error);
                }
            },
            nextPage() {
                this.currentPage = (this.currentPage + 1) % Math.ceil(this.tasks.length / this.rowsPerPage);
            },
            startAutoRefresh() {
                this.autoRefreshInterval = setInterval(() => {
                    if (!this.isHovering) {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            this.refreshTasks();
                            this.countdown = 5;
                        }
                    }
                }, 1000); // Update every second
            },
            goToTask(taskId) {
                window.location.href = `task_detail.php?task_id=${taskId}`;
            },
        },
        mounted() {
            this.startAutoRefresh();
            const tableContainer = this.$el.querySelector('.table-container');
            tableContainer.addEventListener('mouseenter', () => {
                this.isHovering = true;
                clearInterval(this.autoRefreshInterval);
            });
            tableContainer.addEventListener('mouseleave', () => {
                this.isHovering = false;
                this.countdown = 5; // Reset countdown on mouse leave
                this.startAutoRefresh();
            });
        },
    }).mount('#app');
    </script>
</body>
</html>