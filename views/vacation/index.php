<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Vacation Planner</div>
           <div class="routina-sub">Plan your next adventure.</div>
       </div>
        <div>
            <a class="btn btn-outline-secondary btn-sm" href="/vacation/new">New Trip</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Plan Trip</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3 place-host">
                    <label class="form-label">Destination</label>
                    <input name="destination" class="form-control" placeholder="Paris, France" required data-place-autocomplete="true" autocomplete="off" />
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
                        <option value="Completed">✅ Completed</option>
                    </select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Budget (planned)</label>
                        <input name="budget" type="number" step="0.01" class="form-control" placeholder="0.00" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Trip notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Goals, reminders, or ideas..."></textarea>
                </div>
                <button class="btn btn-primary w-100">Add Trip</button>
            </form>
        </div>

        <?php foreach($vacations as $v): ?>
             <div class="card">
                <div class="card-kicker"><?php echo htmlspecialchars($v['status']); ?></div>
                <div class="card-title">
                    <a class="text-decoration-none" href="/vacation/trip?id=<?php echo $v['id']; ?>">
                        <?php echo htmlspecialchars($v['destination']); ?>
                    </a>
                </div>
                <div class="muted">
                     <?php echo date('M d', strtotime($v['start_date'])); ?> - 
                     <?php echo date('M d, Y', strtotime($v['end_date'])); ?>
                </div>
                <?php if (!empty($v['budget'])): ?>
                    <div class="muted">Budget: <?php echo number_format((float)$v['budget'], 2); ?></div>
                <?php endif; ?>
                <div class="card-buttons">
                    <a class="btn-soft" href="/vacation/edit?id=<?php echo $v['id']; ?>">Edit</a>
                    <a class="btn-soft" href="/vacation/trip?id=<?php echo $v['id']; ?>">Open</a>
                </div>
             </div>
        <?php endforeach; ?>
        
        <?php if (empty($vacations)): ?>
            <div class="card d-flex align-items-center justify-content-center text-muted p-5">
                 No trips planned.
             </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
