<?php ob_start(); ?>

<div class="container mt-5 text-center">
    <div class="display-1 text-muted mb-4">🚧</div>
    <h1 class="h3">Feature Coming Soon</h1>
    <p class="lead text-muted">We are currently migrating this feature from the old system.</p>
    <div class="mt-4">
        <a href="/dashboard" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
