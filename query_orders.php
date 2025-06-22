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

// Validate game_id
$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    header('Location: index.php?error=无效的游戏ID。');
    exit;
}

// Verify database connection
if (!isset($pdo)) {
    error_log("数据库连接失败: PDO 未定义");
    die("错误: 无法连接到数据库。");
}

// Verify game exists
try {
    $stmt = $pdo->prepare("SELECT id, name FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game) {
        header('Location: index.php?error=游戏不存在。');
        exit;
    }
} catch (PDOException $e) {
    error_log("查询游戏失败: " . $e->getMessage());
    die("错误: 无法查询游戏信息。");
}

// Check user authorization
try {
    $stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $_SESSION['user_id']]);
    $user_in_game = $stmt->fetch();
    if (!$user_in_game && $_SESSION['role'] !== 'super_admin') {
        header('Location: index.php?error=您无权访问此游戏。');
        exit;
    }
} catch (PDOException $e) {
    error_log("查询用户权限失败: " . $e->getMessage());
    die("错误: 无法验证用户权限。");
}

// Get current round
try {
    $stmt = $pdo->prepare("SELECT round_number FROM rounds WHERE game_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$game_id]);
    $current_round = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {
    error_log("查询当前回合失败: " . $e->getMessage());
    $current_round = 0; // Fallback to 0 if query fails
}

// Handle pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Handle filters
$filters = [
    'round' => $_GET['round'] ?? '',
    'type' => $_GET['type'] ?? '',
    'player' => $_GET['player'] ?? 'all'
];

// Fetch orders
$conditions = ["o.game_id = :game_id"];
$params = [':game_id' => $game_id];

// Restrict current round orders for non-admins
if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'game_admin') {
    $conditions[] = "(o.round < :current_round OR (o.round = :current_round AND o.user_id = :user_id))";
    $params[':current_round'] = $current_round;
    $params[':user_id'] = $_SESSION['user_id'];
}

// Apply filters
if ($filters['round'] && is_numeric($filters['round'])) {
    $conditions[] = "o.round = :round";
    $params[':round'] = $filters['round'];
}
if ($filters['type'] && in_array($filters['type'], ['public', 'secret'])) {
    $conditions[] = "o.type = :type";
    $params[':type'] = $filters['type'];
}
if ($filters['player'] === 'self') {
    $conditions[] = "o.user_id = :user_id";
    $params[':user_id'] = $_SESSION['user_id'];
}

$where_clause = count($conditions) > 1 ? 'WHERE ' . implode(' AND ', $conditions) : 'WHERE o.game_id = :game_id';

// Count total orders
try {
    $total_query = "
        SELECT COUNT(*)
        FROM orders o
        LEFT JOIN cities c ON o.city_id = c.id
        LEFT JOIN city_players cp ON o.city_id = cp.city_id AND o.game_id = cp.game_id
        $where_clause
    ";
    $stmt = $pdo->prepare($total_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $total_orders = $stmt->fetchColumn();
    $total_pages = ceil($total_orders / $items_per_page);
} catch (PDOException $e) {
    error_log("计数指令失败: " . $e->getMessage());
    die("错误: 无法获取指令总数。");
}

// Fetch orders
try {
    $query = "
        SELECT o.id, o.game_id, o.type, o.content, o.round, o.submitted_at, o.admin_reply, c.name AS city_name, cp.player_tag
        FROM orders o
        LEFT JOIN cities c ON o.city_id = c.id
        LEFT JOIN city_players cp ON o.city_id = cp.city_id AND o.game_id = cp.game_id
        $where_clause
        ORDER BY o.submitted_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($query);
    $params[':limit'] = $items_per_page;
    $params[':offset'] = $offset;
    foreach ($params as $key => $value) {
        $param_type = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $param_type);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("查询指令失败: " . $e->getMessage());
    die("错误: 无法获取指令列表。");
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>指令查询 - <?php echo htmlspecialchars($game['name'] ?? '游戏名称未知'); ?></title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="/assets/js/vue.min.js"></script>
    <style>
        .table-container {
            max-height: 60vh;
            overflow-y: auto;
        }
        .content-cell {
            max-width: 300px;
            overflow-wrap: break-word;
            white-space: normal;
        }
        table {
            table-layout: auto;
        }
        th, td {
            min-width: 100px;
        }
        .content-cell, .admin-reply-cell {
            min-width: 200px;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app" class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">指令查询: <?php echo htmlspecialchars($game['name'] ?? '游戏名称未知'); ?></h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="flex gap-2 mb-4">
            <a href="index.php?game_id=<?php echo $game_id; ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">返回游戏</a>
            <a href="lobby.php" class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">返回大厅</a>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-4">
            <h2 class="text-lg font-semibold mb-2">筛选</h2>
            <form @submit.prevent="applyFilters" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium">回合</label>
                    <input type="number" v-model.number="filters.round" name="round" class="p-2 border rounded" placeholder="回合号">
                </div>
                <div>
                    <label class="block text-sm font-medium">类型</label>
                    <select v-model="filters.type" name="type" class="p-2 border rounded">
                        <option value="">全部</option>
                        <option value="public">公开</option>
                        <option value="secret">秘密</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">玩家</label>
                    <select v-model="filters.player" name="player" class="p-2 border rounded">
                        <option value="all">所有人</option>
                        <option value="self">仅自己</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">应用筛选</button>
                    <button type="button" @click="resetFilters" class="ml-2 bg-gray-500 text-white p-2 rounded hover:bg-gray-600">重置</button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white p-4 rounded-lg shadow mb-4 table-container">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 text-left">ID</th>
                        <th class="p-2 text-left">城市</th>
                        <th class="p-2 text-left">回合</th>
                        <th class="p-2 text-left">类型</th>
                        <th class="p-2 text-left">内容</th>
                        <th class="p-2 text-left">管理员回复</th>
                        <th class="p-2 text-left">提交时间</th>
                        <th class="p-2 text-left">玩家</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders" :key="order.id" class="border-t">
                        <td class="p-2">{{ order.id }}</td>
                        <td class="p-2">{{ order.city_name || '-' }}</td>
                        <td class="p-2">{{ order.round }}</td>
                        <td class="p-2">{{ order.type === 'public' ? '公开' : '秘密' }}</td>
                        <td class="p-2 content-cell">{{ order.content }}</td>
                        <td class="p-2 content-cell admin-reply-cell">{{ order.admin_reply || '-' }}</td>
                        <td class="p-2">{{ order.submitted_at }}</td>
                        <td class="p-2">{{ order.player_tag || '未知玩家' }}</td>
                    </tr>
                    <tr v-if="!orders.length">
                        <td colspan="8" class="p-2 text-center text-gray-500">无指令记录。</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center gap-2 mb-4">
            <button v-if="currentPage > 1" @click="changePage(currentPage - 1)" class="bg-gray-300 px-3 py-1 rounded hover:bg-gray-400">上一页</button>
            <span>第 {{ currentPage }} 页 / 共 {{ totalPages }} 页</span>
            <button v-if="currentPage < totalPages" @click="changePage(currentPage + 1)" class="bg-gray-300 px-3 py-1 rounded hover:bg-gray-400">下一页</button>
        </div>
    </div>

    <script>
        const app = Vue.createApp({
            data() {
                return {
                    orders: <?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                    filters: {
                        round: '<?php echo htmlspecialchars($filters['round']); ?>',
                        type: '<?php echo htmlspecialchars($filters['type']); ?>',
                        player: '<?php echo htmlspecialchars($filters['player']); ?>'
                    },
                    currentPage: <?php echo $page; ?>,
                    totalPages: <?php echo $total_pages; ?>
                };
            },
            methods: {
                applyFilters() {
                    const url = new URL(window.location);
                    url.searchParams.set('round', this.filters.round);
                    url.searchParams.set('type', this.filters.type);
                    url.searchParams.set('player', this.filters.player);
                    url.searchParams.set('page', 1);
                    window.location.href = url.toString();
                },
                resetFilters() {
                    this.filters.round = '';
                    this.filters.type = '';
                    this.filters.player = 'all';
                    this.applyFilters();
                },
                changePage(page) {
                    const url = new URL(window.location);
                    url.searchParams.set('page', page);
                    window.location.href = url.toString();
                }
            }
        });
        app.mount('#app');
    </script>
</body>
</html>
