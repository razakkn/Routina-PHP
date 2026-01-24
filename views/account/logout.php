<?php
// views/account/logout.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Disconnected - Routina</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <link rel="stylesheet" href="/css/logout-3d.css" />
</head>
<body>
    <canvas id="canvas-3d"></canvas>

    <div id="ui-layer">
        <div class="drift-message">
            <h1>See You Soon</h1>
            <p>Roam the cosmos until you return.</p>
            
            <a href="/login" class="btn-reconnect">
                Establish Link
            </a>
        </div>

        <div class="status-log">
            SESSION_TERMINATED<br>
            UPLINK_OFFLINE<br>
            DRIFT_MODE_ACTIVE
        </div>
    </div>

    <script src="/js/logout-3d.js"></script>
</body>
</html>
