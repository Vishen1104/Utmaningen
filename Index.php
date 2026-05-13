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
    <link rel="stylesheet" href="Css/Style.css">
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

    <script>
        function visaFlik(flik) {
            document.getElementById('login').style.display = flik === 'login' ? 'block' : 'none';
            document.getElementById('register').style.display = flik === 'register' ? 'block' : 'none';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
        }

        async function logga_in() {
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;

            const res = await fetch('php/auth.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            });
            const data = await res.json();

            if (data.success) {
                window.location.href = 'Lobby.php';
            } else {
                document.getElementById('login-meddelande').textContent = data.message;
            }
        }

        async function registrera() {
            const username = document.getElementById('reg-username').value;
            const password = document.getElementById('reg-password').value;

            const res = await fetch('php/auth.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=register&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            });
            const data = await res.json();

            document.getElementById('reg-meddelande').textContent = data.message;
            if (data.success) {
                setTimeout(() => visaFlik('login'), 1500);
            }
        }
    </script>
</body>
</html>