<?php
require 'check_auth.php';
require 'db.php';

$npc_id = $_GET['npc_id'] ?? null;

try {
    $stmt = $pdo->prepare("SELECT d.id, d.text, n.image_url AS npc_image_url
                           FROM autorpg_dialogues d
                           JOIN autorpg_npcs n ON d.npc_id = n.id
                           WHERE d.id = ?");
    $stmt->execute([$npc_id]);
    $dialogue = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT id, option_text, next_dialogue_id, action_type, action_data
                           FROM autorpg_dialogue_options WHERE dialogue_id = ?");
    $stmt->execute([$npc_id]);
    $dialogue['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($dialogue);
} catch (PDOException $e) {
    error_log("Database error in get_dialogue.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>