<?php ob_start(); ?>

<div class="text-center py-5">
    <h1 class="display-4">Welcome to Routina</h1>
    <p class="lead">Personal Timeline & Life Management</p>
    <hr class="my-4">
    <p>The application has been successfully ported to a PHP structure.</p>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/dashboard" class="btn btn-primary btn-lg">Go to Dashboard</a>
    <?php else: ?>
         <a href="/login" class="btn btn-primary btn-lg">Login to Routina</a>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
