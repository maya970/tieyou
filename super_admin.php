<?php
require_once 'check_auth.php';
require_once 'db.php'; // Include database connection

// Restrict to super_admin
if ($_SESSION['role'] !== 'super_admin') {
    header('Location: lobby.php?error=Unauthorized.');
    exit;
}

// Fetch all users
$stmt = $pdo->prepare("SELECT id, username, points FROM users ORDER BY username");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle points update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_points'])) {
    try {
        $user_id = $_POST['user_id'];
        $points = (int)$_POST['points'];
        $stmt = $pdo->prepare("UPDATE users SET points = ? WHERE id = ?");
        $stmt->execute([$points, $user_id]);
        header('Location: super_admin.php?success=Points updated.');
        exit;
    } catch (Exception $e) {
        error_log("Points update failed: " . $e->getMessage());
        header('Location: super_admin.php?error=Failed to update points.');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Panel</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
    <script src="/assets/js/vue.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">Super Admin Panel</h1>
        <?php if (isset($_GET['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <h2 class="text-lg font-semibold mb-2">Manage User Points</h2>
        <div id="usersApp" class="mb-8">
            <div v-for="user in users" :key="user.id" class="bg-white p-4 rounded-lg shadow mb-4">
                <form method="POST">
                    <input type="hidden" name="update_points" value="1">
                    <input type="hidden" name="user_id" :value="user.id">
                    <p><strong>Username:</strong> {{ user.username }}</p>
                    <input type="number" name="points" v-model.number="user.points" required class="w-full p-2 mb-2 border rounded">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Update Points</button>
                </form>
            </div>
        </div>

        <a href="lobby.php" class="text-blue-500 hover:underline">Back to Lobby</a>
    </div>

    <script>
        try {
            const usersApp = Vue.createApp({
                data() {
                    return {
                        users: <?php echo json_encode($users, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS); ?>
                    };
                }
            });
            usersApp.mount('#usersApp');
        } catch (error) {
            console.error('Failed to initialize Vue for usersApp:', error);
        }
    </script>
</body>
</html>