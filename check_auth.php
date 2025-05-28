<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
 session_start();
}

if (!isset($_SESSION['user_id'])) {
 header('Location: login.php');
 exit;
}
?>
