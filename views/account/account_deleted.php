<?php ob_start(); ?>

<div class="container" style="max-width: 500px; margin: 80px auto; padding: 0 20px; text-align: center;">
    <div class="card" style="border-radius: 24px; border: none; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
        <div class="card-body" style="padding: 48px 32px;">
            <div style="font-size: 64px; margin-bottom: 20px;">👋</div>
            
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #212529; margin-bottom: 16px;">
                Account Deleted
            </h1>
            
            <p style="color: #6c757d; font-size: 1.1rem; line-height: 1.6; margin-bottom: 24px;">
                Your account and all associated data have been permanently removed from our servers.
            </p>
            
            <div style="background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 24px; text-align: left;">
                <h6 style="margin: 0 0 12px 0; color: #495057;">What happens now:</h6>
                <ul style="margin: 0; padding-left: 20px; color: #6c757d; font-size: 0.95rem;">
                    <li>All your personal data has been erased</li>
                    <li>You have been logged out of all sessions</li>
                    <li>Your Routina ID is now available for others</li>
                </ul>
            </div>

            <p style="color: #6c757d; margin-bottom: 24px;">
                Thank you for using Routina. We're sorry to see you go.
            </p>

            <a href="/" class="btn btn-primary" style="padding: 12px 32px; border-radius: 12px; font-weight: 600;">
                Return to Home
            </a>
        </div>
    </div>

    <p style="margin-top: 24px; color: #adb5bd; font-size: 0.875rem;">
        Changed your mind? You can always create a new account.
    </p>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
