console.log('Game script loaded');

window.onerror = function(msg, url, line, col, error) {
    console.error(`Error: ${msg} at ${url}:${line}:${col}`);
    showError(`Error at line ${line}: ${msg}`);
    return false;
};

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => errorDiv.style.display = 'none', 5000);
}

// Game state
const gameId = '<?php echo json_encode($game_id); ?>';
const playerId = '<?php echo json_encode($user_id); ?>';
let playerX = <?php echo json_encode($player_x); ?>;
let playerY = <?php echo json_encode($player_y); ?>;
let currentLayer = <?php echo json_encode($current_layer); ?>;
let health = <?php echo json_encode($health); ?>;
let stamina = <?php echo json_encode($stamina); ?>;
let actionCount = <?php echo json_encode($action_count); ?>;
let moveCount = <?php echo json_encode($move_count); ?>;
let lastMoveHour = '<?php echo json_encode($last_move_hour); ?>';
const tileData = <?php echo json_encode($tile_data); ?>;
let groundItems = <?php echo json_encode($ground_items); ?>;
const synthesisRecipes = <?php echo json_encode($synthesis_recipes); ?>;
const tileSize = 1;
const mapSize = 10;

// Three.js setup
let scene, camera, renderer, player, groundSprites = [];
function initThreeJs() {
    console.log('Initializing Three.js');
    if (!THREE) {
        showError('Three.js not loaded');
        return;
    }
    const canvas = document.createElement('canvas');
    if (!canvas.getContext('webgl') && !canvas.getContext('experimental-webgl')) {
        showError('WebGL not supported');
        return;
    }

    scene = new THREE.Scene();
    camera = new THREE.OrthographicCamera(-mapSize/2, mapSize/2, mapSize/2, -mapSize/2, 0.1, 1000);
    camera.position.set(0, 0, 10);
    camera.lookAt(0, 0, 0);

    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setClearColor(0x000000);
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    document.getElementById('gameCanvas').appendChild(renderer.domElement);
    console.log('Renderer initialized');

    const gridHelper = new THREE.GridHelper(mapSize, mapSize, 0xffffff, 0x555555);
    scene.add(gridHelper);

    const textureLoader = new THREE.TextureLoader();
    const tiles = [];
    for (let x = 0; x < mapSize; x++) {
        tiles[x] = [];
        for (let y = 0; y < mapSize; y++) {
            const tileInfo = tileData[x]?.[y] || { img_url: '', terrain_type: 'grass', passable: true };
            let material;
            if (tileInfo.img_url) {
                try {
                    const texture = textureLoader.load(tileInfo.img_url, undefined, undefined, () => {
                        console.warn(`Texture failed: ${tileInfo.img_url}`);
                        tiles[x][y].material = new THREE.MeshBasicMaterial({
                            color: tileInfo.terrain_type === 'door' ? 0xff0000 :
                                   tileInfo.terrain_type === 'safety_zone' ? 0x00ffff : 0x888888
                        });
                    });
                    material = new THREE.MeshBasicMaterial({ map: texture });
                } catch (e) {
                    console.error(`Texture error: ${tileInfo.img_url}`, e);
                    material = new THREE.MeshBasicMaterial({
                        color: tileInfo.terrain_type === 'door' ? 0xff0000 :
                               tileInfo.terrain_type === 'safety_zone' ? 0x00ffff : 0x888888
                    });
                }
            } else {
                material = new THREE.MeshBasicMaterial({
                    color: tileInfo.terrain_type === 'door' ? 0xff0000 :
                           tileInfo.terrain_type === 'safety_zone' ? 0x00ffff : 0x888888
                });
            }
            const geometry = new THREE.PlaneGeometry(tileSize, tileSize);
            const tile = new THREE.Mesh(geometry, material);
            tile.position.set(x - mapSize/2 + 0.5, mapSize/2 - y - 0.5, 0);
            scene.add(tile);
            tiles[x][y] = tile;
        }
    }

    updateGroundSprites(groundItems);

    const playerGeometry = new THREE.BufferGeometry();
    const vertices = new Float32Array([0, 0.3, 0, -0.3, -0.3, 0, 0.3, -0.3, 0]);
    playerGeometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
    const playerMaterial = new THREE.MeshBasicMaterial({ color: 0x00ff00 });
    player = new THREE.Mesh(playerGeometry, playerMaterial);
    player.position.set(playerX - mapSize/2 + 0.5, mapSize/2 - playerY - 0.5, 0.2);
    scene.add(player);

    function animate() {
        requestAnimationFrame(animate);
        TWEEN.update();
        renderer.render(scene, camera);
    }
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
    }
    window.addEventListener('resize', updateCamera);
    window.addEventListener('orientationchange', updateCamera);
    updateCamera();
}

