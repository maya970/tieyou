<?php
require 'check_auth.php';
require 'db.php';

$game_id = $_GET['game_id'] ?? null;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Fetch player data
    $stmt = $pdo->prepare("SELECT x, y, layer, health, stamina, action_count, move_count, last_move_hour FROM autorpg_players WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $user_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$player) {
        header('Location: autorpg_games.php');
        exit;
    }
    $player_x = $player['x'];
    $player_y = $player['y'];
    $current_layer = $player['layer'];
    $health = $player['health'];
    $stamina = $player['stamina'];
    $action_count = $player['action_count'] ?? 0;
    $move_count = $player['move_count'];
    $last_move_hour = $player['last_move_hour'] ?? null;

    // Check if building master
    $stmt = $pdo->prepare("SELECT id FROM autorpg_building_masters WHERE game_id = ? AND layer = ? AND user_id = ?");
    $stmt->execute([$game_id, $current_layer, $user_id]);
    $is_building_master = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

    // Fetch map tiles
    $stmt = $pdo->prepare("SELECT x, y, img_url, terrain_type, passable FROM autorpg_map_tiles WHERE game_id = ? AND layer = ?");
    $stmt->execute([$game_id, $current_layer]);
    $tiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tile_data = [];
    foreach ($tiles as $tile) {
        $tile_data[$tile['x']][$tile['y']] = [
            'img_url' => $tile['img_url'] ?? '',
            'terrain_type' => $tile['terrain_type'] ?? 'grass',
            'passable' => isset($tile['passable']) ? (bool)$tile['passable'] : true
        ];
    }
    // Fill missing tiles
    for ($x = 0; $x < 10; $x++) {
        for ($y = 0; $y < 10; $y++) {
            if (!isset($tile_data[$x][$y])) {
                $terrain = in_array([$x, $y], [[0,0], [0,9], [9,0], [9,9]]) ? 'door' : ($x == 5 && $y == 5 ? 'safety_zone' : 'grass');
                $tile_data[$x][$y] = [
                    'img_url' => '',
                    'terrain_type' => $terrain,
                    'passable' => true
                ];
            }
        }
    }

    // Fetch inventory
    $stmt = $pdo->prepare("SELECT pi.slot, pi.is_equipped, i.id, i.name, i.icon_url, i.description
                           FROM autorpg_player_inventory pi
                           JOIN autorpg_items i ON pi.item_id = i.id
                           WHERE pi.user_id = ? AND pi.game_id = ?");
    $stmt->execute([$user_id, $game_id]);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $inventory_data = array_fill(0, 10, null);
    $equipment_data = array_fill(0, 4, null);
    foreach ($inventory as $item) {
        if ($item['is_equipped']) {
            $equipment_data[$item['slot']] = $item;
        } else {
            $inventory_data[$item['slot']] = $item;
        }
    }

    // Fetch ground items
    $stmt = $pdo->prepare("SELECT gi.id, i.name, i.icon_url, i.description
                           FROM autorpg_ground_items gi
                           JOIN autorpg_items i ON gi.item_id = i.id
                           WHERE gi.game_id = ? AND gi.layer = ? AND gi.x = ? AND gi.y = ?");
    $stmt->execute([$game_id, $current_layer, $player_x, $player_y]);
    $ground_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch synthesis recipes
    $stmt = $pdo->query("SELECT id, item1_id, item2_id, result_item_id FROM autorpg_synthesis_recipes");
    $synthesis_recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in autorpg_index.php: " . $e->getMessage());
    $error = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AutoRPG Map</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script>
        if (!window.TWEEN) {
            console.warn('TWEEN.js not loaded. Using fallback.');
            window.TWEEN = {
                Tween: function(obj) {
                    return {
                        to: function(props, duration) {
                            this.props = props;
                            this.duration = duration;
                            return this;
                        },
                        start: function() {
                            Object.assign(obj, this.props);
                        }
                    };
                },
                update: function() {}
            };
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tween.js/23.1.3/tween.min.js" integrity="sha512-7g3r1yM7knM+h9tFUDU9ttWd0tH0B6IfnYQfK+jrZ9G+LG4UkwolTa1Tq1wcT0+VR0ur5unlQB7oF2mFs6V9h8w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100vh;
            min-height: 100vh;
            overflow: hidden;
            touch-action: none;
        }
        #gameCanvas {
            width: 100vw;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            touch-action: none;
        }
        .ui-panel {
            position: fixed;
            pointer-events: none;
            transition: all 0.3s ease;
            opacity: 0;
        }
        .ui-panel.active {
            opacity: 1;
            pointer-events: auto;
        }
        .ui-panel > * {
            pointer-events: auto;
        }
        .top-bar {
            top: 0;
            left: 0;
            width: 100%;
            transform: translateY(-100%);
        }
        .top-bar.active {
            transform: translateY(0);
        }
        .sidebar {
            right: 0;
            top: 0;
            height: 100%;
            width: 240px;
            transform: translateX(100%);
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .ground-items {
            bottom: 0;
            left: 0;
            width: 100%;
            transform: translateY(100%);
        }
        .ground-items.active {
            transform: translateY(0);
        }
        #errorMessage {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            z-index: 100;
            display: none;
        }
        .tooltip {
            display: none;
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 70;
        }
        .item-slot:hover .tooltip {
            display: block;
        }
        .item-slot {
            position: relative;
            user-select: none;
            touch-action: manipulation;
        }
        .item-slot.dragging {
            opacity: 0.5;
        }
        .item-slot.drop-target {
            border: 2px dashed #3b82f6;
        }
        .interact-btn {
            pointer-events: auto;
        }
        .toggle-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 50;
            background: #3b82f6;
            color: white;
            padding: 8px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #toggleSidebar { top: 60px; }
        #toggleGround { top: 110px; }
        @media (max-width: 640px) {
            .sidebar {
                width: 180px;
            }
            .text-lg {
                font-size: 1rem;
            }
            .item-slot {
                width: 8vw;
                height: 8vw;
                min-width: 32px;
                min-height: 32px;
            }
            .tooltip {
                font-size: 10px;
            }
            .toggle-btn {
                width: 36px;
                height: 36px;
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <div id="errorMessage"></div>
    <div id="gameCanvas"></div>
    <!-- Toggle Buttons -->
    <button id="toggleTopBar" class="toggle-btn" title="Toggle Stats">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
    <button id="toggleSidebar" class="toggle-btn" title="Toggle Inventory">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
        </svg>
    </button>
    <button id="toggleGround" class="toggle-btn" title="Toggle Ground Items">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
    </button>
    <!-- Top Bar: Health, Stamina, Actions, Reset Timer, Attributes -->
    <div id="topBar" class="ui-panel top-bar bg-gray-800 text-white p-2 flex justify-between">
        <div>
            <span>生命: <span id="health"><?php echo htmlspecialchars($health); ?></span>/100</span>
            <span class="ml-4">体力: <span id="stamina"><?php echo htmlspecialchars($stamina); ?></span>/100</span>
            <span class="ml-4">行动次数: <span id="actionCount"><?php echo htmlspecialchars($action_count); ?></span></span>
            <span class="ml-4">回合刷新: <span id="resetTimer">00:00</span></span>
        </div>
        <div>
            <button id="attributesBtn" class="bg-blue-500 p-1 rounded">Attributes</button>
            <?php if ($is_building_master): ?>
                <a href="building_master.php?game_id=<?php echo htmlspecialchars($game_id); ?>&layer=<?php echo htmlspecialchars($current_layer); ?>" class="bg-green-500 p-1 rounded ml-2">Building Master</a>
            <?php endif; ?>
        </div>
    </div>
    <!-- Attributes Modal -->
    <div id="attributesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-4 rounded">
            <h2 class="text-lg font-bold">Attributes</h2>
            <p>Strength: 10</p>
            <p>Agility: 10</p>
            <p>Endurance: 10</p>
            <button id="closeAttributes" class="bg-red-500 text-white p-1 rounded mt-2">Close</button>
        </div>
    </div>
    <!-- Right Sidebar: Inventory, Equipment, Synthesis -->
    <div id="sidebar" class="ui-panel sidebar bg-gray-700 text-white p-2">
        <h3 class="text-lg font-bold">背包</h3>
        <div id="inventory" class="grid grid-cols-5 gap-1 mb-4">
            <?php for ($i = 0; $i < 10; $i++): ?>
                <div class="item-slot bg-gray-600 w-10 h-10 relative" draggable="true" data-type="inventory" data-slot="<?php echo $i; ?>">
                    <?php if ($inventory_data[$i]): ?>
                        <img src="<?php echo htmlspecialchars($inventory_data[$i]['icon_url']); ?>" class="w-full h-full object-cover" data-item-id="<?php echo $inventory_data[$i]['id']; ?>">
                        <div class="tooltip"><?php echo htmlspecialchars($inventory_data[$i]['description']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <h3 class="text-lg font-bold">装备</h3>
        <div id="equipment" class="grid grid-cols-2 gap-1 mb-4">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="item-slot bg-gray-600 w-10 h-10 relative" draggable="true" data-type="equipment" data-slot="<?php echo $i; ?>">
                    <?php if ($equipment_data[$i]): ?>
                        <img src="<?php echo htmlspecialchars($equipment_data[$i]['icon_url']); ?>" class="w-full h-full object-cover" data-item-id="<?php echo $equipment_data[$i]['id']; ?>">
                        <div class="tooltip"><?php echo htmlspecialchars($equipment_data[$i]['description']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <h3 class="text-lg font-bold">合成</h3>
        <div id="synthesis" class="mb-4">
            <div class="flex gap-2 mb-2">
                <div class="item-slot bg-gray-600 w-10 h-10 relative" data-type="synth1">
                    <div class="tooltip">Drop Item 1</div>
                </div>
                <div class="item-slot bg-gray-600 w-10 h-10 relative" data-type="synth2">
                    <div class="tooltip">Drop Item 2</div>
                </div>
                <div id="synthResult" class="w-10 h-10 flex items-center justify-content-center">
                    <span class="text-red-500 text-2xl hidden">?</span>
                </div>
            </div>
            <button id="synthBtn" class="bg-blue-500 p-1 rounded w-full">Synthesize</button>
        </div>
    </div>
    <!-- Bottom Panel: Ground Items -->
    <div id="groundItemsPanel" class="ui-panel ground-items bg-gray-800 text-white p-2">
        <h3 class="text-lg font-bold">地上的物品</h3>
        <div id="groundItems" class="flex flex-wrap gap-2">
            <?php foreach ($ground_items as $item): ?>
                <div class="item-slot bg-gray-600 w-10 h-10 relative" data-item-id="<?php echo $item['id']; ?>">
                    <img src="<?php echo htmlspecialchars($item['icon_url']); ?>" class="w-full h-full object-cover">
                    <div class="tooltip"><?php echo htmlspecialchars($item['description']); ?></div>
                    <button class="interact-btn bg-blue-500 text-white text-xs p-1 rounded absolute bottom-0 right-0"
                            data-item-name="<?php echo htmlspecialchars($item['name']); ?>">
                        Use
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        console.log('Starting game setup...');
        window.onerror = function(msg, url, line, col, error) {
            console.error(`Error: ${msg} at ${url}:${line}:${col}`);
            showError(`Error: ${msg}`);
            return false;
        };
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }

        const gameId = <?php echo json_encode($game_id); ?>;
        const playerId = <?php echo json_encode($user_id); ?>;
        let playerX = <?php echo $player_x; ?>;
        let playerY = <?php echo $player_y; ?>;
        let currentLayer = <?php echo $current_layer; ?>;
        let health = <?php echo $health; ?>;
        let stamina = <?php echo $stamina; ?>;
        let actionCount = <?php echo $action_count; ?>;
        let moveCount = <?php echo $move_count; ?>;
        let lastMoveHour = <?php echo json_encode($last_move_hour); ?>;
        const tileData = <?php echo json_encode($tile_data); ?>;
        let groundItems = <?php echo json_encode($ground_items); ?>;
        const synthesisRecipes = <?php echo json_encode($synthesis_recipes); ?>;
        const tileSize = 1;
        const mapSize = 10;

        // Three.js setup
        console.log('Initializing Three.js scene...');
        const scene = new THREE.Scene();
        const camera = new THREE.OrthographicCamera(-mapSize/2, mapSize/2, mapSize/2, -mapSize/2, 0.1, 1000);
        camera.position.set(0, 0, 10);
        camera.lookAt(0, 0, 0);
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setClearColor(0x000000);
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        document.getElementById('gameCanvas').appendChild(renderer.domElement);
        console.log('Renderer initialized:', renderer.domElement);

        const gridHelper = new THREE.GridHelper(mapSize, mapSize, 0xffffff, 0x555555);
        scene.add(gridHelper);
        console.log('Grid helper added');

        const textureLoader = new THREE.TextureLoader();
        const tiles = [];
        for (let x = 0; x < mapSize; x++) {
            tiles[x] = [];
            for (let y = 0; y < mapSize; y++) {
                const tileInfo = tileData[x]?.[y] || { img_url: '', terrain_type: 'grass', passable: true };
                let material;
                if (tileInfo.img_url) {
                    try {
                        const texture = textureLoader.load(
                            tileInfo.img_url,
                            (tex) => {
                                console.log(`Loaded texture: ${tileInfo.img_url}`);
                                texture.repeat.set(1, 1);
                                texture.wrapS = texture.wrapT = THREE.ClampToEdgeWrapping;
                                texture.needsUpdate = true;
                            },
                            undefined,
                            () => {
                                console.warn(`Failed to load texture: ${tileInfo.img_url}`);
                                tiles[x][y].material = new THREE.MeshBasicMaterial({
                                    color: tileInfo.terrain_type === 'door' ? 0xff0000 : (tileInfo.terrain_type === 'safety_zone' ? 0x00ffff : 0x888888)
                                });
                            }
                        );
                        material = new THREE.MeshBasicMaterial({ map: texture });
                    } catch (e) {
                        console.error(`Texture error: ${tileInfo.img_url}`, e);
                        material = new THREE.MeshBasicMaterial({
                            color: tileInfo.terrain_type === 'door' ? 0xff0000 : (tileInfo.terrain_type === 'safety_zone' ? 0x00ffff : 0x888888)
                        });
                    }
                } else {
                    material = new THREE.MeshBasicMaterial({
                        color: tileInfo.terrain_type === 'door' ? 0xff0000 : (tileInfo.terrain_type === 'safety_zone' ? 0x00ffff : 0x888888)
                    });
                }
                const geometry = new THREE.PlaneGeometry(tileSize, tileSize);
                const tile = new THREE.Mesh(geometry, material);
                tile.position.set(x - mapSize/2 + 0.5, mapSize/2 - y - 0.5, 0);
                scene.add(tile);
                tiles[x][y] = tile;
            }
        }
        console.log('Tiles rendered:', tiles.length * tiles[0].length);

        let groundSprites = [];
        function updateGroundSprites(items) {
            groundSprites.forEach(sprite => scene.remove(sprite));
            groundSprites.length = 0;
            items.forEach(item => {
                const texture = textureLoader.load(
                    item.icon_url,
                    () => console.log(`Loaded ground item texture: ${item.icon_url}`),
                    undefined,
                    () => console.warn(`Failed to load ground item texture: ${item.icon_url}`)
                );
                const material = new THREE.SpriteMaterial({ map: texture });
                const sprite = new THREE.Sprite(material);
                sprite.position.set(playerX - mapSize/2 + 0.5, mapSize/2 - playerY - 0.5, 0.1);
                sprite.scale.set(0.5, 0.5, 1);
                scene.add(sprite);
                groundSprites.push(sprite);
            });
        }
        updateGroundSprites(groundItems);

        const playerGeometry = new THREE.BufferGeometry();
        const vertices = new Float32Array([0, 0.3, 0, -0.3, -0.3, 0, 0.3, -0.3, 0]);
        playerGeometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
        const playerMaterial = new THREE.MeshBasicMaterial({ color: 0x00ff00 });
        const player = new THREE.Mesh(playerGeometry, playerMaterial);
        player.position.set(playerX - mapSize/2 + 0.5, mapSize/2 - playerY - 0.5, 0.2);
        scene.add(player);
        console.log('Player added at:', playerX, playerY);

        function animate() {
            requestAnimationFrame(animate);
            TWEEN.update();
            renderer.render(scene, camera);
        }
        console.log('Starting animation loop...');
        animate();

        function updateCamera() {
            renderer.setSize(window.innerWidth, window.innerHeight);
            const aspect = window.innerWidth / window.innerHeight;
            if (aspect > 1) {
                camera.left = -mapSize / 2 * aspect;
                camera.right = mapSize / 2 * aspect;
                camera.top = mapSize / 2;
                camera.bottom = -mapSize / 2;
            } else {
                camera.left = -mapSize / 2;
                camera.right = mapSize / 2;
                camera.top = mapSize / 2 / aspect;
                camera.bottom = -mapSize / 2 / aspect;
            }
            camera.updateProjectionMatrix();
            console.log('Camera updated:', camera.left, camera.right, camera.top, camera.bottom);
        }
        window.addEventListener('resize', updateCamera);
        window.addEventListener('orientationchange', updateCamera);
        updateCamera();

        // Prevent scrolling/zoom
        window.addEventListener('scroll', (e) => {
            e.preventDefault();
            window.scrollTo(0, 0);
            console.log('Scroll prevented');
        }, { passive: false });
        document.addEventListener('gesturestart', (e) => e.preventDefault());
        document.addEventListener('gesturechange', (e) => e.preventDefault());
        document.addEventListener('gestureend', (e) => e.preventDefault());

        // UI toggles
        document.getElementById('toggleTopBar').addEventListener('click', () => {
            document.getElementById('topBar').classList.toggle('active');
        });
        document.getElementById('toggleSidebar').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('active');
        });
        document.getElementById('toggleGround').addEventListener('click', () => {
            document.getElementById('groundItemsPanel').classList.toggle('active');
        });

        // UI interactions
        document.getElementById('attributesBtn').addEventListener('click', () => {
            document.getElementById('attributesModal').classList.remove('hidden');
        });
        document.getElementById('closeAttributes').addEventListener('click', () => {
            document.getElementById('attributesModal').classList.add('hidden');
        });

        // Reset timer
        function updateResetTimer() {
            const now = new Date();
            const nextHour = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours() + 1);
            const diff = nextHour - now;
            const minutes = Math.floor(diff / 1000 / 60);
            const seconds = Math.floor((diff / 1000) % 60);
            document.getElementById('resetTimer').textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            setTimeout(updateResetTimer, 1000);
        }
        updateResetTimer();

        // Stamina and action count
        function updateStamina() {
            const now = new Date();
            const lastHour = lastMoveHour ? new Date(lastMoveHour) : now;
            if ((now - lastHour) / 1000 / 3600 >= 1) {
                if (actionCount < 20) {
                    stamina = Math.min(100, stamina + 30);
                }
                stamina = Math.max(0, stamina);
                actionCount = 0;
                moveCount = 0;
                lastMoveHour = now.toISOString();
                document.getElementById('stamina').textContent = stamina;
                document.getElementById('actionCount').textContent = actionCount;
                syncStamina();
            }
            setTimeout(updateStamina, 60 * 1000);
        }
        async function syncStamina() {
            try {
                const params = new URLSearchParams({
                    game_id: gameId,
                    user_id: playerId,
                    stamina: stamina,
                    action_count: actionCount,
                    move_count: moveCount,
                    last_move_hour: lastMoveHour
                });
                const response = await fetch('update_stamina.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const result = await response.json();
                if (result.status !== 'success') {
                    showError('Stamina sync failed: ' + result.message);
                }
            } catch (e) {
                showError('Stamina sync error: ' + e.message);
            }
        }
        updateStamina();

        async function movePlayer(dx, dy) {
            if (!player) {
                showError('Player not initialized!');
                return;
            }
            if (stamina <= 0) {
                showError('No stamina!');
                return;
            }
            const newX = playerX + dx;
            const newY = playerY + dy;
            if (newX < 0 || newX >= mapSize || newY < 0 || newY >= mapSize) {
                console.log('Out of bounds:', newX, newY);
                return;
            }
            if (tileData[newX]?.[newY]?.passable === false) {
                console.log('Impassable tile:', newX, newY);
                return;
            }
            if ((newX === 0 && newY === 0) || (newX === 0 && newY === 9) || (newX === 9 && newY === 0) || (newX === 9 && newY === 9)) {
                await handleDoor(newX, newY);
                return;
            }

            const staminaCost = actionCount > 100 ? 8 : (actionCount > 75 ? 4 : (actionCount > 50 ? 2 : 1));
            if (stamina < staminaCost) {
                showError('Not enough stamina!');
                return;
            }

            playerX = newX;
            playerY = newY;
            stamina -= staminaCost;
            actionCount++;
            moveCount++;
            document.getElementById('stamina').textContent = stamina;
            document.getElementById('actionCount').textContent = actionCount;

            new TWEEN.Tween(player.position)
                .to({ x: playerX - mapSize/2 + 0.5, y: mapSize/2 - playerY - 0.5 }, 200)
                .start();
            console.log('Player moved to:', playerX, playerY);

            try {
                const params = new URLSearchParams({
                    game_id: gameId,
                    user_id: playerId,
                    x: playerX,
                    y: playerY,
                    layer: currentLayer
                });
                const positionResponse = await fetch('update_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const positionText = await positionResponse.text();
                let positionResult;
                try {
                    positionResult = JSON.parse(positionText);
                } catch (e) {
                    console.error('Invalid JSON from update_position.php:', positionText);
                    showError('Position update failed: Invalid server response');
                    return;
                }
                if (positionResult.status !== 'success') {
                    showError('Position update failed: ' + positionResult.message);
                }

                const groundResponse = await fetch(`get_ground_items.php?game_id=${encodeURIComponent(gameId)}&layer=${encodeURIComponent(currentLayer)}&x=${playerX}&y=${playerY}`);
                const groundText = await groundResponse.text();
                let groundItemsNew;
                try {
                    groundItemsNew = JSON.parse(groundText);
                } catch (e) {
                    console.error('Invalid JSON from get_ground_items.php:', groundText);
                    showError('Ground items fetch failed: Invalid server response');
                    return;
                }
                groundItems = groundItemsNew;
                updateGroundItems(groundItems);
                syncStamina();

                if (health <= 0) {
                    await respawn();
                }
            } catch (e) {
                showError('Move error: ' + e.message);
            }
        }

        async function respawn() {
            playerX = 5;
            playerY = 5;
            currentLayer = 1;
            health = 100;
            stamina = 100;
            actionCount = 0;
            moveCount = 0;
            lastMoveHour = new Date().toISOString();
            document.getElementById('health').textContent = health;
            document.getElementById('stamina').textContent = stamina;
            document.getElementById('actionCount').textContent = actionCount;
            player.position.set(playerX - mapSize/2 + 0.5, mapSize/2 - playerY - 0.5, 0.2);
            try {
                const params = new URLSearchParams({
                    game_id: gameId,
                    user_id: playerId,
                    x: playerX,
                    y: playerY,
                    layer: currentLayer
                });
                const response = await fetch('update_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const result = await response.json();
                if (result.status !== 'success') {
                    showError('Respawn failed: ' + result.message);
                }
                syncStamina();
            } catch (e) {
                showError('Respawn error: ' + e.message);
            }
        }

        async function handleDoor(x, y) {
            console.log('Handling door at:', x, y);
            const doors = [{x:0,y:0}, {x:0,y:9}, {x:9,y:0}, {x:9,y:9}];
            const nextDoorIndex = Math.floor(Math.random() * 4);
            const isNextLayer = doors[nextDoorIndex].x === x && doors[nextDoorIndex].y === y;
            let newLayer = isNextLayer ? currentLayer + 1 : Math.max(1, currentLayer - Math.floor(Math.random() * 3) - 1);

            if (isNextLayer) {
                try {
                    const params = new URLSearchParams({ game_id: gameId, layer: newLayer });
                    const response = await fetch('create_layer.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: params.toString()
                    });
                    const result = await response.json();
                    if (result.status !== 'success') {
                        showError('Layer creation failed: ' + result.message);
                        return;
                    }
                } catch (e) {
                    showError('Layer creation error: ' + e.message);
                    return;
                }
            }

            playerX = 5;
            playerY = 5;
            currentLayer = newLayer;
            try {
                const params = new URLSearchParams({
                    game_id: gameId,
                    user_id: playerId,
                    x: playerX,
                    y: playerY,
                    layer: currentLayer
                });
                const response = await fetch('update_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const result = await response.json();
                if (result.status !== 'success') {
                    showError('Layer transition failed: ' + result.message);
                    return;
                }
            } catch (e) {
                showError('Layer transition error: ' + e.message);
                return;
            }
            window.location.href = `autorpg_index.php?game_id=${encodeURIComponent(gameId)}`;
        }

        // Keyboard controls
        document.addEventListener('keydown', (e) => {
            e.preventDefault();
            const key = e.key.toLowerCase();
            console.log('Key pressed:', key);
            switch (key) {
                case 'arrowup': case 'w': movePlayer(0, -1); break;
                case 'arrowdown': case 's': movePlayer(0, 1); break;
                case 'arrowleft': case 'a': movePlayer(-1, 0); break;
                case 'arrowright': case 'd': movePlayer(1, 0); break;
            }
        }, { passive: false });

        // Swipe controls
        let touchStartX = 0, touchStartY = 0;
        const canvas = document.getElementById('gameCanvas');
        canvas.addEventListener('touchstart', (e) => {
            if (e.target === canvas) {
                e.preventDefault();
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                console.log('Touch start:', touchStartX, touchStartY);
            }
        }, { passive: false });
        canvas.addEventListener('touchmove', (e) => {
            if (e.target === canvas) e.preventDefault();
            console.log('Touch move prevented');
        }, { passive: false });
        canvas.addEventListener('touchend', (e) => {
            if (e.target === canvas) {
                e.preventDefault();
                const touchEndX = e.changedTouches[0].clientX;
                const touchEndY = e.changedTouches[0].clientY;
                const dx = touchEndX - touchStartX;
                const dy = touchEndY - touchStartY;
                console.log('Touch end:', touchEndX, touchEndY, 'dx:', dx, 'dy:', dy);
                const threshold = 50;
                if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > threshold) {
                    movePlayer(dx > 0 ? 1 : -1, 0);
                } else if (Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > threshold) {
                    movePlayer(0, dy < 0 ? -1 : 1);
                }
            }
        }, { passive: false });

        // Drag-and-drop
        let draggedItem = null;
        document.querySelectorAll('.item-slot').forEach(slot => {
            slot.addEventListener('dragstart', (e) => {
                draggedItem = slot;
                slot.classList.add('dragging');
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    type: slot.dataset.type,
                    slot: slot.dataset.slot,
                    itemId: slot.querySelector('img')?.dataset.itemId
                }));
            });
            slot.addEventListener('dragend', () => {
                slot.classList.remove('dragging');
                draggedItem = null;
            });
            slot.addEventListener('dragover', (e) => {
                e.preventDefault();
                slot.classList.add('drop-target');
            });
            slot.addEventListener('dragleave', () => {
                slot.classList.remove('drop-target');
            });
            slot.addEventListener('drop', async (e) => {
                e.preventDefault();
                slot.classList.remove('drop-target');
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                try {
                    if (data.type === 'synth1' || data.type === 'synth2') {
                        slot.innerHTML = draggedItem.innerHTML;
                        slot.dataset.itemId = data.itemId;
                        checkSynthesis();
                    } else if (data.type === slot.dataset.type) {
                        const params = new URLSearchParams({
                            game_id: gameId,
                            user_id: playerId,
                            slot1: data.slot,
                            slot2: slot.dataset.slot
                        });
                        const response = await fetch('swap_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            window.location.reload(true);
                        } else {
                            showError('Swap failed: ' + result.message);
                        }
                    } else if (data.type === 'inventory' && slot.dataset.type === 'equipment') {
                        const params = new URLSearchParams({
                            game_id: gameId,
                            user_id: playerId,
                            slot: data.slot,
                            equip: 1
                        });
                        const response = await fetch('equip_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            window.location.reload(true);
                        } else {
                            showError('Equip failed: ' + result.message);
                        }
                    } else if (data.type === 'equipment' && slot.dataset.type === 'inventory') {
                        const params = new URLSearchParams({
                            game_id: gameId,
                            user_id: playerId,
                            slot: data.slot,
                            equip: 0
                        });
                        const response = await fetch('equip_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            window.location.reload(true);
                        } else {
                            showError('Unequip failed: ' + result.message);
                        }
                    } else {
                        showError('Invalid drop target');
                    }
                } catch (err) {
                    showError('Drag-and-drop error: ' + err.message);
                }
            });

            // Touch drag-and-drop
            slot.addEventListener('touchstart', (e) => {
                draggedItem = slot;
                slot.classList.add('dragging');
            }, { passive: true });
            slot.addEventListener('touchmove', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const target = document.elementFromPoint(touch.clientX, touch.clientY);
                document.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
                if (target && target.closest('.item-slot')) {
                    target.closest('.item-slot').classList.add('drop-target');
                }
            }, { passive: false });
            slot.addEventListener('touchend', async (e) => {
                slot.classList.remove('dragging');
                const target = document.querySelector('.drop-target');
                if (!target) {
                    draggedItem = null;
                    return;
                }
                target.classList.remove('drop-target');
                const data = {
                    type: slot.dataset.type,
                    slot: slot.dataset.slot,
                    itemId: slot.querySelector('img')?.dataset.itemId
                };
                try {
                    if (target.dataset.type === 'synth1' || target.dataset.type === 'synth2') {
                        target.innerHTML = slot.innerHTML;
                        target.dataset.itemId = data.itemId;
                        checkSynthesis();
                    } else if (data.type === target.dataset.type) {
                        const params = new URLSearchParams({
                            game_id: gameId,
                            user_id: playerId,
                            slot1: data.slot,
                            slot2: target.dataset.slot
                        });
                        const response = await fetch('swap_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            window.location.reload(true);
                        } else {
                            showError('Touch swap failed: ' + result.message);
                        }
                    } else if (data.type === 'inventory' && target.dataset.type === 'equipment') {
                        const params = new URLSearchParams({
                            game_id: gameId,
                            user_id: playerId,
                            slot: data.slot,
                            equip: 1
                        });
                        const response = await fetch('equip_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            window.location.reload(true);
                        } else {
                            showError('Touch equip failed: ' + result.message);
                        }
                    } else if (data.type === 'equipment' && target.dataset.type === 'inventory') {
                        const params = new URLSearchParams({
                            game_id: gameId,
                            user_id: playerId,
                            slot: data.slot,
                            equip: 0
                        });
                        const response = await fetch('equip_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            window.location.reload(true);
                        } else {
                            showError('Touch unequip failed: ' + result.message);
                        }
                    } else {
                        showError('Invalid touch drop target');
                    }
                } catch (e) {
                    showError('Touch drop error: ' + e.message);
                }
                draggedItem = null;
            }, { passive: true });
        });

        // Synthesis
        async function checkSynthesis() {
            const synth1 = document.querySelector('#synthesis [data-type="synth1"] img');
            const synth2 = document.querySelector('#synthesis [data-type="synth2"] img');
            const resultDiv = document.getElementById('synthResult');
            resultDiv.querySelector('span').classList.add('hidden');
            if (!synth1 || !synth2) {
                resultDiv.querySelector('span').classList.remove('hidden');
                return;
            }
            const item1Id = parseInt(synth1.dataset.itemId);
            const item2Id = parseInt(synth2.dataset.itemId);
            const recipe = synthesisRecipes.find(r => 
                (r.item1_id === item1Id && r.item2_id === item2Id) || 
                (r.item1_id === item2Id && r.item2_id === item1Id)
            );
            if (!recipe) {
                resultDiv.querySelector('span').classList.remove('hidden');
                return;
            }
            const params = new URLSearchParams({
                game_id: gameId,
                user_id: playerId,
                item1_id: item1Id,
                item2_id: item2Id,
                result_item_id: recipe.result_item_id
            });
            try {
                const response = await fetch('synthesize_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload(true);
                } else {
                    showError('Synthesis failed: ' + result.message);
                }
            } catch (e) {
                showError('Synthesis error: ' + e.message);
            }
        }
        document.getElementById('synthBtn').addEventListener('click', checkSynthesis);

        // Ground items
        function updateGroundItems(newItems) {
            const groundItemsDiv = document.getElementById('groundItems');
            groundItemsDiv.innerHTML = '';
            newItems.forEach(item => {
                const div = document.createElement('div');
                div.className = 'item-slot bg-gray-600 w-10 h-10 relative';
                div.dataset.itemId = item.id;
                div.innerHTML = `
                    <img src="${item.icon_url}" class="w-full h-full object-cover">
                    <div class="tooltip">${item.description}</div>
                    <button class="interact-btn bg-blue-500 text-white text-xs p-1 rounded absolute bottom-0 right-0" data-item-name="${item.name}">Use</button>
                `;
                groundItemsDiv.appendChild(div);
                div.querySelector('.interact-btn').addEventListener('click', async () => {
                    if (item.name === 'Sleeping Bag') {
                        stamina = Math.min(100, stamina + 50);
                        document.getElementById('stamina').textContent = stamina;
                        syncStamina();
                    } else if (item.name === 'Campfire') {
                        try {
                            const params = new URLSearchParams({
                                game_id: gameId,
                                layer: currentLayer,
                                x: playerX,
                                y: playerY,
                                item_id: item.id,
                                action: 'cook'
                            });
                            const response = await fetch('use_ground_item.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: params.toString()
                            });
                            const result = await response.json();
                            if (result.status === 'success') {
                                window.location.reload(true);
                            } else {
                                showError('Campfire use failed: ' + result.message);
                            }
                            } catch (e) {
                                showError('Campfire error: ' + e.message);
                            }
                        }
                    });
                });
            updateGroundSprites(newItems);
        }

        // Debug
        console.log('Scene objects:', scene.children.length);
        console.log('Tile data:', tileData);
        console.log('Viewport size:', window.innerWidth, window.innerHeight);
    </script>
</body>
</html>