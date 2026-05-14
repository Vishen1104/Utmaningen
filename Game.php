<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: Index.php'); exit; }
$room_id = intval($_GET['room'] ?? 0);
if (!$room_id) { header('Location: Lobby.php'); exit; }
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Poker – Spel</title>
    <link rel="stylesheet" href="Css/Style.css">
</head>
<body>
<div class="spel-wrapper">

    <div class="spel-topp">
        <a href="Lobby.php" class="btn-small">← Lobby</a>
        <span id="round-text">Väntar på spelare...</span>
        <span id="pot-text">Pott: 0 kr</span>
    </div>

    <div class="bord">
        <div class="motstandare" id="motstandare"></div>
        <div class="community-omrade">
            <div id="community-kort" class="community-kort"></div>
            <div id="vinnare-text" class="vinnare-text" style="display:none;"></div>
        </div>
        <div class="mina-kort" id="mina-kort"></div>
    </div>

    <div class="atgarder" id="atgarder" style="display:none;">
        <button class="btn-fold"  onclick="action('fold')">Fold</button>
        <button class="btn-check" id="btn-check" onclick="action('check')">Check</button>
        <button class="btn-call"  id="btn-call"  onclick="action('call')">Call</button>
        <div class="raise-omrade">
            <input type="number" id="raise-belopp" value="40" min="20" step="20">
            <button class="btn-raise" onclick="action('raise')">Raise</button>
        </div>
    </div>

    <div id="starta-omrade" style="display:none; text-align:center; margin-top:16px;">
        <button onclick="starta_spel()" class="btn-starta">▶ Starta spel</button>
    </div>

    <div id="ny-hand-omrade" style="display:none; text-align:center; margin-top:16px;">
        <button onclick="ny_hand()" class="btn-starta">🔄 Ny hand</button>
    </div>

</div>

<script src="Js/Game.js"></script>
<script>
    const ROOM_ID = <?= $room_id ?>;
    const MY_USER = <?= $_SESSION['user_id'] ?>;
    const MY_NAME = "<?= htmlspecialchars($_SESSION['username']) ?>";
    startPolling(ROOM_ID);
</script>
</body>
</html>