<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'game_admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

$game_id = $_GET['game_id'] ?? null;
if (!$game_id || !is_numeric($game_id)) {
    header('Location: lobby.php?error=无效的游戏ID。');
    exit;
}

// Verify game exists and user is authorized
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game || ($game['creator_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'super_admin')) {
    header('Location: lobby.php?error=您无权管理此游戏。');
    exit;
}

// Load custom field names
$stmt = $pdo->prepare("SELECT field_name, display_name FROM game_field_names WHERE game_id = ?");
$stmt->execute([$game_id]);
$field_names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Load cities with player tags
$stmt = $pdo->prepare("
    SELECT c.*, cp.player_tag AS player_username
    FROM cities c
    LEFT JOIN city_players cp ON c.id = cp.city_id AND c.game_id = cp.game_id
    WHERE c.game_id = ?
");
$stmt->execute([$game_id]);
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Handle city update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_city'])) {
    error_log('POST Data: ' . print_r($_POST, true)); // Debug
    $city_id = $_POST['city_id'] ?? null;
    $name = trim($_POST['name'] ?? '');

    if (!$city_id || !is_numeric($city_id) || empty($name)) {
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&error=城市ID或名称不能为空。');
        exit;
    }
    if (!is_numeric($_POST['x'] ?? null) || !is_numeric($_POST['y'] ?? null)) {
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&error=坐标必须为数字。');
        exit;
    }
    if (!empty($_POST['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'])) {
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&error=颜色格式无效，必须为六位十六进制代码（如 #0000FF）。');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE cities SET
                name = ?, x = ?, y = ?, description = ?, type = ?, population = ?, resources = ?, growth_rate = ?,
                updated_at = NOW(), city_display_type = ?, city_display_value = ?, economy = ?, military = ?,
                military_growth = ?, culture = ?, culture_growth = ?, science = ?, science_growth = ?,
                infrastructure = ?, infrastructure_growth = ?, health = ?, health_growth = ?, education = ?,
                education_growth = ?, stability = ?, stability_growth = ?, show_name = ?, color = ?,
                food_consumption = ?, money_consumption = ?
            WHERE id = ? AND game_id = ?
        ");
        $stmt->execute([
            $name,
            $_POST['x'],
            $_POST['y'],
            trim($_POST['description'] ?? ''),
            $_POST['type'] ?? 'city',
            $_POST['population'] ?? null,
            $_POST['resources'] ?? null,
            $_POST['type'] === 'city' ? ($_POST['growth_rate'] ?? null) : null,
            $_POST['city_display_type'] ?? ($_POST['type'] === 'city' ? 'circle' : 'text'),
            trim($_POST['city_display_value'] ?? ''),
            $_POST['type'] === 'city' ? ($_POST['economy'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['military'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['military_growth'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['culture'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['culture_growth'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['science'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['science_growth'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['infrastructure'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['infrastructure_growth'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['health'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['health_growth'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['education'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['education_growth'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['stability'] ?? null) : null,
            $_POST['type'] === 'city' ? ($_POST['stability_growth'] ?? null) : null,
            isset($_POST['show_name']) ? 1 : 0,
            $_POST['color'] ?: null,
            $_POST['food_consumption'] ?? null,
            $_POST['money_consumption'] ?? null,
            $city_id,
            $game_id
        ]);
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&success=城市更新成功。');
        exit;
    } catch (Exception $e) {
        error_log("城市更新失败: " . $e->getMessage());
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&error=城市更新失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

// Handle city deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_city'])) {
    $city_id = $_POST['city_id'] ?? null;
    if (!$city_id || !is_numeric($city_id)) {
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&error=无效的城市ID。');
        exit;
    }
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM cities WHERE id = ? AND game_id = ?");
        $stmt->execute([$city_id, $game_id]);
        $stmt = $pdo->prepare("DELETE FROM city_players WHERE city_id = ? AND game_id = ?");
        $stmt->execute([$city_id, $game_id]);
        $pdo->commit();
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&success=城市删除成功。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("城市删除失败: " . $e->getMessage());
        header('Location: dynamic_edit.php?game_id=' . $game_id . '&error=城市删除失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>动态编辑 - <?php echo htmlspecialchars($game['name']); ?></title>
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
            z-index: 30;
            transition: all 0.3s ease;
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
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-close {
            cursor: pointer;
            z-index: 1001;
        }
        .draggable:hover {
            cursor: move;
        }
        .arrow-controls {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app" class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">动态编辑: <?php echo htmlspecialchars($game['name']); ?></h1>
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
                <g v-for="city in cities" :key="city.id">
                    <circle
                        v-if="city.city_display_type === 'circle'"
                        :cx="city.x"
                        :cy="city.y"
                        r="10"
                        :fill="city.color || 'blue'"
                        class="draggable"
                        @click="selectCity(city)"
                        @mousedown="startCityDrag($event, city)"
                        @touchstart="startCityDrag($event, city)"
                    />
                    <image
                        v-if="city.city_display_type === 'image' && city.city_display_value"
                        :x="city.x - 15"
                        :y="city.y - 15"
                        width="30"
                        height="30"
                        :href="city.city_display_value"
                        class="draggable"
                        @click="selectCity(city)"
                        @mousedown="startCityDrag($event, city)"
                        @touchstart="startCityDrag($event, city)"
                    />
                    <text
                        v-if="city.city_display_type === 'text' && city.city_display_value"
                        :x="city.x"
                        :y="city.y"
                        :fill="city.color || 'black'"
                        font-size="20"
                        text-anchor="middle"
                        class="draggable"
                        @click="selectCity(city)"
                        @mousedown="startCityDrag($event, city)"
                        @touchstart="startCityDrag($event, city)"
                    >{{ city.city_display_value }}</text>
                    <text
                        v-if="city.show_name"
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

        <!-- City Edit Form (Modal) -->
        <div v-if="selectedCity" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">编辑 {{ selectedCity.name }}</h2>
                <form @submit.prevent="submitCityUpdate" class="space-y-2">
                    <input type="hidden" name="edit_city" value="1">
                    <input type="hidden" name="city_id" :value="selectedCity.id">
                    <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                    <label class="block text-sm font-medium">名称</label>
                    <input type="text" v-model="selectedCity.name" name="name" required class="w-full p-2 border rounded">
                    <label class="block text-sm font-medium">X 坐标</label>
                    <input type="number" v-model.number="selectedCity.x" name="x" required class="w-full p-2 border rounded">
                    <label class="block text-sm font-medium">Y 坐标</label>
                    <input type="number" v-model.number="selectedCity.y" name="y" required class="w-full p-2 border rounded">
                    <div class="arrow-controls">
                        <button type="button" @click="moveCity('up')" class="bg-gray-300 px-2 py-1 rounded">↑</button>
                        <button type="button" @click="moveCity('down')" class="bg-gray-300 px-2 py-1 rounded">↓</button>
                        <button type="button" @click="moveCity('left')" class="bg-gray-300 px-2 py-1 rounded">←</button>
                        <button type="button" @click="moveCity('right')" class="bg-gray-300 px-2 py-1 rounded">→</button>
                    </div>
                    <label class="block text-sm font-medium">颜色（六位十六进制，如 #0000FF）</label>
                    <input type="text" v-model="selectedCity.color" name="color" class="w-full p-2 border rounded" placeholder="#0000FF">
                    <label class="block text-sm font-medium">类型</label>
                    <select v-model="selectedCity.type" name="type" class="w-full p-2 border rounded" @change="toggleCityFields">
                        <option value="city">城市</option>
                        <option value="mountain">军团</option>
                        <option value="forest">特殊</option>
                        <option value="ocean">障碍</option>
                    </select>
                    <label class="block text-sm font-medium">描述</label>
                    <textarea v-model="selectedCity.description" name="description" class="w-full p-2 border rounded h-16"></textarea>
                    <label class="block text-sm font-medium">显示类型</label>
                    <select v-model="selectedCity.city_display_type" name="city_display_type" class="w-full p-2 border rounded">
                        <option value="circle">圆形</option>
                        <option value="image">图片</option>
                        <option value="text">文本</option>
                        <option value="none">无</option>
                    </select>
                    <label class="block text-sm font-medium">显示值</label>
                    <input type="text" v-model="selectedCity.city_display_value" name="city_display_value" class="w-full p-2 border rounded" placeholder="图片 URL 或文本">
                    <div class="city-fields space-y-2">
                        <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?></label>
                        <input type="number" v-model.number="selectedCity.population" name="population" class="w-full p-2 border rounded">
                        <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['resources'] ?? '物资储备'); ?></label>
                        <input type="number" v-model.number="selectedCity.resources" name="resources" class="w-full p-2 border rounded">
                        <label class="block text-sm font-medium">耗粮</label>
                        <input type="number" step="0.01" v-model.number="selectedCity.food_consumption" name="food_consumption" class="w-full p-2 border rounded">
                        <label class="block text-sm font-medium">耗钱</label>
                        <input type="number" step="0.01" v-model.number="selectedCity.money_consumption" name="money_consumption" class="w-full p-2 border rounded">
                        <template v-if="selectedCity.type === 'city'">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['population'] ?? '居民数量'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.growth_rate" name="growth_rate" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['economy'] ?? '财富指数'); ?></label>
                            <input type="number" v-model.number="selectedCity.economy" name="economy" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['military'] ?? '军力水平'); ?></label>
                            <input type="number" v-model.number="selectedCity.military" name="military" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['military'] ?? '军力水平'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.military_growth" name="military_growth" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['culture'] ?? '文化影响力'); ?></label>
                            <input type="number" v-model.number="selectedCity.culture" name="culture" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['culture'] ?? '文化影响力'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.culture_growth" name="culture_growth" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['science'] ?? '科技进展'); ?></label>
                            <input type="number" v-model.number="selectedCity.science" name="science" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['science'] ?? '科技进展'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.science_growth" name="science_growth" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['infrastructure'] ?? '基础建设'); ?></label>
                            <input type="number" v-model.number="selectedCity.infrastructure" name="infrastructure" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['infrastructure'] ?? '基础建设'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.infrastructure_growth" name="infrastructure_growth" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['health'] ?? '公共卫生'); ?></label>
                            <input type="number" v-model.number="selectedCity.health" name="health" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['health'] ?? '公共卫生'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.health_growth" name="health_growth" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['education'] ?? '教育水平'); ?></label>
                            <input type="number" v-model.number="selectedCity.education" name="education" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['education'] ?? '教育水平'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.education_growth" name="education_growth" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?></label>
                            <input type="number" v-model.number="selectedCity.stability" name="stability" class="w-full p-2 border rounded">
                            <label class="block text-sm font-medium"><?php echo htmlspecialchars($field_names['stability'] ?? '社会稳定'); ?>增长率 (%)</label>
                            <input type="number" step="0.01" v-model.number="selectedCity.stability_growth" name="stability_growth" class="w-full p-2 border rounded">
                        </template>
                    </div>
                    <label class="block">
                        <input type="checkbox" v-model="selectedCity.show_name" name="show_name" :true-value="1" :false-value="0">
                        显示名称
                    </label>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">更新</button>
                        <button type="button" @click="confirmDelete" class="bg-red-500 text-white p-2 rounded hover:bg-red-600">删除</button>
                        <button type="button" @click="selectedCity = null" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600">取消</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">确认删除</h2>
                <p>确定要删除城市 "{{ selectedCity.name }}" 吗？此操作不可撤销。</p>
                <form @submit.prevent="submitCityDelete" class="mt-4 flex gap-2">
                    <input type="hidden" name="delete_city" value="1">
                    <input type="hidden" name="city_id" :value="selectedCity.id">
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
                    cities: <?php echo json_encode($cities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?: '[]'; ?>,
                    selectedCity: null,
                    zoom: 1,
                    translateX: 0,
                    translateY: 0,
                    isDraggingMap: false,
                    isDraggingCity: false,
                    startX: 0,
                    startY: 0,
                    draggedCity: null,
                    showDeleteConfirm: false,
                    backgroundImage: '<?php echo htmlspecialchars($game['background_image'] ?? ''); ?>'
                };
            },
            methods: {
                selectCity(city) {
                    if (this.isDraggingCity) return;
                    this.selectedCity = {
                        ...city,
                        id: city.id,
                        name: city.name || '',
                        x: city.x || 0,
                        y: city.y || 0,
                        type: city.type || 'city',
                        description: city.description || '',
                        city_display_type: city.city_display_type || 'circle',
                        city_display_value: city.city_display_value || '',
                        show_name: city.show_name ? 1 : 0,
                        color: city.color || '',
                        population: city.population ?? null,
                        resources: city.resources ?? null,
                        growth_rate: city.growth_rate ?? null,
                        economy: city.economy ?? null,
                        military: city.military ?? null,
                        military_growth: city.military_growth ?? null,
                        culture: city.culture ?? null,
                        culture_growth: city.culture_growth ?? null,
                        science: city.science ?? null,
                        science_growth: city.science_growth ?? null,
                        infrastructure: city.infrastructure ?? null,
                        infrastructure_growth: city.infrastructure_growth ?? null,
                        health: city.health ?? null,
                        health_growth: city.health_growth ?? null,
                        education: city.education ?? null,
                        education_growth: city.education_growth ?? null,
                        stability: city.stability ?? null,
                        stability_growth: city.stability_growth ?? null,
                        food_consumption: city.food_consumption ?? null,
                        money_consumption: city.money_consumption ?? null
                    };
                },
                toggleCityFields() {
                    if (this.selectedCity.type !== 'city') {
                        ['growth_rate', 'economy', 'military', 'military_growth',
                         'culture', 'culture_growth', 'science', 'science_growth', 'infrastructure', 'infrastructure_growth',
                         'health', 'health_growth', 'education', 'education_growth', 'stability', 'stability_growth']
                            .forEach(field => this.selectedCity[field] = null);
                    }
                },
                submitCityUpdate() {
                    if (!this.selectedCity || !this.selectedCity.id || !this.selectedCity.name) {
                        alert('城市ID或名称不能为空。');
                        return;
                    }
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    const fields = {
                        edit_city: '1',
                        city_id: this.selectedCity.id,
                        name: this.selectedCity.name,
                        x: this.selectedCity.x,
                        y: this.selectedCity.y,
                        type: this.selectedCity.type,
                        description: this.selectedCity.description || '',
                        city_display_type: this.selectedCity.city_display_type || 'circle',
                        city_display_value: this.selectedCity.city_display_value || '',
                        show_name: this.selectedCity.show_name ? '1' : '0',
                        color: this.selectedCity.color || '',
                        population: this.selectedCity.population ?? '',
                        resources: this.selectedCity.resources ?? '',
                        growth_rate: this.selectedCity.type === 'city' ? (this.selectedCity.growth_rate ?? '') : '',
                        economy: this.selectedCity.type === 'city' ? (this.selectedCity.economy ?? '') : '',
                        military: this.selectedCity.type === 'city' ? (this.selectedCity.military ?? '') : '',
                        military_growth: this.selectedCity.type === 'city' ? (this.selectedCity.military_growth ?? '') : '',
                        culture: this.selectedCity.type === 'city' ? (this.selectedCity.culture ?? '') : '',
                        culture_growth: this.selectedCity.type === 'city' ? (this.selectedCity.culture_growth ?? '') : '',
                        science: this.selectedCity.type === 'city' ? (this.selectedCity.science ?? '') : '',
                        science_growth: this.selectedCity.type === 'city' ? (this.selectedCity.science_growth ?? '') : '',
                        infrastructure: this.selectedCity.type === 'city' ? (this.selectedCity.infrastructure ?? '') : '',
                        infrastructure_growth: this.selectedCity.type === 'city' ? (this.selectedCity.infrastructure_growth ?? '') : '',
                        health: this.selectedCity.type === 'city' ? (this.selectedCity.health ?? '') : '',
                        health_growth: this.selectedCity.type === 'city' ? (this.selectedCity.health_growth ?? '') : '',
                        education: this.selectedCity.type === 'city' ? (this.selectedCity.education ?? '') : '',
                        education_growth: this.selectedCity.type === 'city' ? (this.selectedCity.education_growth ?? '') : '',
                        stability: this.selectedCity.type === 'city' ? (this.selectedCity.stability ?? '') : '',
                        stability_growth: this.selectedCity.type === 'city' ? (this.selectedCity.stability_growth ?? '') : '',
                        food_consumption: this.selectedCity.food_consumption ?? '',
                        money_consumption: this.selectedCity.money_consumption ?? ''
                    };
                    for (const [name, value] of Object.entries(fields)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    }
                    document.body.appendChild(form);
                    console.log('Submitting form with data:', fields); // Debug
                    form.submit();
                },
                confirmDelete() {
                    this.showDeleteConfirm = true;
                },
                submitCityDelete() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_city';
                    deleteInput.value = '1';
                    form.appendChild(deleteInput);
                    const cityIdInput = document.createElement('input');
                    cityIdInput.type = 'hidden';
                    cityIdInput.name = 'city_id';
                    cityIdInput.value = this.selectedCity.id;
                    form.appendChild(cityIdInput);
                    document.body.appendChild(form);
                    form.submit();
                },
                moveCity(direction) {
                    const step = 10;
                    switch (direction) {
                        case 'up': this.selectedCity.y -= step; break;
                        case 'down': this.selectedCity.y += step; break;
                        case 'left': this.selectedCity.x -= step; break;
                        case 'right': this.selectedCity.x += step; break;
                    }
                    this.updateCityPosition();
                },
                startCityDrag(event, city) {
                    event.stopPropagation();
                    this.isDraggingCity = true;
                    this.draggedCity = city;
                    const clientX = event.type.includes('touch') ? event.touches[0].clientX : event.clientX;
                    const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
                    this.startX = clientX;
                    this.startY = clientY;
                    document.addEventListener('mousemove', this.dragCity);
                    document.addEventListener('touchmove', this.dragCity);
                    document.addEventListener('mouseup', this.endCityDrag);
                    document.addEventListener('touchend', this.endCityDrag);
                },
                dragCity(event) {
                    if (!this.isDraggingCity) return;
                    event.preventDefault();
                    const clientX = event.type.includes('touch') ? event.touches[0].clientX : event.clientX;
                    const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
                    const deltaX = (clientX - this.startX) / this.zoom;
                    const deltaY = (clientY - this.startY) / this.zoom;
                    const cityIndex = this.cities.findIndex(c => c.id === this.draggedCity.id);
                    this.cities[cityIndex].x = parseFloat(this.draggedCity.x) + deltaX;
                    this.cities[cityIndex].y = parseFloat(this.draggedCity.y) + deltaY;
                    if (this.selectedCity && this.selectedCity.id === this.draggedCity.id) {
                        this.selectedCity.x = this.cities[cityIndex].x;
                        this.selectedCity.y = this.cities[cityIndex].y;
                    }
                    this.startX = clientX;
                    this.startY = clientY;
                },
                endCityDrag() {
                    this.isDraggingCity = false;
                    this.draggedCity = null;
                    document.removeEventListener('mousemove', this.dragCity);
                    document.removeEventListener('touchmove', this.dragCity);
                    document.removeEventListener('mouseup', this.endCityDrag);
                    document.removeEventListener('touchend', this.endCityDrag);
                },
                updateCityPosition() {
                    const cityIndex = this.cities.findIndex(c => c.id === this.selectedCity.id);
                    if (cityIndex !== -1) {
                        this.cities[cityIndex].x = this.selectedCity.x;
                        this.cities[cityIndex].y = this.selectedCity.y;
                    }
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
                startMapDrag(event) {
                    event.preventDefault();
                    this.isDraggingMap = true;
                    const clientX = event.type.includes('touch') ? event.touches[0].clientX : event.clientX;
                    const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
                    this.startX = clientX - this.translateX;
                    this.startY = clientY - this.translateY;
                },
                dragMap(event) {
                    if (!this.isDraggingMap) return;
                    event.preventDefault();
                    const clientX = event.type.includes('touch') ? event.touches[0].clientX : event.clientX;
                    const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
                    this.translateX = clientX - this.startX;
                    this.translateY = clientY - this.startY;
                    this.adjustBoundaries();
                },
                endMapDrag() {
                    this.isDraggingMap = false;
                }
            },
            directives: {
                draggable: {
                    mounted(el, binding) {
                        const instance = binding.instance;
                        el.addEventListener('mousedown', (e) => instance.startMapDrag(e));
                        el.addEventListener('mousemove', (e) => instance.dragMap(e));
                        el.addEventListener('mouseup', () => instance.endMapDrag());
                        el.addEventListener('mouseleave', () => instance.endMapDrag());
                        el.addEventListener('touchstart', (e) => instance.startMapDrag(e));
                        el.addEventListener('touchmove', (e) => instance.dragMap(e));
                        el.addEventListener('touchend', () => instance.endMapDrag());
                    }
                }
            },
            mounted() {
                this.adjustBoundaries();
            }
        });
        app.mount('#app');
    </script>
</body>
</html>