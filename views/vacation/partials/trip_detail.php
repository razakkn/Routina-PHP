<?php
    $vacation = $vacation ?? null;
    if (!$vacation) {
        echo '<div class="text-muted">Trip not found.</div>';
        return;
    }
?>
<div class="md-detail-header">
    <div class="md-detail-title"><?php echo htmlspecialchars((string)($vacation['destination'] ?? 'Trip')); ?></div>
    <div class="md-detail-sub"><?php echo htmlspecialchars((string)($vacation['status'] ?? '')); ?></div>
</div>
<div class="md-detail-body">
    <div class="text-muted">
        <?php echo date('M d', strtotime((string)$vacation['start_date'])); ?> -
        <?php echo date('M d, Y', strtotime((string)$vacation['end_date'])); ?>
    </div>
    <?php if (!empty($vacation['budget'])): ?>
        <div class="mt-2">Budget: <?php echo number_format((float)$vacation['budget'], 2); ?></div>
    <?php endif; ?>
    <?php if (!empty($vacation['notes'])): ?>
        <div class="mt-3"><?php echo nl2br(htmlspecialchars((string)$vacation['notes'])); ?></div>
    <?php endif; ?>
    <div class="mt-3 d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary btn-sm" href="/vacation/edit?id=<?php echo (int)$vacation['id']; ?>">Edit</a>
        <a class="btn btn-primary btn-sm" href="/vacation/trip?id=<?php echo (int)$vacation['id']; ?>">Open</a>
    </div>
</div>
