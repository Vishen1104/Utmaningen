<?php
$host = '127.0.0.1';
$dbname = 'if0_41899622_if0_41899622_poker';
$user = 'if0_41899622';
$pass = 'Vishen1104';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
}
?>