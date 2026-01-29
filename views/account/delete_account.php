<?php ob_start(); ?>

<?php
    $viewUser = [];
    if (isset($user) && is_array($user)) {
        $viewUser = $user;
    } elseif (isset($Model) && is_array($Model) && isset($Model['user']) && is_array($Model['user'])) {
        $viewUser = $Model['user'];
    } elseif (isset($Model) && is_object($Model) && isset($Model->user) && is_array($Model->user)) {
        $viewUser = $Model->user;
    }

    $errorMsg = null;
    if (isset($error) && (is_string($error) || $error === null)) {
        $errorMsg = $error;
    } elseif (isset($Model) && is_array($Model) && array_key_exists('error', $Model)) {
        $errorMsg = is_string($Model['error']) ? $Model['error'] : null;
    } elseif (isset($Model) && is_object($Model) && isset($Model->error)) {
        $errorMsg = is_string($Model->error) ? $Model->error : null;
    }

    $displayName = $viewUser['display_name'] ?? $viewUser['routina_id'] ?? 'User';
?>

<div class="container" style="max-width: 600px; margin: 40px auto; padding: 0 20px;">
    <div class="card" style="border-radius: 20px; border: 2px solid #dc3545; background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);">
        <div class="card-body" style="padding: 32px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                <h2 style="color: #dc3545; margin: 0; font-weight: 700;">Delete Your Account</h2>
                <p class="text-muted" style="margin-top: 8px;">This action cannot be undone</p>
            </div>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger" style="border-radius: 12px;">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <div style="background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(220, 53, 69, 0.2);">
                <h5 style="color: #dc3545; margin: 0 0 12px 0;">What will be deleted:</h5>
                <ul style="margin: 0; padding-left: 20px; color: #666;">
                    <li>Your profile information and settings</li>
                    <li>All journal entries and memories</li>
                    <li>Financial records, budgets, and transactions</li>
                    <li>Vehicle records and maintenance history</li>
                    <li>Vacation plans and checklists</li>
                    <li>Health records and trackers</li>
                    <li>Calendar events and reminders</li>
                    <li>Family tree information</li>
                    <li>Home tasks and management data</li>
                    <li>All uploaded photos and documents</li>
                </ul>
            </div>

            <div style="background: #fff3cd; border-radius: 12px; padding: 16px; margin-bottom: 24px; border: 1px solid #ffc107;">
                <strong style="color: #856404;">⚡ Important:</strong>
                <span style="color: #856404;">This will permanently erase all your data from our servers. You will not be able to recover any information after deletion.</span>
            </div>

            <form method="post" action="/profile/delete">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" style="font-weight: 600;">To confirm, type <code style="background: #f8d7da; padding: 2px 6px; border-radius: 4px; color: #dc3545;">DELETE MY ACCOUNT</code></label>
                    <input type="text" 
                           name="confirm_text" 
                           class="form-control" 
                           placeholder="Type DELETE MY ACCOUNT"
                           autocomplete="off"
                           style="border-color: #dc3545;"
                           required />
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-weight: 600;">Enter your password to confirm</label>
                    <input type="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Your current password"
                           style="border-color: #dc3545;" />
                    <div class="form-text">If you signed up with Google only, leave this blank.</div>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="/profile" class="btn btn-outline-secondary" style="flex: 1; min-width: 120px;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-danger" style="flex: 1; min-width: 120px;">
                        🗑️ Delete My Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 14px;">
        <p>Need help? Contact us at support@routina.app</p>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
