<?php
session_start();

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log'); // Ensure this path is writable

// Debug mode (set to false in production)
$debug_mode = true;
if ($debug_mode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

try {
    // Load database connection
    if (!file_exists('db.php')) {
        throw new Exception("db.php 文件未找到。");
    }
    require 'db.php';
    if (!isset($pdo)) {
        throw new Exception("PDO 连接未定义。");
    }

    // Check user authentication and role
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['game_admin', 'super_admin'])) {
        error_log("未授权访问: 用户未登录或角色无效。");
        header('Location: login.php');
        exit;
    }

    // Validate game_id
    $game_id = $_GET['game_id'] ?? null;
    if (!$game_id || !is_numeric($game_id)) {
        error_log("无效的游戏ID: game_id=$game_id");
        header('Location: lobby.php?error=无效的游戏ID。');
        exit;
    }

    // Verify game exists and user is authorized
    $stmt = $pdo->prepare("SELECT id, name, creator_id FROM games WHERE id = :game_id");
    if (!$stmt->execute(['game_id' => $game_id])) {
        throw new Exception("游戏查询失败: " . implode(', ', $stmt->errorInfo()));
    }
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game || ($game['creator_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'super_admin')) {
        error_log("无权访问游戏: game_id=$game_id, user_id={$_SESSION['user_id']}, role={$_SESSION['role']}");
        header('Location: lobby.php?error=您无权管理此游戏。');
        exit;
    }

    // Handle pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 20;
    $offset = ($page - 1) * $items_per_page;

    // Handle filters
    $filters = [
        'type' => $_GET['type'] ?? '',
        'round' => $_GET['round'] ?? '',
        'player_tag' => trim($_GET['player_tag'] ?? '')
    ];

    // Initialize records and totals
    $records = [];
    $total_records = 0;

    // Fetch history records
    try {
        $history_conditions = ["game_id = :game_id"];
        $history_params = [':game_id' => $game_id];

        if ($filters['type'] && in_array($filters['type'], ['order', 'response', 'announcement'])) {
            $history_conditions[] = "type = :type";
            $history_params[':type'] = $filters['type'];
        }
        if ($filters['round']) {
            $history_conditions[] = "round_number = :round";
            $history_params[':round'] = $filters['round'];
        }
        if ($filters['player_tag']) {
            $history_conditions[] = "player_tag LIKE :player_tag";
            $history_params[':player_tag'] = "%{$filters['player_tag']}%";
        }

        $history_where = count($history_conditions) > 1 ? 'WHERE ' . implode(' AND ', $history_conditions) : 'WHERE game_id = :game_id';

        // Count history records
        $total_history_query = "SELECT COUNT(*) FROM history $history_where";
        $stmt = $pdo->prepare($total_history_query);
        if (!$stmt->execute($history_params)) {
            throw new Exception("历史记录计数查询失败: " . implode(', ', $stmt->errorInfo()));
        }
        $total_history = $stmt->fetchColumn();
        $total_records += $total_history;

        // Fetch history records
        $history_query = "
            SELECT id, game_id, type, content, player_tag, round_number AS round, created_at, city_id, NULL AS user_id, NULL AS admin_reply, 'history' AS source
            FROM history
            $history_where
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($history_query);
        $history_params[':limit'] = (int)$items_per_page;
        $history_params[':offset'] = (int)$offset;
        foreach ($history_params as $key => $value) {
            $param_type = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $param_type);
        }
        if (!$stmt->execute()) {
            throw new Exception("历史记录查询失败: " . implode(', ', $stmt->errorInfo()));
        }
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("历史记录处理失败: " . $e->getMessage());
        if ($debug_mode) {
            echo "<p class='text-red-500'>历史记录错误: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        $records = []; // Continue with empty history records
    }

    // Fetch orders records
    $include_orders = true; // Set to false to disable orders table queries for debugging
    if ($include_orders) {
        try {
            $orders_conditions = ["o.game_id = :game_id"];
            $orders_params = [':game_id' => $game_id];

            if ($filters['type'] && in_array($filters['type'], ['public', 'secret'])) {
                $orders_conditions[] = "o.type = :type";
                $orders_params[':type'] = $filters['type'];
            }
            if ($filters['round']) {
                $orders_conditions[] = "o.round = :round";
                $orders_params[':round'] = $filters['round'];
            }
            // Skip player_tag filter for orders since u.player_tag doesn't exist

            $orders_where = count($orders_conditions) > 1 ? 'WHERE ' . implode(' AND ', $orders_conditions) : 'WHERE o.game_id = :game_id';

            // Count orders records
            $total_orders_query = "SELECT COUNT(*) FROM orders o $orders_where";
            $stmt = $pdo->prepare($total_orders_query);
            if (!$stmt->execute($orders_params)) {
                throw new Exception("指令记录计数查询失败: " . implode(', ', $stmt->errorInfo()));
            }
            $total_orders = $stmt->fetchColumn();
            $total_records += $total_orders;

            // Fetch orders records
            $orders_query = "
                SELECT o.id, o.game_id, o.type, o.content, NULL AS player_tag, o.round, o.submitted_at AS created_at, o.city_id, o.user_id, o.admin_reply, 'orders' AS source
                FROM orders o
                $orders_where
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $pdo->prepare($orders_query);
            $orders_params[':limit'] = (int)$items_per_page;
            $orders_params[':offset'] = (int)$offset;
            foreach ($orders_params as $key => $value) {
                $param_type = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $param_type);
            }
            if (!$stmt->execute()) {
                throw new Exception("指令记录查询失败: " . implode(', ', $stmt->errorInfo()));
            }
            $orders_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $records = array_merge($records, $orders_records);
        } catch (Exception $e) {
            error_log("指令记录处理失败: " . $e->getMessage());
            if ($debug_mode) {
                echo "<p class='text-red-500'>指令记录错误: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            // Continue with only history records
        }
    }

    // Sort and slice records
    if (!empty($records)) {
        usort($records, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $records = array_slice($records, 0, $items_per_page);
    }

    $total_pages = ceil($total_records / $items_per_page);

    // Handle update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_record'])) {
        try {
            $record_id = $_POST['record_id'] ?? null;
            $source = $_POST['source'] ?? null;
            $content = trim($_POST['content'] ?? '');
            $admin_reply = isset($_POST['admin_reply']) ? trim($_POST['admin_reply']) : null;

            if (!$record_id || !is_numeric($record_id) || !$source || !in_array($source, ['history', 'orders']) || empty($content)) {
                throw new Exception("无效的更新请求: ID=$record_id, 来源=$source, 内容=" . (empty($content) ? '空' : '非空'));
            }

            if ($source === 'history') {
                $stmt = $pdo->prepare("UPDATE history SET content = :content WHERE id = :id AND game_id = :game_id");
                if (!$stmt->execute([':content' => $content, ':id' => $record_id, ':game_id' => $game_id])) {
                    throw new Exception("历史记录更新失败: " . implode(', ', $stmt->errorInfo()));
                }
            } else {
                $stmt = $pdo->prepare("UPDATE orders SET content = :content, admin_reply = :admin_reply WHERE id = :id AND game_id = :game_id");
                if (!$stmt->execute([':content' => $content, ':admin_reply' => $admin_reply, ':id' => $record_id, ':game_id' => $game_id])) {
                    throw new Exception("指令记录更新失败: " . implode(', ', $stmt->errorInfo()));
                }
            }
            header('Location: history_manager.php?game_id=' . $game_id . '&success=记录更新成功。');
            exit;
        } catch (Exception $e) {
            error_log("记录更新失败: " . $e->getMessage());
            header('Location: history_manager.php?game_id=' . $game_id . '&error=记录更新失败: ' . htmlspecialchars($e->getMessage()));
            exit;
        }
    }

    // Handle deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
        try {
            $record_id = $_POST['record_id'] ?? null;
            $source = $_POST['source'] ?? null;

            if (!$record_id || !is_numeric($record_id) || !$source || !in_array($source, ['history', 'orders'])) {
                throw new Exception("无效的删除请求: ID=$record_id, 来源=$source");
            }

            $table = $source === 'history' ? 'history' : 'orders';
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = :id AND game_id = :game_id");
            if (!$stmt->execute([':id' => $record_id, ':game_id' => $game_id])) {
                throw new Exception("记录删除失败: " . implode(', ', $stmt->errorInfo()));
            }
            header('Location: history_manager.php?game_id=' . $game_id . '&success=记录删除成功。');
            exit;
        } catch (Exception $e) {
            error_log("记录删除失败: " . $e->getMessage());
            header('Location: history_manager.php?game_id=' . $game_id . '&error=记录删除失败: ' . htmlspecialchars($e->getMessage()));
            exit;
        }
    }

} catch (Exception $e) {
    error_log("页面加载失败: " . $e->getMessage());
    if ($debug_mode) {
        die("错误: " . htmlspecialchars($e->getMessage()));
    }
    header('Location: lobby.php?error=服务器繁忙，请稍后再试。');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>历史指令管理 - <?php echo htmlspecialchars($game['name']); ?></title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="/assets/js/vue.min.js"></script>
    <style>
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            overflow-y: auto;
            padding: 1rem;
        }
        .modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .table-container {
            max-height: 60vh;
            overflow-y: auto;
        }
        .truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app" class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">历史指令管理: <?php echo htmlspecialchars($game['name']); ?></h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="flex gap-2 mb-4">
            <a href="admin.php?game_id=<?php echo $game_id; ?>" class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">返回管理员面板</a>
            <a href="index.php?game_id=<?php echo $game_id; ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">返回游戏</a>
            <a href="dynamic_edit.php?game_id=<?php echo $game_id; ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">动态编辑</a>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-4">
            <h2 class="text-lg font-semibold mb-2">筛选</h2>
            <form @submit.prevent="applyFilters" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium">类型</label>
                    <select v-model="filters.type" name="type" class="p-2 border rounded">
                        <option value="">全部</option>
                        <option value="order">指令</option>
                        <option value="response">回复</option>
                        <option value="announcement">公告</option>
                        <option value="public">公开指令</option>
                        <option value="secret">秘密指令</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">回合</label>
                    <input type="number" v-model.number="filters.round" name="round" class="p-2 border rounded" placeholder="回合号">
                </div>
                <div>
                    <label class="block text-sm font-medium">玩家标签</label>
                    <input type="text" v-model="filters.player_tag" name="player_tag" class="p-2 border rounded" placeholder="玩家标签">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">应用筛选</button>
                    <button type="button" @click="resetFilters" class="ml-2 bg-gray-500 text-white p-2 rounded hover:bg-gray-600">重置</button>
                </div>
            </form>
        </div>

        <!-- Records Table -->
        <div class="bg-white p-4 rounded-lg shadow mb-4 table-container">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 text-left">ID</th>
                        <th class="p-2 text-left">类型</th>
                        <th class="p-2 text-left">内容</th>
                        <th class="p-2 text-left">玩家标签</th>
                        <th class="p-2 text-left">回合</th>
                        <th class="p-2 text-left">城市ID</th>
                        <th class="p-2 text-left">创建时间</th>
                        <th class="p-2 text-left">管理员回复</th>
                        <th class="p-2 text-left">来源</th>
                        <th class="p-2 text-left">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="record in records" :key="record.id" class="border-t">
                        <td class="p-2">{{ record.id }}</td>
                        <td class="p-2">{{ record.type }}</td>
                        <td class="p-2 truncate" :title="record.content">{{ record.content }}</td>
                        <td class="p-2">{{ record.player_tag || '-' }}</td>
                        <td class="p-2">{{ record.round }}</td>
                        <td class="p-2">{{ record.city_id || '-' }}</td>
                        <td class="p-2">{{ record.created_at }}</td>
                        <td class="p-2 truncate" :title="record.admin_reply">{{ record.admin_reply || '-' }}</td>
                        <td class="p-2">{{ record.source }}</td>
                        <td class="p-2 flex gap-2">
                            <button @click="editRecord(record)" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">编辑</button>
                            <button @click="confirmDelete(record)" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">删除</button>
                        </td>
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

        <!-- Edit Modal -->
        <div v-if="selectedRecord" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">编辑记录 ({{ selectedRecord.source === 'history' ? '历史' : '指令' }})</h2>
                <form @submit.prevent="submitUpdate" class="space-y-2">
                    <input type="hidden" name="edit_record" value="1">
                    <input type="hidden" name="record_id" :value="selectedRecord.id">
                    <input type="hidden" name="source" :value="selectedRecord.source">
                    <label class="block text-sm font-medium">内容</label>
                    <textarea v-model="selectedRecord.content" name="content" required class="w-full p-2 border rounded h-24"></textarea>
                    <label v-if="selectedRecord.source === 'orders'" class="block text-sm font-medium">管理员回复</label>
                    <textarea v-if="selectedRecord.source === 'orders'" v-model="selectedRecord.admin_reply" name="admin_reply" class="w-full p-2 border rounded h-24"></textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">更新</button>
                        <button type="button" @click="selectedRecord = null" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600">取消</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">确认删除</h2>
                <p>确定要删除此记录 (ID: {{ deleteRecord.id }}) 吗？此操作不可撤销。</p>
                <form @submit.prevent="submitDelete" class="mt-4 flex gap-2">
                    <input type="hidden" name="delete_record" value="1">
                    <input type="hidden" name="record_id" :value="deleteRecord.id">
                    <input type="hidden" name="source" :value="deleteRecord.source">
                    <button type="submit" class="bg-red-500 text-white p-2 rounded hover:bg-red-600">确认删除</button>
                    <button type="button" @click="showDeleteConfirm = false" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600">取消</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const app = Vue.createApp({
            data() {
                return {
                    records: <?php echo json_encode($records, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                    filters: {
                        type: '<?php echo htmlspecialchars($filters['type']); ?>',
                        round: '<?php echo htmlspecialchars($filters['round']); ?>',
                        player_tag: '<?php echo htmlspecialchars($filters['player_tag']); ?>'
                    },
                    selectedRecord: null,
                    deleteRecord: null,
                    showDeleteConfirm: false,
                    currentPage: <?php echo $page; ?>,
                    totalPages: <?php echo $total_pages; ?>
                };
            },
            methods: {
                editRecord(record) {
                    this.selectedRecord = { ...record };
                },
                confirmDelete(record) {
                    this.deleteRecord = record;
                    this.showDeleteConfirm = true;
                },
                submitUpdate() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    for (const key in this.selectedRecord) {
                        if (['content', 'admin_reply', 'id', 'source'].includes(key)) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = this.selectedRecord[key] ?? '';
                            form.appendChild(input);
                        }
                    }
                    const editInput = document.createElement('input');
                    editInput.type = 'hidden';
                    editInput.name = 'edit_record';
                    editInput.value = '1';
                    form.appendChild(editInput);
                    document.body.appendChild(form);
                    form.submit();
                },
                submitDelete() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_record';
                    deleteInput.value = '1';
                    form.appendChild(deleteInput);
                    const recordIdInput = document.createElement('input');
                    recordIdInput.type = 'hidden';
                    recordIdInput.name = 'record_id';
                    recordIdInput.value = this.deleteRecord.id;
                    form.appendChild(recordIdInput);
                    const sourceInput = document.createElement('input');
                    sourceInput.type = 'hidden';
                    sourceInput.name = 'source';
                    sourceInput.value = this.deleteRecord.source;
                    form.appendChild(sourceInput);
                    document.body.appendChild(form);
                    form.submit();
                },
                applyFilters() {
                    const url = new URL(window.location);
                    url.searchParams.set('type', this.filters.type);
                    url.searchParams.set('round', this.filters.round);
                    url.searchParams.set('player_tag', this.filters.player_tag);
                    url.searchParams.set('page', 1);
                    window.location.href = url.toString();
                },
                resetFilters() {
                    this.filters.type = '';
                    this.filters.round = '';
                    this.filters.player_tag = '';
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