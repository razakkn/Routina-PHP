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

    <link rel="stylesheet" href="/css/master-detail.css" />

    <div class="grid">
        <div class="card" style="grid-column: 1 / -1;">
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
    </div>

    <div class="md-shell" data-module="vacation">
        <div class="md-list card">
            <div class="md-list-header">
                <div>
                    <div class="card-kicker">Trips</div>
                    <div class="text-muted small">Select a trip to view details.</div>
                </div>
            </div>

            <?php if (empty($vacations)): ?>
                <div class="text-muted text-center py-4">No trips planned.</div>
            <?php else: ?>
                <div class="list-group list-group-flush mt-3 md-list-items">
                    <?php foreach($vacations as $v): ?>
                        <button type="button"
                                class="list-group-item list-group-item-action md-item"
                                data-detail-url="/vacation/detail?id=<?php echo (int)$v['id']; ?>">
                            <div class="d-flex justify-content-between">
                                <div class="fw-semibold"><?php echo htmlspecialchars($v['destination']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($v['status']); ?></div>
                            </div>
                            <div class="text-muted mt-1">
                                 <?php echo date('M d', strtotime($v['start_date'])); ?> -
                                 <?php echo date('M d, Y', strtotime($v['end_date'])); ?>
                            </div>
                            <?php if (!empty($v['budget'])): ?>
                                <div class="text-muted mt-1">Budget: <?php echo number_format((float)$v['budget'], 2); ?></div>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="md-detail card">
            <div class="md-detail-top">
                <button type="button" class="btn btn-outline-secondary btn-sm md-back">Back</button>
            </div>
            <div class="md-detail-content">
                <div class="text-muted">Select a trip to see details.</div>
            </div>
        </div>
    </div>

    <script src="/js/master-detail.js" defer></script>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
