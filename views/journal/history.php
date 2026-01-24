<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Journal History</div>
           <div class="routina-sub">Browse your past entries.</div>
       </div>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-kicker">All Entries</div>
        <?php if (empty($entries)): ?>
            <div class="text-muted py-4 text-center">No journal entries yet.</div>
        <?php else: ?>
            <div class="list-group list-group-flush mt-3">
                <?php foreach($entries as $entry): ?>
                    <a class="list-group-item list-group-item-action" href="/journal/view?id=<?php echo $entry['id']; ?>">
                        <div class="d-flex justify-content-between">
                            <div class="fw-semibold"><?php echo htmlspecialchars($entry['mood']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($entry['entry_date']); ?></small>
                        </div>
                        <div class="text-muted mt-1">
                            <?php echo htmlspecialchars(mb_strimwidth($entry['content'], 0, 120, '...')); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
