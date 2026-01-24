<?php
// views/account/logged_out.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Logged out - Routina</title>
    <link rel="stylesheet" href="/css/logout-3d.css" />
</head>
<body>
    <div id="ui-layer">
        <div class="drift-message">
            <h1>See You Soon</h1>
            <p>Roam the cosmos until you return.</p>
            <a href="/login" class="btn-reconnect">Establish Link</a>
        </div>
        <div class="status-log">
            SESSION_TERMINATED<br>
            UPLINK_OFFLINE<br>
            DRIFT_MODE_ACTIVE
        </div>
    </div>
</body>
</html>
