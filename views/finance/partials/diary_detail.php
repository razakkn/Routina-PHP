<?php
    $entry = $entry ?? null;
    if (!$entry) {
        echo '<div class="text-muted">Entry not found.</div>';
        return;
    }
?>
<div class="md-detail-header">
    <div class="md-detail-title">Diary Entry</div>
    <div class="md-detail-sub"><?php echo htmlspecialchars((string)($entry['entry_date'] ?? '')); ?></div>
</div>
<div class="md-detail-body">
    <div class="md-detail-text"><?php echo nl2br(htmlspecialchars((string)($entry['notes'] ?? ''))); ?></div>
</div>
