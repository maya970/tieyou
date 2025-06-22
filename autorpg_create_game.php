<?php
require 'check_auth.php';
require 'db.php';

if ($_SESSION['role'] !== 'super_admin') {
    header('Location: autorpg_games.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_name = $_POST['game_name'] ?? '';
    if (empty($game_name)) {
        $error = 'Game name is required.';
    } else {
        try {
            $pdo->beginTransaction();
            // Insert game
            $stmt = $pdo->prepare("INSERT INTO autorpg_games (name, creator_id) VALUES (?, ?)");
            $stmt->execute([$game_name, $_SESSION['user_id']]);
            $game_id = $pdo->lastInsertId();

            // Insert layer
            $stmt = $pdo->prepare("INSERT INTO autorpg_layers (game_id, layer) VALUES (?, 1)");
            $stmt->execute([$game_id]);

            // Insert 10x10 tiles for layer 1
            $stmt = $pdo->prepare("INSERT INTO autorpg_map_tiles (game_id, layer, x, y, img_url, terrain_type, passable) VALUES (?, 1, ?, ?, ?, ?, ?)");
            for ($x = 0; $x < 10; $x++) {
                for ($y = 0; $y < 10; $y++) {
                    $terrain = in_array([$x, $y], [[0,0], [0,9], [9,0], [9,9]]) ? 'door' : 'grass';
                    $img_url = "/assets/tiles/$terrain.png";
                    $passable = 1; // Doors and grass are passable
                    $stmt->execute([$game_id, $x, $y, $img_url, $terrain, $passable]);
                }
            }
            $pdo->commit();
            header('Location: autorpg_games.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error creating game: " . $e->getMessage());
            $error = 'Failed to create game: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create AutoRPG Game</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">Create AutoRPG Game</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" class="bg-white p-4 rounded-lg shadow">
            <div class="mb-4">
                <label for="game_name" class="block text-sm font-medium text-gray-700">Game Name</label>
                <input type="text" name="game_name" id="game_name" class="mt-1 p-2 w-full border rounded" required>
            </div>
            <button type="submit" class="bg-green-600 text-white p-2 rounded hover:bg-green-700">Create Game</button>
            <a href="autorpg_games.php" class="ml-4 text-blue-500 hover:underline">Back to Games</a>
        </form>
    </div>
</body>
</html>