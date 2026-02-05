<?php ob_start(); ?>

<div class="account-deleted-page">
    <div class="account-deleted-card card">
        <div class="card-body">
            <div class="account-deleted-emoji">👋</div>

            <h1 class="account-deleted-title">
                Account Deleted
            </h1>

            <p class="account-deleted-lead">
                Your account and all associated data have been permanently removed from our servers.
            </p>

            <div class="account-deleted-box">
                <h6>What happens now:</h6>
                <ul>
                    <li>All your personal data has been erased</li>
                    <li>You have been logged out of all sessions</li>
                    <li>Your Routina ID is now available for others</li>
                </ul>
            </div>

            <p class="account-deleted-note">
                Thank you for using Routina. We're sorry to see you go.
            </p>

            <a href="/" class="btn btn-primary account-deleted-cta">
                Return to Home
            </a>
        </div>
    </div>

    <p class="account-deleted-footer">
        Changed your mind? You can always create a new account.
    </p>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
