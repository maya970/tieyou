<?php
require 'check_auth.php';
require 'db.php';

$game_id = $_POST['game_id'] ?? null;
$combat_id = $_POST['combat_id'] ?? null;
$action = $_POST['action'] ?? null;
$speed = $_POST['speed'] ?? 10;
$distance = $_POST['distance'] ?? 16;

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT attacker_id, attacker_monster_id, defender_id, distance FROM autorpg_combats WHERE id = ? AND game_id = ?");
    $stmt->execute([$combat_id, $game_id]);
    $combat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$combat) {
        echo json_encode(['status' => 'error', 'message' => 'Combat not found']);
        exit;
    }

    $result = ['status' => 'success'];
    if ($action === 'attack') {
        $stmt = $pdo->prepare("SELECT COALESCE(i.attack_range, s.attack_range, 0) AS attack_range
                               FROM autorpg_player_inventory pi
                               LEFT JOIN autorpg_items i ON pi.item_id = i.id
                               LEFT JOIN autorpg_player_skills ps ON ps.user_id = pi.user_id AND ps.game_id = pi.game_id
                               LEFT JOIN autorpg_skills s ON ps.skill_id = s.id
                               WHERE pi.user_id = ? AND pi.game_id = ? AND (pi.is_equipped = 1 OR ps.slot IS NOT NULL)
                               LIMIT 1");
        $stmt->execute([$combat['defender_id'], $game_id]);
        $attack_range = $stmt->fetchColumn() ?? 0;

        if ($combat['distance'] > $attack_range) {
            echo json_encode(['status' => 'error', 'message' => 'Target out of range']);
            exit;
        }

        $damage = 20;
        if ($combat['attacker_monster_id']) {
            $stmt = $pdo->prepare("UPDATE autorpg_players SET health = GREATEST(0, health - ?) WHERE user_id = ? AND game_id = ?");
            $stmt->execute([$damage, $combat['defender_id'], $game_id]);
            $stmt = $pdo->prepare("SELECT health FROM autorpg_players WHERE user_id = ? AND game_id = ?");
            $stmt->execute([$combat['defender_id'], $game_id]);
            $result['defender_health'] = $stmt->fetchColumn();
            if ($result['defender_health'] <= 0) {
                $stmt = $pdo->prepare("DELETE FROM autorpg_combats WHERE id = ?");
                $stmt->execute([$combat_id]);
                $result['winner'] = 'monster';
            }
        } else {
            $result['defender_health'] = 100 - $damage;
        }
    } else if ($action === 'advance') {
        $new_distance = max(0, $combat['distance'] - Math.floor($speed / 2));
        $stmt = $pdo->prepare("UPDATE autorpg_combats SET distance = ? WHERE id = ?");
        $stmt->execute([$new_distance, $combat_id]);
        $result['distance'] = $new_distance;
    } else if ($action === 'flee') {
        $flee_chance = min(1, $distance / 16);
        if (mt_rand(0, 100) / 100 < $flee_chance) {
            $stmt = $pdo->prepare("DELETE FROM autorpg_combats WHERE id = ?");
            $stmt->execute([$combat_id]);
            $result['fled'] = true;
        } else {
            $result['fled'] = false;
        }
    }

    if ($result['winner'] === 'player' && $combat['attacker_monster_id']) {
        $stmt = $pdo->prepare("SELECT item_id, drop_chance FROM autorpg_monster_drops WHERE monster_id = ?");
        $stmt->execute([$combat['attacker_monster_id']]);
        $drops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['drops'] = [];
        foreach ($drops as $drop) {
            if (mt_rand(0, 100) / 100 < $drop['drop_chance']) {
                $stmt = $pdo->prepare("INSERT INTO autorpg_player_inventory (user_id, game_id, item_id, slot, is_equipped) 
                                       VALUES (?, ?, ?, (SELECT COALESCE(MIN(slot), 0) FROM autorpg_player_inventory WHERE game_id = ? AND user_id = ? AND is_equipped = 0), 0)");
                $stmt->execute([$combat['defender_id'], $game_id, $drop['item_id'], $game_id, $combat['defender_id']]);
                $result['drops'][] = $drop['item_id'];
            }
        }
    }

    $pdo->commit();
    echo json_encode($result);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in combat_action.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>