<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Monthly Reflection</div>
           <div class="routina-sub">Note key lessons and milestones.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/finance">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Reflection</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Month</label>
                    <input name="month" type="month" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Summary</label>
                    <textarea name="summary" class="form-control" rows="4" required></textarea>
                </div>
                <button class="btn btn-primary w-100">Save Reflection</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Reflections</div>
            <?php if (empty($reflections)): ?>
                <div class="text-muted text-center py-4">No reflections yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush mt-3">
                    <?php foreach ($reflections as $r): ?>
                        <div class="list-group-item">
                            <div class="fw-semibold"><?php echo htmlspecialchars($r['month']); ?></div>
                            <div class="text-muted mt-1"><?php echo nl2br(htmlspecialchars($r['summary'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
