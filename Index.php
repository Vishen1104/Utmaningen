<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: Lobby.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Poker – Logga in</title>
    <link rel="stylesheet" href="css/Style.css">
</head>
<body>
    <div class="auth-container">
        <h1>🃏 Poker</h1>

        <div class="tabs">
            <button class="tab-btn active" onclick="visaFlik('login')">Logga in</button>
            <button class="tab-btn" onclick="visaFlik('register')">Registrera</button>
        </div>

        <div id="login" class="flik">
            <input type="text" id="login-username" placeholder="Användarnamn">
            <input type="password" id="login-password" placeholder="Lösenord">
            <button onclick="logga_in()">Logga in</button>
            <p id="login-meddelande" class="meddelande"></p>
        </div>


        <div id="register" class="flik" style="display:none;">
            <input type="text" id="reg-username" placeholder="Välj användarnamn">
            <input type="password" id="reg-password" placeholder="Välj lösenord">
            <button onclick="registrera()">Skapa konto</button>
            <p id="reg-meddelande" class="meddelande"></p>
        </div>
    </div>



    



