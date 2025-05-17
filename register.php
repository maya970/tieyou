<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } elseif (strlen($username) < 3 || strlen($password) < 6) {
        $error = "Username must be at least 3 characters, and password must be at least 6 characters.";
    } elseif ($role !== 'player' && $role !== 'game_admin') {
        $error = "Invalid role selected.";
    } else {
        // Check if username exists
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already taken.";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, points) VALUES (?, ?, ?, 0)");
                $stmt->execute([$username, $hashed_password, $role]);
                header('Location: login.php?success=Registration successful! Please log in.');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration database error: " . $e->getMessage());
            $error = "Registration failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="/assets/css/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-4 text-center">Register</h1>
        <?php if (isset($error)): ?>
            <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-green-500 mb-4 text-center"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required class="w-full p-2 mb-4 border rounded">
            <input type="password" name="password" placeholder="Password" required class="w-full p-2 mb-4 border rounded">
            <select name="role" required class="w-full p-2 mb-4 border rounded">
                <option value="player">Player</option>
                <option value="game_admin">Game Admin</option>
            </select>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Register</button>
        </form>
        <p class="mt-4 text-center">
            Already have an account? <a href="login.php" class="text-blue-500 hover:underline">Log in</a>
        </p>
    </div>
</body>
</html>
