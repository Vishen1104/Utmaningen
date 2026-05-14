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
        <h1>♠ Poker ♦</h1>
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

<script>
async function hamta_rum() {
    const res  = await fetch('php/lobby.php?action=list_rooms');
    const data = await res.json();
    const el   = document.getElementById('rum-lista');
    if (!data.rooms?.length) {
        el.innerHTML = '<p class="tomt">Inga rum just nu – skapa ett!</p>';
        return;
    }
    el.innerHTML = data.rooms.map(r => `
        <div class="rum-rad">
            <div>
                <strong>${r.name}</strong>
                <span class="badge ${r.status === 'playing' ? 'badge-gron' : 'badge-gul'}">${r.status === 'playing' ? 'Pågår' : 'Väntar'}</span>
            </div>
            <div class="rum-rad-hoger">
                <span>👥 ${r.player_count}/6</span>
                <button onclick="ga_med(${r.id})">Gå med</button>
            </div>
        </div>
    `).join('');
}

async function skapa_rum() {
    const namn = document.getElementById('rum-namn').value;
    const res  = await fetch('php/lobby.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=create_room&name=${encodeURIComponent(namn)}`
    });
    const data = await res.json();
    if (data.success) window.location.href = `Game.php?room=${data.room_id}`;
}

async function ga_med(room_id) {
    const res  = await fetch('php/lobby.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=join_room&room_id=${room_id}`
    });
    const data = await res.json();
    if (data.success) window.location.href = `Game.php?room=${data.room_id}`;
    else alert(data.message);
}

async function logga_ut() {
    await fetch('php/auth.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=logout'
    });
    window.location.href = 'Index.php';
}

hamta_rum();
setInterval(hamta_rum, 5000);
</script>
</body>
</html>