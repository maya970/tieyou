<?php
require_once 'check_auth.php';
require_once 'db.php';

// Validate game_id
$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    header('Location: lobby.php?error=无效的游戏ID。');
    exit;
}

// Verify game exists
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    header('Location: lobby.php?error=游戏不存在。');
    exit;
}

// Check user authorization
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
$stmt->execute([$game_id, $_SESSION['user_id']]);
if (!$stmt->fetch() && $_SESSION['role'] !== 'super_admin') {
    header('Location: lobby.php?error=您无权访问此游戏。');
    exit;
}

// Load cities with player tags
$stmt = $pdo->prepare("
    SELECT c.id, c.game_id, c.name, c.x, c.y, c.color, c.description, c.population, c.resources, 
           c.growth_rate, c.updated_at, c.city_display_type, c.city_display_value, c.economy, 
           c.economy_growth, c.military, c.military_growth, c.culture, c.culture_growth, c.science, 
           c.science_growth, c.infrastructure, c.infrastructure_growth, c.health, c.health_growth, 
           c.education, c.education_growth, c.stability, c.stability_growth, c.value9, c.growth_rate9, 
           c.type, cp.player_tag AS player_username
    FROM cities c
    LEFT JOIN city_players cp ON c.id = cp.city_id AND c.game_id = cp.game_id
    WHERE c.game_id = ?
");
$stmt->execute([$game_id]);
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$cities) {
    $cities = [];
}

// Load distinct player tags
$stmt = $pdo->prepare("SELECT DISTINCT player_tag FROM city_players WHERE game_id = ?");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Load current round
$stmt = $pdo->prepare("SELECT * FROM rounds WHERE game_id = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$game_id]);
$current_round = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current_round) {
    $current_round = ['round_number' => 0, 'end_time' => null];
}

