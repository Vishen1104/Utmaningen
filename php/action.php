<?php
session_start();
require_once 'db.php';
require_once 'game_logic.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Inte inloggad']));
}

$room_id = intval($_POST['room_id'] ?? 0);
$action  = $_POST['action'] ?? '';
$amount  = intval($_POST['amount'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($action === 'start_game') {
    $stmt = $pdo->prepare("SELECT * FROM players_in_room WHERE room_id = ? ORDER BY seat");
    $stmt->execute([$room_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($players) < 2) {
        die(json_encode(['success' => false, 'message' => 'Minst 2 spelare krävs']));
    }
    startGame($pdo, $room_id, $players);
    echo json_encode(['success' => true]);

} elseif (in_array($action, ['fold', 'call', 'raise', 'check'])) {
    $result = handleAction($pdo, $room_id, $user_id, $action, $amount);
    echo json_encode($result);

} elseif ($action === 'new_hand') {
    $stmt = $pdo->prepare("DELETE FROM players_in_room WHERE room_id = ? AND chips <= 0");
    $stmt->execute([$room_id]);

    $stmt = $pdo->prepare("UPDATE players_in_room SET status='active', hand=NULL, current_bet=0, total_bet=0 WHERE room_id=?");
    $stmt->execute([$room_id]);

    $stmt = $pdo->prepare("UPDATE rooms SET status='playing' WHERE id=?");
    $stmt->execute([$room_id]);

    $stmt = $pdo->prepare("SELECT * FROM players_in_room WHERE room_id=? ORDER BY seat");
    $stmt->execute([$room_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($players) < 2) {
        die(json_encode(['success' => false, 'message' => 'Inte tillräckligt med spelare']));
    }
    startGame($pdo, $room_id, $players);
    echo json_encode(['success' => true]);
}
?>