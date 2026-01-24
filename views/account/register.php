<?php
// views/account/register.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create account - Routina</title>
    <link rel="stylesheet" href="/css/site.css" />
    <link rel="stylesheet" href="/css/auth.css" />
</head>
<body class="auth-body">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">Routina</div>
            <div class="auth-sub">Personal Diary</div>

            <div class="auth-prompt">This is your space.</div>
            <div class="auth-context">Create an account to start your quiet timeline.</div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mt-3 mb-0" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/register" class="mt-4">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Display name</label>
                    <input class="form-control form-control-lg auth-input" name="display_name" placeholder="Your name" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control form-control-lg auth-input" name="email" type="email" required autocomplete="username" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control form-control-lg auth-input" name="password" type="password" required autocomplete="new-password" />
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100 btn-pill">
                    Create account →
                </button>

                <div class="auth-foot mt-3">
                    <span class="text-muted">Already have an account?</span>
                    <a class="auth-link ms-1" href="/login">Sign in</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
