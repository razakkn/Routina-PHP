<?php
// views/account/forgot_password.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot password - Routina</title>
    <link rel="stylesheet" href="/css/site.css" />
    <link rel="stylesheet" href="/css/auth.css" />
</head>
<body class="auth-body">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">Routina</div>
            <div class="auth-sub">Account Recovery</div>

            <div class="auth-prompt">Forgot your access code?</div>
            <div class="auth-context">Enter your email and we will send a reset link.</div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mt-3 mb-0" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mt-3 mb-0" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/forgot-password" class="mt-4">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control form-control-lg auth-input" name="email" type="email" required autocomplete="email" />
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100 btn-pill">
                    Send reset link
                </button>

                <div class="auth-foot mt-3">
                    <a class="auth-link" href="/login">Return to login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
