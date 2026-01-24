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
                        <label class="form-label">Routina ID</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #666;">@</span>
                            <input type="text" name="routina_id" class="form-input" placeholder="yourname" required style="padding-left: 25px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Code</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-cyber">
                        Connect
                    </button>
                </form>

                <div class="divider-or">
                    <span>or</span>
                </div>

                <a href="/auth/google" class="btn-google">
                    <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign in with Google
                </a>

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

                <form action="/register" method="post" id="signup-form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Choose Routina ID</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #666;">@</span>
                            <input type="text" name="routina_id" id="signup-routina-id" class="form-input" placeholder="yourname" required pattern="[a-z][a-z0-9_]{2,19}" style="padding-left: 25px;">
                        </div>
                        <div id="routina-id-status" style="font-size: 0.75rem; margin-top: 4px;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Set Access Code</label>
                        <input type="password" name="password" id="signup-password" class="form-input" placeholder="••••••••" required>
                        <div class="password-strength" id="password-strength">
                            <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                            <div class="strength-text" id="strength-text">Enter a strong password</div>
                        </div>
                        <ul class="password-requirements" id="password-requirements">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-upper">One uppercase letter</li>
                            <li id="req-lower">One lowercase letter</li>
                            <li id="req-number">One number</li>
                            <li id="req-special">One special character</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn-cyber" id="signup-btn" disabled>
                        Initialize
                    </button>
                </form>

                <div class="divider-or">
                    <span>or</span>
                </div>

                <a href="/auth/google?action=register" class="btn-google">
                    <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign up with Google
                </a>

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