function updateGroundSprites(items) {
    groundSprites.forEach(sprite => scene.remove(sprite));
    groundSprites.length = 0;
    const textureLoader = new THREE.TextureLoader();
    items.forEach(item => {
        const texture = textureLoader.load(item.icon_url, undefined, undefined, () => console.warn(`Ground item texture failed: ${item.icon_url}`));
        const material = new THREE.SpriteMaterial({ map: texture });
        const sprite = new THREE.Sprite(material);
        sprite.position.set(playerX - mapSize/2 + 0.5, mapSize/2 - playerY - 0.5, 0.1);
        sprite.scale.set(0.5, 0.5, 1);
        scene.add(sprite);
        groundSprites.push(sprite);
    });
}

// Prevent scrolling/zoom
window.addEventListener('scroll', (e) => {
    e.preventDefault();
    window.scrollTo(0, 0);
}, { passive: false });
document.addEventListener('gesturestart', (e) => e.preventDefault());
document.addEventListener('gesturechange', (e) => e.preventDefault());
document.addEventListener('gestureend', (e) => e.preventDefault());

// UI interactions
document.getElementById('uiToggle').addEventListener('click', () => {
    document.getElementById('uiPanel').classList.toggle('open');
});
document.getElementById('closeUi').addEventListener('click', () => {
    document.getElementById('uiPanel').classList.remove('open');
});
document.getElementById('attributesBtn').addEventListener('click', () => {
    document.getElementById('uiPanel').classList.remove('open');
    document.getElementById('attributesModal').classList.add('open');
});
document.getElementById('closeAttributes').addEventListener('click', () => {
    document.getElementById('attributesModal').classList.remove('open');
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
    const params = new URLSearchParams({
        game_id: gameId,
        user_id: playerId,
        stamina,
        action_count: actionCount,
        move_count: moveCount,
        last_move_hour: lastMoveHour
    });
    try {
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
        return;
    }
    if (!tileData[newX] || tileData[newX][newY]?.passable === false) {
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

    const params = new URLSearchParams({
        game_id: gameId,
        user_id: playerId,
        x: playerX,
        y: playerY,
        layer: currentLayer
    });
    try {
        const response = await fetch('update_position.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        const result = await response.json();
        if (result.status !== 'success') {
            showError('Position sync failed: ' + result.message);
        }
        syncStamina();
        const groundResponse = await fetch(`get_ground_items.php?game_id=${encodeURIComponent(gameId)}&layer=${encodeURIComponent(currentLayer)}&x=${playerX}&y=${playerY}`);
        groundItems = await groundResponse.json();
        updateGroundItems(groundItems);
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
    const params = new URLSearchParams({
        game_id: gameId,
        user_id: playerId,
        x: playerX,
        y: playerY,
        layer: currentLayer
    });
    try {
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
    const doors = [{x:0,y:0}, {x:0,y:9}, {x:9,y:0}, {x:9,y:9}];
    const nextDoorIndex = Math.floor(Math.random() * 4);
    const isNextLayer = doors[nextDoorIndex].x === x && doors[nextDoorIndex].y === y;
    let newLayer = isNextLayer ? currentLayer + 1 : Math.max(1, currentLayer - Math.floor(Math.random() * 3) - 1);

    if (isNextLayer) {
        const params = new URLSearchParams({ game_id: gameId, layer: newLayer });
        try {
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
    const params = new URLSearchParams({
        game_id: gameId,
        user_id: playerId,
        x: playerX,
        y: playerY,
        layer: currentLayer
    });
    try {
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

// Controls
document.addEventListener('keydown', (e) => {
    e.preventDefault();
    switch (e.key.toLowerCase()) {
        case 'arrowup': case 'w': movePlayer(0, -1); break;
        case 'arrowdown': case 's': movePlayer(0, 1); break;
        case 'arrowleft': case 'a': movePlayer(-1, 0); break;
        case 'arrowright': case 'd': movePlayer(1, 0); break;
    }
}, { passive: false });

let touchStartX = 0, touchStartY = 0;
const canvas = document.getElementById('gameCanvas');
canvas.addEventListener('touchstart', (e) => {
    if (e.target === canvas) {
        e.preventDefault();
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    }
}, { passive: false });
canvas.addEventListener('touchmove', (e) => {
    if (e.target === canvas) e.preventDefault();
}, { passive: false });
canvas.addEventListener('touchend', (e) => {
    if (e.target === canvas) {
        e.preventDefault();
        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;
        const dx = touchEndX - touchStartX;
        const dy = touchEndY - touchStartY;
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
function updateGroundItems(items) {
    const groundItemsDiv = document.getElementById('groundItems');
    groundItemsDiv.innerHTML = '';
    items.forEach(item => {
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
                const params = new URLSearchParams({
                    game_id: gameId,
                    layer: currentLayer,
                    x: playerX,
                    y: playerY,
                    item_id: item.id,
                    action: 'cook'
                });
                try {
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
    updateGroundSprites(items);
}

// Initialize
initThreeJs();