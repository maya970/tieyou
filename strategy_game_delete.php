<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', '/home/ftp/m/maya970/wwwroot/logs/php_errors.log');

// Start session
session_start();

// Check dependencies
$required_files = ['check_auth.php', 'db.php'];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("错误: 缺少依赖文件 {$file}");
    }
}
require_once 'check_auth.php';
require_once 'db.php';

// Validate session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php?error=会话过期，请重新登录。');
    exit;
}

// Check super_admin role
if ($_SESSION['role'] !== 'super_admin') {
    header('Location: lobby.php?error=只有超级管理员可以删除游戏。');
    exit;
}

// Verify database connection
if (!isset($pdo)) {
    error_log("数据库连接失败: PDO 未定义");
    die("错误: 无法连接到数据库。");
}

// Fetch all games
try {
    $stmt = $pdo->query("
        SELECT g.id, g.name, u.username AS creator, COUNT(gp.user_id) AS player_count
        FROM games g
        JOIN users u ON g.creator_id = u.id
        LEFT JOIN game_players gp ON g.id = gp.game_id
        GROUP BY g.id
        ORDER BY g.name
    ");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("查询游戏列表失败: " . $e->getMessage());
    die("错误: 无法获取游戏列表。");
}

// Handle game deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = $_POST['game_id'];
    
    // Validate game_id
    if (!is_numeric($game_id)) {
        header('Location: strategy_game_delete.php?error=无效的游戏ID。');
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Delete related records
        $tables = [
            'history' => 'DELETE FROM history WHERE game_id = ?', // Added history table
            'game_field_names' => 'DELETE FROM game_field_names WHERE game_id = ?',
            'orders' => 'DELETE FROM orders WHERE game_id = ?',
            'city_players' => 'DELETE FROM city_players WHERE game_id = ?',
            'cities' => 'DELETE FROM cities WHERE game_id = ?',
            'rounds' => 'DELETE FROM rounds WHERE game_id = ?',
            'game_players' => 'DELETE FROM game_players WHERE game_id = ?',
            'games' => 'DELETE FROM games WHERE id = ?'
        ];

        foreach ($tables as $table => $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$game_id]);
        }

        // Commit transaction
        $pdo->commit();
        error_log("超级管理员 {$_SESSION['user_id']} 删除游戏 ID: {$game_id}");
        header('Location: lobby.php?success=游戏已成功删除。');
        exit;
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        error_log("删除游戏 ID {$game_id} 失败: " . $e->getMessage());
        header('Location: strategy_game_delete.php?error=删除游戏失败：' . htmlspecialchars($e->getMessage()));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>策略游戏删除界面</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="/assets/js/vue.min.js"></script>
    <style>
        .game-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        .game-card {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .confirm-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app" class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">策略游戏删除界面</h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="flex gap-2 mb-4">
            <a href="lobby.php" class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">返回大厅</a>
            <a href="super_admin.php" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">超级管理员面板</a>
        </div>

        <!-- Game List -->
        <div v-if="games.length" class="game-list">
            <div v-for="game in games" :key="game.id" class="game-card">
                <h2 class="text-lg font-semibold">{{ game.name }}</h2>
                <p>创建者: {{ game.creator }}</p>
                <p>玩家数量: {{ game.player_count }}</p>
                <button 
                    @click="confirmDelete(game.id, game.name)" 
                    class="mt-2 w-full bg-red-500 text-white p-2 rounded hover:bg-red-600"
                >
                    删除游戏
                </button>
            </div>
        </div>
        <p v-else class="text-gray-500">没有可删除的游戏。</p>

        <!-- Confirm Delete Modal -->
        <div v-if="showConfirm" class="confirm-modal" @click.self="cancelDelete">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">确认删除</h2>
                <p>你确定要删除游戏 "{{ confirmGameName }}" 吗？此操作不可撤销，将删除所有相关数据（城市、指令、回合等）。</p>
                <form method="POST" class="mt-4 flex gap-2">
                    <input type="hidden" name="game_id" :value="confirmGameId">
                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">确认删除</button>
                    <button type="button" @click="cancelDelete" class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">取消</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const app = Vue.createApp({
            data() {
                return {
                    games: <?php echo json_encode($games, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                    showConfirm: false,
                    confirmGameId: null,
                    confirmGameName: ''
                };
            },
            methods: {
                confirmDelete(gameId, gameName) {
                    this.confirmGameId = gameId;
                    this.confirmGameName = gameName;
                    this.showConfirm = true;
                },
                cancelDelete() {
                    this.showConfirm = false;
                    this.confirmGameId = null;
                    this.confirmGameName = '';
                }
            }
        });
        app.mount('#app');
    </script>
</body>
</html>