<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'game_admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $rules = trim($_POST['rules'] ?? '');
    $show_city_names = isset($_POST['show_city_names']) ? 1 : 0;
    $creator_id = $_SESSION['user_id'];

    if (empty($name) || empty($rules)) {
        header('Location: create_game.php?error=游戏名称和规则为必填项。');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Create game
        $stmt = $pdo->prepare("INSERT INTO games (name, creator_id, rules, show_city_names) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $creator_id, $rules, $show_city_names]);
        $game_id = $pdo->lastInsertId();

        // Create initial round
        $end_time = date('Y-m-d H:i:s', strtotime('+1 day'));
        $stmt = $pdo->prepare("INSERT INTO rounds (game_id, round_number, is_active, end_time) VALUES (?, 1, 1, ?)");
        $stmt->execute([$game_id, $end_time]);

        // Add cities
        $cities = $_POST['cities'] ?? [];
        if (empty($cities)) {
            $cities = [
                [
                    'name' => '城市 A',
                    'x' => 100,
                    'y' => 100,
                    'description' => '繁荣的城市',
                    'population' => 100000,
                    'resources' => 500,
                    'growth_rate' => 2.5,
                    'economy' => 500,
                    'economy_growth' => 0,
                    'military' => 100,
                    'military_growth' => 0,
                    'culture' => 100,
                    'culture_growth' => 0,
                    'science' => 100,
                    'science_growth' => 0,
                    'infrastructure' => 100,
                    'infrastructure_growth' => 0,
                    'health' => 100,
                    'health_growth' => 0,
                    'education' => 100,
                    'education_growth' => 0,
                    'stability' => 100,
                    'stability_growth' => 0,
                    'city_display_type' => 'circle',
                    'city_display_value' => '',
                    'show_name' => 1
                ],
                [
                    'name' => '城市 B',
                    'x' => 300,
                    'y' => 200,
                    'description' => '沿海小镇',
                    'population' => 50000,
                    'resources' => 300,
                    'growth_rate' => 1.8,
                    'economy' => 300,
                    'economy_growth' => 0,
                    'military' => 50,
                    'military_growth' => 0,
                    'culture' => 50,
                    'culture_growth' => 0,
                    'science' => 50,
                    'science_growth' => 0,
                    'infrastructure' => 50,
                    'infrastructure_growth' => 0,
                    'health' => 50,
                    'health_growth' => 0,
                    'education' => 50,
                    'education_growth' => 0,
                    'stability' => 50,
                    'stability_growth' => 0,
                    'city_display_type' => 'text',
                    'city_display_value' => '林',
                    'show_name' => 1
                ]
            ];
        }
        $stmt = $pdo->prepare("
            INSERT INTO cities (
                game_id, name, x, y, description, population, resources, growth_rate, updated_at,
                city_display_type, city_display_value, economy, economy_growth, military, military_growth,
                culture, culture_growth, science, science_growth, infrastructure, infrastructure_growth,
                health, health_growth, education, education_growth, stability, stability_growth, show_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($cities as $city) {
            if (!empty($city['name']) && isset($city['x'], $city['y']) && is_numeric($city['x']) && is_numeric($city['y'])) {
                $stmt->execute([
                    $game_id,
                    $city['name'],
                    $city['x'],
                    $city['y'],
                    $city['description'] ?? '',
                    $city['population'] ?? 1000,
                    $city['resources'] ?? 500,
                    $city['growth_rate'] ?? 0,
                    $city['city_display_type'] ?? 'circle',
                    $city['city_display_value'] ?? '',
                    $city['economy'] ?? 500,
                    $city['economy_growth'] ?? 0,
                    $city['military'] ?? 100,
                    $city['military_growth'] ?? 0,
                    $city['culture'] ?? 100,
                    $city['culture_growth'] ?? 0,
                    $city['science'] ?? 100,
                    $city['science_growth'] ?? 0,
                    $city['infrastructure'] ?? 100,
                    $city['infrastructure_growth'] ?? 0,
                    $city['health'] ?? 100,
                    $city['health_growth'] ?? 0,
                    $city['education'] ?? 100,
                    $city['education_growth'] ?? 0,
                    $city['stability'] ?? 100,
                    $city['stability_growth'] ?? 0,
                    $city['show_name'] ?? 1
                ]);
            }
        }

        // Add creator as player
        $stmt = $pdo->prepare("INSERT INTO game_players (game_id, user_id) VALUES (?, ?)");
        $stmt->execute([$game_id, $creator_id]);

        $pdo->commit();
        header('Location: lobby.php?success=游戏创建成功。');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("游戏创建失败: " . $e->getMessage());
        header('Location: create_game.php?error=创建游戏失败: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>创建游戏</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="/assets/js/vue.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">创建新游戏</h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>
        <form method="POST" id="createGameForm">
            <input type="text" name="name" placeholder="游戏名称" required class="w-full p-2 mb-4 border rounded">
            <textarea name="rules" placeholder="游戏规则" required class="w-full p-2 mb-4 border rounded h-24"></textarea>
            <label class="block mb-4">
                <input type="checkbox" name="show_city_names" checked>
                在地图上显示城市名称
            </label>

            <h2 class="text-lg font-semibold mb-2">添加城市</h2>
            <div id="citiesApp">
                <div v-for="(city, index) in cities" :key="index" class="mb-4 p-4 bg-white rounded-lg shadow">
                    <input type="text" v-model="city.name" name="cities[][name]" placeholder="城市名称" required class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.x" name="cities[][x]" placeholder="X 坐标" required class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.y" name="cities[][y]" placeholder="Y 坐标" required class="w-full p-2 mb-2 border rounded">
                    <textarea v-model="city.description" name="cities[][description]" placeholder="描述" class="w-full p-2 mb-2 border rounded h-16"></textarea>
                    <select v-model="city.city_display_type" name="cities[][city_display_type]" class="w-full p-2 mb-2 border rounded">
                        <option value="circle">圆形</option>
                        <option value="image">图片</option>
                        <option value="text">文本</option>
                        <option value="none">无</option>
                    </select>
                    <input v-if="city.city_display_type === 'image' || city.city_display_type === 'text'" type="text" v-model="city.city_display_value" name="cities[][city_display_value]" placeholder="图片 URL 或文本" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.population" name="cities[][population]" placeholder="人口" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.resources" name="cities[][resources]" placeholder="资源" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.growth_rate" name="cities[][growth_rate]" placeholder="人口增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.economy" name="cities[][economy]" placeholder="经济" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.economy_growth" name="cities[][economy_growth]" placeholder="经济增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.military" name="cities[][military]" placeholder="军事" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.military_growth" name="cities[][military_growth]" placeholder="军事增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.culture" name="cities[][culture]" placeholder="文化" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.culture_growth" name="cities[][culture_growth]" placeholder="文化增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.science" name="cities[][science]" placeholder="科技" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.science_growth" name="cities[][science_growth]" placeholder="科技增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.infrastructure" name="cities[][infrastructure]" placeholder="基础设施" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.infrastructure_growth" name="cities[][infrastructure_growth]" placeholder="基础设施增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.health" name="cities[][health]" placeholder="健康" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.health_growth" name="cities[][health_growth]" placeholder="健康增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.education" name="cities[][education]" placeholder="教育" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.education_growth" name="cities[][education_growth]" placeholder="教育增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <input type="number" v-model.number="city.stability" name="cities[][stability]" placeholder="稳定性" class="w-full p-2 mb-2 border rounded">
                    <input type="number" step="0.01" v-model.number="city.stability_growth" name="cities[][stability_growth]" placeholder="稳定性增长率 (%)" class="w-full p-2 mb-2 border rounded">
                    <label class="block mb-2">
                        <input type="checkbox" v-model="city.show_name" name="cities[][show_name]" :value="1">
                        显示城市名称
                    </label>
                    <button type="button" @click="removeCity(index)" class="bg-red-500 text-white p-2 rounded hover:bg-red-600">移除</button>
                </div>
                <button type="button" @click="addCity" class="bg-green-500 text-white p-2 rounded hover:bg-green-600 mb-4">添加城市</button>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">创建游戏</button>
        </form>
        <a href="lobby.php" class="mt-4 inline-block text-blue-500 hover:underline">返回大厅</a>
    </div>
    <script>
        try {
            const citiesApp = Vue.createApp({
                data() {
                    return {
                        cities: []
                    };
                },
                methods: {
                    addCity() {
                        this.cities.push({
                            name: '',
                            x: 100,
                            y: 100,
                            description: '',
                            population: 1000,
                            resources: 500,
                            growth_rate: 0,
                            economy: 500,
                            economy_growth: 0,
                            military: 100,
                            military_growth: 0,
                            culture: 100,
                            culture_growth: 0,
                            science: 100,
                            science_growth: 0,
                            infrastructure: 100,
                            infrastructure_growth: 0,
                            health: 100,
                            health_growth: 0,
                            education: 100,
                            education_growth: 0,
                            stability: 100,
                            stability_growth: 0,
                            city_display_type: 'circle',
                            city_display_value: '',
                            show_name: 1
                        });
                    },
                    removeCity(index) {
                        this.cities.splice(index, 1);
                    }
                }
            });
            citiesApp.mount('#citiesApp');
        } catch (error) {
            console.error('无法初始化 Vue 应用:', error);
        }
    </script>
</body>
</html>
