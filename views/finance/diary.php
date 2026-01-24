<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Finance Diary</div>
           <div class="routina-sub">Record financial thoughts and notes.</div>
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
            <div class="card-kicker">New Entry</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input name="entry_date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="4" required></textarea>
                </div>
                <button class="btn btn-primary w-100">Save Entry</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Entries</div>
            <?php if (empty($entries)): ?>
                <div class="text-muted text-center py-4">No entries yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush mt-3">
                    <?php foreach ($entries as $e): ?>
                        <div class="list-group-item">
                            <div class="fw-semibold"><?php echo htmlspecialchars($e['entry_date']); ?></div>
                            <div class="text-muted mt-1"><?php echo nl2br(htmlspecialchars($e['notes'])); ?></div>
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
