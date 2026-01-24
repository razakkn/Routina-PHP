<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Plans</div>
           <div class="routina-sub">Track custom build or maintenance plans.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/vehicle/dashboard">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Plan</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Vehicle</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">Select vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" placeholder="Custom exhaust upgrade" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="planned">Planned</option>
                        <option value="in-progress">In progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <button class="btn btn-primary w-100">Save Plan</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Plans</div>
            <?php if (empty($plans)): ?>
                <div class="text-muted text-center py-4">No plans yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush mt-3">
                    <?php foreach ($plans as $plan): ?>
                        <div class="list-group-item">
                            <div class="fw-semibold"><?php echo htmlspecialchars($plan['title']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($plan['status']); ?></div>
                            <div class="text-muted mt-1"><?php echo nl2br(htmlspecialchars($plan['notes'])); ?></div>
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
