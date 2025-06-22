<?php
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'check_auth.php';
require 'db.php';

try {

    $stmt = $pdo->query("
        SELECT t.*, u.username AS creator,
            CASE t.rating
                WHEN 'EX' THEN 9
                WHEN 'SSS' THEN 8
                WHEN 'S' THEN 7
                WHEN 'A' THEN 6
                WHEN 'B' THEN 5
                WHEN 'C' THEN 4
                WHEN 'D' THEN 3
                WHEN 'E' THEN 2
                WHEN 'F' THEN 1
            END AS rating_score,
            ((t.latest_price - t.opening_price) / t.opening_price * 100) AS change_percentage
        FROM tasks t
        JOIN users u ON t.creator_id = u.id
        WHERE t.task_state IN ('Open', 'Completed', 'Perpetual')
        ORDER BY (t.latest_price / CASE t.rating
            WHEN 'EX' THEN 9
            WHEN 'SSS' THEN 8
            WHEN 'S' THEN 7
            WHEN 'A' THEN 6
            WHEN 'B' THEN 5
            WHEN 'C' THEN 4
            WHEN 'D' THEN 3
            WHEN 'E' THEN 2
            WHEN 'F' THEN 1
        END) ASC
    ");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tasks);
} catch (PDOException $e) {
    error_log("Database error in tasks.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>