// Load history rounds (with pagination)
$rounds_per_page = 5;
$page = isset($_GET['history_page']) && is_numeric($_GET['history_page']) ? (int)$_GET['history_page'] : 1;
$offset = ($page - 1) * $rounds_per_page;
$stmt = $pdo->prepare("
    SELECT * FROM rounds
    WHERE game_id = :game_id AND is_active = 0
    ORDER BY round_number DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':game_id', (int)$game_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', (int)$rounds_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$history_rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total history rounds for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rounds WHERE game_id = ?");
$stmt->execute([$game_id]);
$total_rounds = $stmt->fetchColumn();
$total_pages = ceil($total_rounds / $rounds_per_page);

// Sanitize history_rounds for JSON
$history_rounds_safe = array_map(function($round) {
    return [
        'id' => (int)$round['id'],
        'round_number' => (int)$round['round_number'],
        'end_time' => $round['end_time'] ?? 'N/A',
        'orders' => [],
        'announcements' => []
    ];
}, $history_rounds);

// Load orders for the current user
$stmt = $pdo->prepare("
    SELECT o.*, c.name AS city_name
    FROM orders o
    LEFT JOIN cities c ON o.city_id = c.id
    WHERE o.game_id = ? AND o.user_id = ?
    ORDER BY o.submitted_at DESC
");
$stmt->execute([$game_id, $_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['name']); ?> - 游戏</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="/assets/js/vue.min.js"></script>
    <style>
        .map-container {
            position: relative;
            width: 100%;
            height: 600px;
            overflow: hidden;
            background: #f0f0f0;
            z-index: 10;
            margin-top: 2rem;
            border-radius: 0.5rem;
        }
        .map-svg {
            position: absolute;
            top: 0;
            left: 0;
            width: 800px;
            height: 600px;
            z-index: 1;
        }
        .map-controls {
            position: relative;
            z-index: 20;
            margin-top: 0.5rem;
        }
        .city-details {
            position: relative;
            z-index: 30;
            transition: all 0.3s ease;
        }
        .city-details.show {
            transform: scale(1.05);
        }
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
            max-width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-close {
            cursor: pointer;
            z-index: 1001;
        }
        .player-filter {
            max-height: 200px;
            overflow-y: auto;
            z-index: 20;
        }
        .pagination {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        .ui-buttons, .game-info {
            position: relative;
            z-index: 20;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app" class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($game['name']); ?></h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <!-- Game Info -->
        <div class="game-info bg-white p-4 rounded-lg shadow">
            <p><strong>规则:</strong> <?php echo htmlspecialchars($game['rules']); ?></p>
            <p><strong>当前回合:</strong> {{ currentRound }}</p>
            <p><strong>回合结束时间:</strong> <?php echo $current_round['end_time'] ?? '未设定'; ?></p>
        </div>

        <!-- UI Buttons -->
        <div class="ui-buttons flex flex-wrap gap-2">
            <a href="lobby.php" class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">返回大厅</a>
            <button @click="showRules = true" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">查看规则</button>
            <button @click="openHistoryModal" class="bg-purple-500 text-white px-3 py-1 rounded hover:bg-purple-600">查看历史</button>
            <?php if ($_SESSION['role'] === 'game_admin' && $game['creator_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'super_admin'): ?>
                <a href="admin.php?game_id=<?php echo $game_id; ?>" class="bg-purple-500 text-white px-3 py-1 rounded hover:bg-purple-600">管理员面板</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <a href="super_admin.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">超级管理员面板</a>
            <?php endif; ?>
        </div>

        <!-- Player Filter -->
        <div class="player-filter">
            <label class="block text-sm font-medium mb-1">筛选玩家</label>
            <select v-model="selectedPlayer" class="w-full p-2 border rounded player-filter">
                <option value="">所有玩家</option>
                <option v-for="player in players" :key="player" :value="player">{{ player }}</option>
            </select>
        </div>

        <!-- Player Stats (if selected) -->
        <div v-if="selectedPlayer" class="bg-white p-4 rounded-lg shadow mb-4">
            <h3 class="text-lg font-semibold">玩家 {{ selectedPlayer }} 的统计</h3>
            <p>人口: {{ playerStats.population }}</p>
            <p>资源: {{ playerStats.resources }}</p>
            <p>经济: {{ playerStats.economy }}</p>
            <p>军事: {{ playerStats.military }}</p>
            <p>文化: {{ playerStats.culture }}</p>
            <p>科技: {{ playerStats.science }}</p>
            <p>基础设施: {{ playerStats.infrastructure }}</p>
            <p>健康: {{ playerStats.health }}</p>
            <p>教育: {{ playerStats.education }}</p>
            <p>稳定性: {{ playerStats.stability }}</p>
            <p>拥有城市数: {{ playerStats.cityCount }}</p>
        </div>

        <!-- Map -->
        <div class="map-container bg-white p-4 rounded-lg shadow mb-4">
            <svg
                ref="svg"
                class="map-svg"
                :style="{ transform: `translate(${translateX}px, ${translateY}px) scale(${zoom})` }"
                width="800"
                height="600"
                v-draggable
            >
                <!-- Background Image -->
                <image
                    v-if="backgroundImage"
                    x="0"
                    y="0"
                    width="800"
                    height="600"
                    :href="backgroundImage"
                    preserveAspectRatio="xMidYMid meet"
                />
                <!-- Cities -->
                <g v-for="city in filteredCities" :key="city.id">
                    <circle
                        v-if="city.city_display_type === 'circle'"
                        :cx="city.x"
                        :cy="city.y"
                        r="10"
                        :fill="city.color || (city.player_username === selectedPlayer && selectedPlayer ? 'red' : 'blue')"
                        class="cursor-pointer hover:fill-opacity-80"
                        @click="showCityDetails(city)"
                        @touchstart="showCityDetails(city)"
                    />
                    <image
                        v-if="city.city_display_type === 'image' && city.city_display_value"
                        :x="city.x - 15"
                        :y="city.y - 15"
                        width="30"
                        height="30"
                        :href="city.city_display_value"
                        class="cursor-pointer"
                        @click="showCityDetails(city)"
                        @touchstart="showCityDetails(city)"
                    />
                    <text
                        v-if="city.city_display_type === 'text' && city.city_display_value"
                        :x="city.x"
                        :y="city.y"
                        :fill="city.color || 'black'"
                        font-size="20"
                        text-anchor="middle"
                        class="cursor-pointer"
                        @click="showCityDetails(city)"
                        @touchstart="showCityDetails(city)"
                    >{{ city.city_display_value }}</text>
                    <text
                        v-if="showCityNames"
                        :x="city.x + 15"
                        :y="city.y"
                        :fill="city.color || 'black'"
                        font-size="12"
                    >{{ city.name }}</text>
                </g>
            </svg>
            <div class="map-controls mt-2 flex gap-2">
                <button @click="zoomIn" class="bg-gray-300 px-2 py-1 rounded">放大</button>
                <button @click="zoomOut" class="bg-gray-300 px-2 py-1 rounded">缩小</button>
                <button @click="resetMap" class="bg-gray-300 px-2 py-1 rounded">重置地图</button>
            </div>
            
        </div>

<!-- City Details -->
<div v-if="selectedCity" class="city-details bg-white p-4 rounded-lg shadow mt-4">
    <h2 class="text-lg font-semibold">{{ selectedCity.name }}</h2>
    <p class="mt-2">坐标: ({{ selectedCity.x }}, {{ selectedCity.y }})</p>
    <p>颜色: <span :style="{ color: selectedCity.color || '#0000FF' }">{{ selectedCity.color || '#0000FF' }}</span></p>
    <p>{{ selectedCity.description }}</p>
    <p v-if="selectedCity.player_username">控制者: {{ selectedCity.player_username }}</p>
    <p v-if="selectedCity.population && selectedCity.population !== 'N/A'">人口: {{ selectedCity.population }} (增长: {{ selectedCity.growth_rate || 0 }}%)</p>
    <p v-if="selectedCity.resources && selectedCity.resources !== 'N/A'">资源: {{ selectedCity.resources }}</p>
    <p v-if="selectedCity.economy && selectedCity.economy !== 'N/A'">经济: {{ selectedCity.economy }} (增长: {{ selectedCity.economy_growth || 0 }}%)</p>
    <p v-if="selectedCity.military && selectedCity.military !== 'N/A'">军事: {{ selectedCity.military }} (增长: {{ selectedCity.military_growth || 0 }}%)</p>
    <p v-if="selectedCity.culture && selectedCity.culture !== 'N/A'">文化: {{ selectedCity.culture }} (增长: {{ selectedCity.culture_growth || 0 }}%)</p>
    <p v-if="selectedCity.science && selectedCity.science !== 'N/A'">科技: {{ selectedCity.science }} (增长: {{ selectedCity.science_growth || 0 }}%)</p>
    <p v-if="selectedCity.infrastructure && selectedCity.infrastructure !== 'N/A'">基础设施: {{ selectedCity.infrastructure }} (增长: {{ selectedCity.infrastructure_growth || 0 }}%)</p>
    <p v-if="selectedCity.health && selectedCity.health !== 'N/A'">健康: {{ selectedCity.health }} (增长: {{ selectedCity.health_growth || 0 }}%)</p>
    <p v-if="selectedCity.education && selectedCity.education !== 'N/A'">教育: {{ selectedCity.education }} (增长: {{ selectedCity.education_growth || 0 }}%)</p>
    <p v-if="selectedCity.stability && selectedCity.stability !== 'N/A'">稳定性: {{ selectedCity.stability }} (增长: {{ selectedCity.stability_growth || 0 }}%)</p>
    <p>显示类型: {{ selectedCity.city_display_type }}</p>
    <p v-if="selectedCity.city_display_value">显示值: {{ selectedCity.city_display_value }}</p>
    <button @click="selectedCity = null" class="mt-4 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">关闭</button>
</div>

        <!-- Submit Order -->
        <h2 class="text-lg font-semibold mt-8 mb-2">提交命令</h2>
        <form method="POST" action="submit_order.php" class="mb-8 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
            <select name="city_id" required class="w-full p-2 mb-2 border rounded">
                <option value="">选择城市</option>
                <option v-for="city in cities" :value="city.id">{{ city.name }}</option>
            </select>
            <input type="hidden" name="round" :value="currentRound">
            <textarea name="content" placeholder="输入命令内容" required class="w-full p-2 mb-2 border rounded h-24"></textarea>
            <select name="type" required class="w-full p-2 mb-2 border rounded">
                <option value="public">公开</option>
                <option value="secret">秘密</option>
            </select>
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">提交命令</button>
        </form>

        <!-- Public Orders -->
        <h2 class="text-lg font-semibold mb-2">当前回合公开命令</h2>
        <div class="bg-white p-4 rounded-lg shadow mb-8">
            <ul v-if="publicOrders.length" class="list-disc pl-5">
                <li v-for="order in publicOrders" :key="order.id">
                    {{ order.content }} (由 {{ order.username }} 提交)
                    <span v-if="order.admin_reply"> | 回复: {{ order.admin_reply }}</span>
                </li>
            </ul>
            <p v-else class="text-gray-500">无公开命令。</p>
        </div>

        <!-- Order History -->
        <h2 class="text-lg font-semibold mb-2">命令历史</h2>
        <div class="bg-white p-4 rounded-lg shadow">
            <div v-if="orders.length === 0">
                <p class="text-gray-500">无命令历史。</p>
            </div>
            <div v-else v-for="order in orders" :key="order.id" class="mb-4">
                <p><strong>城市:</strong> {{ order.city_name }}</p>
                <p><strong>回合:</strong> {{ order.round }}</p>
                <p><strong>类型:</strong> {{ order.type === 'public' ? '公开' : '秘密' }}</p>
                <p><strong>内容:</strong> {{ order.content }}</p>
                <p><strong>回复:</strong> {{ order.admin_reply || '等待回复' }}</p>
                <p><strong>时间:</strong> {{ order.submitted_at }}</p>
            </div>
        </div>

        <!-- Rules Modal -->
        <div v-if="showRules" class="modal" @click.self="showRules = false">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">游戏规则</h2>
                <p class="mb-4"><?php echo htmlspecialchars($game['rules']); ?></p>
                <button @click="showRules = false" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 modal-close">关闭</button>
            </div>
        </div>

        <!-- History Modal -->
        <div v-if="showHistory" class="modal" @click.self="closeHistoryModal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">回合历史</h2>
                <div v-if="historyRounds.length === 0" class="mb-4">
                    <p>无历史记录。</p>
                </div>
                <div v-else v-for="round in historyRounds" :key="round.id" class="mb-4">
                    <h3 class="text-lg font-semibold">回合 {{ round.round_number }}</h3>
                    <p>结束时间: {{ round.end_time }}</p>
                    <h4 class="font-semibold mt-2">公告</h4>
                    <ul v-if="round.announcements && round.announcements.length" class="list-disc pl-5">
                        <li v-for="announcement in round.announcements" :key="announcement.id">
                            {{ announcement.content }} (发布于 {{ announcement.created_at }})
                        </li>
                    </ul>
                    <p v-else class="pl-5">此回合无公告。</p>
                    <h4 class="font-semibold mt-2">命令</h4>
                    <ul v-if="round.orders && round.orders.length" class="list-disc pl-5">
                        <li v-for="order in round.orders" :key="order.id">
                            {{ order.type === 'public' ? '公开' : '秘密' }}: {{ order.content }} (由 {{ order.username }} 提交)
                            <span v-if="order.admin_reply"> | 回复: {{ order.admin_reply }}</span>
                        </li>
                    </ul>
                    <p v-else class="pl-5">此回合无命令。</p>
                    <p v-if="round.error" class="text-red-500 pl-5">{{ round.error }}</p>
                </div>
                <div class="pagination">
                    <button
                        v-if="currentPage > 1"
                        @click="changePage(currentPage - 1)"
                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                    >上一页</button>
                    <span>第 {{ currentPage }} 页 / 共 {{ totalPages }} 页</span>
                    <button
                        v-if="currentPage < totalPages"
                        @click="changePage(currentPage + 1)"
                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                    >下一页</button>
                </div>
                <button @click="closeHistoryModal" class="mt-4 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 modal-close">关闭</button>
            </div>
        </div>
    </div>

    <script>
        try {
            const app = Vue.createApp({
                data() {
                    return {
                        cities: <?php echo json_encode($cities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                        filteredCities: <?php echo json_encode($cities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                        selectedCity: null,
                        currentRound: <?php echo $current_round['round_number'] ?? 0; ?>,
                        publicOrders: [],
                        showRules: false,
                        showHistory: false,
                        historyRounds: <?php echo json_encode($history_rounds_safe, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                        currentPage: <?php echo $page; ?>,
                        totalPages: <?php echo $total_pages; ?>,
                        zoom: 1,
                        translateX: 0,
                        translateY: 0,
                        isDragging: false,
                        startX: 0,
                        startY: 0,
                        showCityNames: false, // Client-side toggle, default to false
                        players: <?php echo json_encode($players, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                        selectedPlayer: '',
                        orders: <?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                        backgroundImage: '<?php echo htmlspecialchars($game['background_image'] ?? ''); ?>'
                    };
                },
                computed: {
                    playerStats() {
                        if (!this.selectedPlayer) return {};
                        const ownedCities = this.cities.filter(city => city.player_username === this.selectedPlayer);
                        return {
                            population: ownedCities.reduce((sum, city) => sum + (parseFloat(city.population) || 0), 0).toFixed(2),
                            resources: ownedCities.reduce((sum, city) => sum + (parseFloat(city.resources) || 0), 0).toFixed(2),
                            economy: ownedCities.reduce((sum, city) => sum + (parseFloat(city.economy) || 0), 0).toFixed(2),
                            military: ownedCities.reduce((sum, city) => sum + (parseFloat(city.military) || 0), 0).toFixed(2),
                            culture: ownedCities.reduce((sum, city) => sum + (parseFloat(city.culture) || 0), 0).toFixed(2),
                            science: ownedCities.reduce((sum, city) => sum + (parseFloat(city.science) || 0), 0).toFixed(2),
                            infrastructure: ownedCities.reduce((sum, city) => sum + (parseFloat(city.infrastructure) || 0), 0).toFixed(2),
                            health: ownedCities.reduce((sum, city) => sum + (parseFloat(city.health) || 0), 0).toFixed(2),
                            education: ownedCities.reduce((sum, city) => sum + (parseFloat(city.education) || 0), 0).toFixed(2),
                            stability: ownedCities.reduce((sum, city) => sum + (parseFloat(city.stability) || 0), 0).toFixed(2),
                            cityCount: ownedCities.length
                        };
                    }
                },
                methods: {
                    showCityDetails(city) {
                        this.selectedCity = city;
                    },
                    loadPublicOrders() {
                        fetch('view_orders.php?game_id=<?php echo $game_id; ?>&type=public')
                            .then(response => {
                                if (!response.ok) throw new Error('无法获取公开命令: ' + response.statusText);
                                return response.text();
                            })
                            .then(data => {
                                this.publicOrders = data.split('\n').filter(line => line).map(line => {
                                    let [id, content, username, admin_reply] = line.split('|');
                                    return { id, content, username, admin_reply };
                                });
                            })
                            .catch(error => {
                                console.error('获取公开命令错误:', error);
                                this.publicOrders = [];
                            });
                    },
                    loadRoundOrders(round) {
                        fetch('view_round_orders.php?game_id=<?php echo $game_id; ?>&round=' + round.round_number)
                            .then(response => {
                                if (!response.ok) throw new Error('无法获取回合命令: ' + response.statusText);
                                return response.text();
                            })
                            .then(data => {
                                const orders = data.split('\n').filter(line => line).map(line => {
                                    let [id, content, username, type, admin_reply] = line.split('|');
                                    return {
                                        id: id || '',
                                        content: content || '',
                                        username: username || '未知',
                                        type: type || 'public',
                                        admin_reply: admin_reply || ''
                                    };
                                });
                                this.$set(this.historyRounds, this.historyRounds.indexOf(round), {
                                    ...round,
                                    orders
                                });
                            })
                            .catch(error => {
                                console.error('获取回合 ' + round.round_number + ' 命令错误:', error);
                                this.$set(this.historyRounds, this.historyRounds.indexOf(round), {
                                    ...round,
                                    orders: [],
                                    error: '无法加载命令：' + error.message
                                });
                            });
                    },
                    loadRoundAnnouncements(round) {
                        fetch('view_announcements.php?game_id=<?php echo $game_id; ?>&round=' + round.round_number)
                            .then(response => {
                                if (!response.ok) throw new Error('无法获取公告: ' + response.statusText);
                                return response.json();
                            })
                            .then(data => {
                                this.$set(this.historyRounds, this.historyRounds.indexOf(round), {
                                    ...round,
                                    announcements: data
                                });
                            })
                            .catch(error => {
                                console.error('获取回合 ' + round.round_number + ' 公告错误:', error);
                                this.$set(this.historyRounds, this.historyRounds.indexOf(round), {
                                    ...round,
                                    announcements: [],
                                    error: round.error ? round.error + '; 无法加载公告：' + error.message : '无法加载公告：' + error.message
                                });
                            });
                    },
                    openHistoryModal() {
                        this.showHistory = true;
                        if (this.historyRounds.length > 0) {
                            this.historyRounds.forEach(round => {
                                if (!round.orders || round.orders.length === 0) {
                                    this.loadRoundOrders(round);
                                }
                                if (!round.announcements || round.announcements.length === 0) {
                                    this.loadRoundAnnouncements(round);
                                }
                            });
                        }
                    },
                    closeHistoryModal() {
                        this.showHistory = false;
                    },
                    changePage(page) {
                        window.location.href = 'index.php?game_id=<?php echo $game_id; ?>&history_page=' + page;
                    },
                    zoomIn() {
                        this.zoom = Math.min(this.zoom + 0.2, 3);
                        this.adjustBoundaries();
                    },
                    zoomOut() {
                        this.zoom = Math.max(this.zoom - 0.2, 0.5);
                        this.adjustBoundaries();
                    },
                    resetMap() {
                        this.translateX = 0;
                        this.translateY = 0;
                        this.zoom = 1;
                        this.adjustBoundaries();
                    },
                    adjustBoundaries() {
                        const containerWidth = this.$refs.svg.parentElement.clientWidth;
                        const containerHeight = this.$refs.svg.parentElement.clientHeight;
                        const svgWidth = 800 * this.zoom;
                        const svgHeight = 600 * this.zoom;
                        this.translateX = Math.min(0, Math.max(this.translateX, -(svgWidth - containerWidth)));
                        this.translateY = Math.min(0, Math.max(this.translateY, -(svgHeight - containerHeight)));
                    },
                    startDrag(event) {
                        event.preventDefault();
                        this.isDragging = true;
                        const clientX = event.type.includes('touch') ? event.touches[0].clientX : event.clientX;
                        const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
                        this.startX = clientX - this.translateX;
                        this.startY = clientY - this.translateY;
                    },
                    drag(event) {
                        if (!this.isDragging) return;
                        event.preventDefault();
                        const clientX = event.type.includes('touch') ? event.touches[0].clientX : event.clientX;
                        const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
                        this.translateX = clientX - this.startX;
                        this.translateY = clientY - this.startY;
                        this.adjustBoundaries();
                    },
                    endDrag() {
                        this.isDragging = false;
                    },
                    updateFilteredCities() {
                        if (!this.selectedPlayer) {
                            this.filteredCities = this.cities;
                            return;
                        }
                        this.filteredCities = this.cities.filter(city => city.player_username === this.selectedPlayer);
                    }
                },
                watch: {
                    selectedPlayer() {
                        this.updateFilteredCities();
                    }
                },
                directives: {
                    draggable: {
                        mounted(el, binding) {
                            const instance = binding.instance;
                            el.addEventListener('mousedown', (e) => instance.startDrag(e));
                            el.addEventListener('mousemove', (e) => instance.drag(e));
                            el.addEventListener('mouseup', () => instance.endDrag());
                            el.addEventListener('mouseleave', () => instance.endDrag());
                            el.addEventListener('touchstart', (e) => instance.startDrag(e));
                            el.addEventListener('touchmove', (e) => instance.drag(e));
                            el.addEventListener('touchend', () => instance.endDrag());
                        }
                    }
                },
                mounted() {
                    this.adjustBoundaries();
                    this.updateFilteredCities();
                    this.loadPublicOrders();
                }
            });
            app.mount('#app');
        } catch (error) {
            console.error('无法初始化 Vue 应用:', error);
        }
    </script>
</body>
</html>