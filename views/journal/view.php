<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Journal Entry</div>
           <div class="routina-sub"><?php echo htmlspecialchars($entry['entry_date']); ?></div>
       </div>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-kicker">Mood: <?php echo htmlspecialchars($entry['mood']); ?></div>
        <div class="mt-3">
            <?php echo nl2br(htmlspecialchars($entry['content'])); ?>
        </div>
        <div class="mt-4">
            <a class="btn btn-outline-secondary" href="/journal/history">Back to history</a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
