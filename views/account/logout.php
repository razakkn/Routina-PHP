<?php
// views/account/logout.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Disconnect - Routina</title>
    <link rel="stylesheet" href="/css/login-matrix.css" />
</head>
<body>

    <div id="matrix-container">
        <div id="matrix-bg"></div>
        <div id="terminal-window">
            <div class="matrix-text">
                <p>CONFIRM DISCONNECT</p>
                <p>Are you sure you want to terminate your session?</p>
            </div>
            <form action="/logout" method="post" class="matrix-form">
                <?= csrf_field() ?>
                <button type="submit" class="matrix-btn">Terminate Session</button>
            </form>
            <div class="back-link">
                <a href="/dashboard">Cancel</a>
            </div>
        </div>
    </div>

</body>
</html>
