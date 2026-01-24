<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Calendar</div>
           <div class="routina-sub">Upcoming events and schedule.</div>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Event</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" required />
                </div>
                 <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start</label>
                        <input name="start" type="datetime-local" class="form-control" required />
                    </div>
                     <div class="col-6">
                        <label class="form-label">End</label>
                        <input name="end" type="datetime-local" class="form-control" required />
                    </div>
                </div>
                 <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="event">Event</option>
                        <option value="meeting">Meeting</option>
                        <option value="reminder">Reminder</option>
                    </select>
                </div>
                <button class="btn btn-primary w-100">Add Event</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Upcoming</div>
            <ul class="recent-list">
                <?php foreach($events as $e): ?>
                    <li class="d-flex justify-content-between align-items-center p-2">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($e['title']); ?></div>
                            <small class="text-muted">
                                <?php echo date('M d H:i', strtotime($e['start_datetime'])); ?> - 
                                <?php echo date('H:i', strtotime($e['end_datetime'])); ?>
                            </small>
                        </div>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($e['type']); ?></span>
                    </li>
                <?php endforeach; ?>
                 <?php if (empty($events)): ?>
                    <li class="text-muted">No upcoming events.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
