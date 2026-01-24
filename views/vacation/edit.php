<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Edit Trip</div>
           <div class="routina-sub">Update dates, status, and budget.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/vacation">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 720px;">
        <div class="card-kicker">Trip details</div>
        <form method="post" class="mt-3">
            <?= csrf_field() ?>
            <div class="mb-3 place-host">
                <label class="form-label">Destination</label>
                <input name="destination" class="form-control" value="<?php echo htmlspecialchars($vacation['destination']); ?>" required data-place-autocomplete="true" autocomplete="off" />
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Start Date</label>
                    <input name="start_date" type="date" class="form-control" value="<?php echo htmlspecialchars($vacation['start_date']); ?>" required />
                </div>
                 <div class="col-6">
                    <label class="form-label">End Date</label>
                    <input name="end_date" type="date" class="form-control" value="<?php echo htmlspecialchars($vacation['end_date']); ?>" required />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <?php $status = (string)($vacation['status'] ?? 'Planned'); ?>
                <select name="status" class="form-select">
                    <option value="Idea" <?php echo $status === 'Idea' ? 'selected' : ''; ?>>💡 Idea</option>
                    <option value="Planned" <?php echo $status === 'Planned' ? 'selected' : ''; ?>>📅 Planned</option>
                    <option value="Booked" <?php echo $status === 'Booked' ? 'selected' : ''; ?>>✈️ Booked</option>
                    <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>✅ Completed</option>
                </select>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Budget (planned)</label>
                    <input name="budget" type="number" step="0.01" class="form-control" value="<?php echo htmlspecialchars((string)($vacation['budget'] ?? '')); ?>" placeholder="0.00" />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Trip notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Goals, reminders, or ideas..."><?php echo htmlspecialchars((string)($vacation['notes'] ?? '')); ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save Changes</button>
                <a class="btn btn-outline-secondary" href="/vacation/trip?id=<?php echo (int)$vacation['id']; ?>">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>