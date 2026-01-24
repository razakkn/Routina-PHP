<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Vehicle Events</div>
           <div class="routina-sub">Log refuels, inspections, and more.</div>
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
            <div class="card-kicker">Add Event</div>
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
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Type</label>
                        <input name="event_type" class="form-control" placeholder="Fuel, Inspection" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Date</label>
                        <input name="event_date" type="date" class="form-control" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <button class="btn btn-primary w-100">Log Event</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Events</div>
            <?php if (empty($events)): ?>
                <div class="text-muted text-center py-4">No events yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <?php
                                            $vehicleLabel = '';
                                            foreach ($vehicles as $v) {
                                                if ($v['id'] == $event['vehicle_id']) {
                                                    $vehicleLabel = $v['year'] . ' ' . $v['make'] . ' ' . $v['model'];
                                                    break;
                                                }
                                            }
                                            echo htmlspecialchars($vehicleLabel);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
