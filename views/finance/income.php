<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Income</div>
           <div class="routina-sub">Record income sources.</div>
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
            <div class="card-kicker">Add Income</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Source</label>
                    <input name="source" class="form-control" placeholder="Salary, Freelance" required />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Amount</label>
                        <input name="amount" type="number" step="0.01" class="form-control" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Date</label>
                        <input name="received_date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                    </div>
                </div>
                <button class="btn btn-primary w-100">Add Income</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Recent Income</div>
            <?php if (empty($income)): ?>
                <div class="text-muted text-center py-4">No income recorded.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($income as $i): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($i['source']); ?></td>
                                    <td><?php echo htmlspecialchars($i['received_date']); ?></td>
                                    <td class="text-end">$<?php echo number_format($i['amount'], 2); ?></td>
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
