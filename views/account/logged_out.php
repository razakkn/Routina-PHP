<?php
// views/account/logged_out.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Logged out - Routina</title>
    <link rel="stylesheet" href="/css/login-matrix.css" />
</head>
<body>

    <div id="matrix-container">
        <div id="matrix-bg"></div>
        <div id="terminal-window">
            <div class="matrix-text">
                <p>SESSION TERMINATED</p>
                <p>UPLINK_OFFLINE</p>
                <p>DRIFT_MODE_ACTIVE</p>
            </div>
            <div class="matrix-text">
                <p>See you soon.</p>
                <p>Roam the cosmos until you return.</p>
            </div>
            <a href="/login" class="matrix-btn">Establish Link</a>
        </div>
    </div>

</body>
</html>
