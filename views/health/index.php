<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Health Tracker</div>
           <div class="routina-sub">Monitor your vitals and habits.</div>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Log Today</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>" />
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Weight (kg)</label>
                        <input name="weight" type="number" step="0.1" class="form-control" />
                    </div>
                     <div class="col-6">
                        <label class="form-label">Steps</label>
                        <input name="steps" type="number" class="form-control" />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sleep (hrs)</label>
                        <input name="sleep" type="number" step="0.5" class="form-control" />
                    </div>
                     <div class="col-6">
                        <label class="form-label">Water (glasses)</label>
                        <input name="water" type="number" class="form-control" />
                    </div>
                </div>
                <button class="btn btn-primary w-100 mt-3">Save Log</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Recent Logs</div>
            <div class="table-responsive mt-3">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Weight</th>
                            <th>Steps</th>
                            <th>Sleep</th>
                            <th>Water</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d', strtotime($log['entry_date'])); ?></td>
                                <td><?php echo htmlspecialchars($log['weight']); ?></td>
                                <td><?php echo htmlspecialchars($log['steps']); ?></td>
                                <td><?php echo htmlspecialchars($log['sleep_hours']); ?></td>
                                <td><?php echo htmlspecialchars($log['water_glasses']); ?> 💧</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No health logs yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
