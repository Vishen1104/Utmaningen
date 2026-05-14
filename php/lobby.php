<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Inte inloggad']));
}

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'list_rooms') {
    $stmt = $pdo->query("
        SELECT r.*, COUNT(p.id) as player_count
        FROM rooms r
        LEFT JOIN players_in_room p ON r.id = p.room_id
        WHERE r.status IN ('waiting','playing')
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    echo json_encode(['success' => true, 'rooms' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

} elseif ($action === 'create_room') {
    $name = trim($_POST['name'] ?? '') ?: 'Rum ' . rand(100, 999);
    $stmt = $pdo->prepare("INSERT INTO rooms (name) VALUES (?)");
    $stmt->execute([$name]);
    $room_id = $pdo->lastInsertId();
    joinRoom($pdo, $room_id, $user_id);
    echo json_encode(['success' => true, 'room_id' => $room_id]);

} elseif ($action === 'join_room') {
    $room_id = intval($_POST['room_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=? AND status IN ('waiting','playing')");
    $stmt->execute([$room_id]);
    if (!$stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'Rummet finns inte']));
    }
    $stmt = $pdo->prepare("SELECT id FROM players_in_room WHERE room_id=? AND user_id=?");
    $stmt->execute([$room_id, $user_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM players_in_room WHERE room_id=?");
        $stmt->execute([$room_id]);
        if ($stmt->fetchColumn() >= 6) {
            die(json_encode(['success' => false, 'message' => 'Rummet är fullt']));
        }
        joinRoom($pdo, $room_id, $user_id);
    }
    echo json_encode(['success' => true, 'room_id' => $room_id]);
}

function joinRoom($pdo, $room_id, $user_id) {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(seat), -1) FROM players_in_room WHERE room_id=?");
    $stmt->execute([$room_id]);
    $seat = $stmt->fetchColumn() + 1;
    $stmt = $pdo->prepare("SELECT chips FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $chips = $stmt->fetchColumn() ?: 1000;
    $stmt = $pdo->prepare("INSERT INTO players_in_room (room_id, user_id, seat, chips) VALUES (?,?,?,?)");
    $stmt->execute([$room_id, $user_id, $seat, $chips]);
}