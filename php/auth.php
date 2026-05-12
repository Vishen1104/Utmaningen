<?php
session_start();
require_once 'db.php';

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        die(json_encode(['success' => false, 'message' => 'Fyll i alla fält']));
    }

    // Kolla om användarnamnet redan finns
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'Användarnamnet är redan taget']));
    }

    // Skapa användaren
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashed]);

    echo json_encode(['success' => true, 'message' => 'Konto skapat!']);
}

elseif ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['chips'] = $user['chips'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fel användarnamn eller lösenord']);
    }
}

elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
}
?>