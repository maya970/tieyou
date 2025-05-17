<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

try {
 // Fetch user points
 $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user_points = $stmt->fetchColumn() ?: 0;

 // Fetch games
 $stmt = $pdo->query("
 SELECT g.*, u.username AS creator, COUNT(gp.user_id) AS player_count
 FROM games g
 JOIN users u ON g.creator_id = u.id
 LEFT JOIN game_players gp ON g.id = gp.game_id
 GROUP BY g.id
 ");
 $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $game_id = $_POST['game_id'];
 $user_id = $_SESSION['user_id'];

 $stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
 $stmt->execute([$game_id, $user_id]);
 if (!$stmt->fetch()) {
 $stmt = $pdo->prepare("INSERT INTO game_players (game_id, user_id) VALUES (?, ?)");
 $stmt->execute([$game_id, $user_id]);
 }

 header('Location: index.php?game_id=' . $game_id);
 exit;
 }
} catch (PDOException $e) {
 error_log("Database error in lobby.php: " . $e->getMessage());
 $error = 'Database error occurred: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Lobby</title>
 <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-4">
 <div class="container mx-auto">
 <h1 class="text-2xl font-bold mb-4">Game Lobby</h1>
 <?php if (isset($error)): ?>
 <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
 <?php endif; ?>
 <div class="mb-4 bg-white p-4 rounded-lg shadow">
 <p class="text-lg font-semibold">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
 <p>Your Points: <?php echo (int)$user_points; ?></p>
 </div>
 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
 <?php foreach ($games as $game): ?>
 <div class="bg-white p-4 rounded-lg shadow">
 <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($game['name']); ?></h2>
 <p>Created by: <?php echo htmlspecialchars($game['creator']); ?></p>
 <p>Players: <?php echo $game['player_count']; ?></p>
 <form method="POST" class="mt-2">
 <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
 <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Join</button>
 </form>
 </div>
 <?php endforeach; ?>
 </div>
 <div class="mt-4 flex gap-4">
 <?php if ($_SESSION['role'] === 'game_admin'): ?>
 <a href="create_game.php" class="bg-green-500 text-white p-2 rounded hover:bg-green-600">Create New Game</a>
 <?php endif; ?>
 <?php if ($_SESSION['role'] === 'super_admin'): ?>
 <a href="super_admin.php" class="bg-purple-500 text-white p-2 rounded hover:bg-purple-600">Super Admin Panel</a>
 <?php endif; ?>
 <a href="logout.php" class="bg-red-500 text-white p-2 rounded hover:bg-red-600">Logout</a>
 </div>
 </div>
</body>
</html>
