<?php
// views/account/login.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Routina - Deep Space Access</title>
    <!-- Three.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <link rel="stylesheet" href="/css/login-3d.css" />
</head>
<body>

    <!-- 3D Canvas Background -->
    <canvas id="canvas-3d"></canvas>

    <!-- UI Overlay -->
    <div id="ui-layer">
        
        <!-- Initial Interaction Prompt -->
        <div id="intro-text">
            <h1>Routina</h1>
            <p>[ Click to Initialize ]</p>
        </div>

        <!-- The 3D Cube Container -->
        <div class="cube-wrapper" id="cube-wrapper">
            
            <!-- FRONT FACE: LOGIN -->
            <div class="cube-face face-front">
                <div class="corner tl"></div><div class="corner tr"></div>
                <div class="corner bl"></div><div class="corner br"></div>
                
                <div class="brand">ROUTINA ID</div>
                
                <?php if (isset($error)): ?>
                    <div style="color: #ff6b6b; font-size: 0.8rem; margin-bottom: 1rem; text-align: center; border: 1px solid #ff6b6b; padding: 5px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Identity</label>
                        <input type="email" name="email" class="form-input" placeholder="pilot@routina.com" value="demo@routina.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Code</label>
                        <input type="password" name="password" class="form-input" placeholder="••••" value="demo" required>
                    </div>
                    <button type="submit" class="btn-cyber">
                        Connect
                    </button>
                </form>

                <div class="switch-form" style="margin-top: 1rem;">
                    <a href="/forgot-password">Forgot access code?</a>
                </div>

                <div class="switch-form">
                    New Pilot? <a id="switch-signup">Register Commission</a>
                </div>
            </div>

            <!-- RIGHT FACE: SIGN UP -->
            <div class="cube-face face-right">
                <div class="corner tl"></div><div class="corner tr"></div>
                <div class="corner bl"></div><div class="corner br"></div>

                <div class="brand">NEW ID</div>

                <form action="/register" method="post"> <!-- Note: Route needs implementation or it will 404 -->
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <input type="text" name="display_name" class="form-input" placeholder="Captain Name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comms (Email)</label>
                        <input type="email" name="email" class="form-input" placeholder="email@routina.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Set Code</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-cyber">
                        Initialize
                    </button>
                </form>

                <div class="switch-form">
                    Has ID? <a id="switch-login">Return to Login</a>
                </div>
            </div>

            <!-- BACK/LEFT/TOP/BOTTOM FACES (Decoration for 3D effect) -->
            <div class="cube-face face-back"></div>
            <div class="cube-face face-left"></div>
            <div class="cube-face face-top"></div>
            <div class="cube-face face-bottom"></div>

        </div>
    </div>

    <script src="/js/login-3d.js"></script>
</body>
</html>
