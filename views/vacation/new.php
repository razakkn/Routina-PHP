<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">New Trip</div>
           <div class="routina-sub">Start a fresh vacation plan.</div>
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
            <div class="mb-3">
                <label class="form-label">Destination</label>
                <input name="destination" class="form-control" placeholder="Paris, France" required />
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Start Date</label>
                    <input name="start_date" type="date" class="form-control" required />
                </div>
                 <div class="col-6">
                    <label class="form-label">End Date</label>
                    <input name="end_date" type="date" class="form-control" required />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="Idea">💡 Idea</option>
                    <option value="Planned">📅 Planned</option>
                    <option value="Booked">✈️ Booked</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Create Trip</button>
                <a class="btn btn-outline-secondary" href="/vacation">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
