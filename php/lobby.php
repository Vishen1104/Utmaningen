<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: Index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Poker – Lobby</title>
    <link rel="stylesheet" href="Css/Style.css">
</head>
<body>
<div class="lobby-wrapper">
    <div class="lobby-header">
        <h1>🃏 Poker</h1>
        <div class="user-info">
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <button onclick="logga_ut()" class="btn-small">Logga ut</button>
        </div>
    </div>

    <div class="lobby-main">
        <div class="skapa-kort">
            <h2>Skapa rum</h2>
            <input type="text" id="rum-namn" placeholder="Rummets namn (valfritt)">
            <button onclick="skapa_rum()">+ Skapa rum</button>
        </div>

        <div class="rum-lista-kort">
            <div class="rum-lista-rubrik">
                <h2>Öppna rum</h2>
                <button onclick="hamta_rum()" class="btn-small">↻</button>
            </div>
            <div id="rum-lista">Laddar sida...</div>
        </div>
    </div>
</div>

