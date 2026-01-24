<?php ob_start(); ?>

<?php
    $currencySymbol = isset($currencySymbol) ? (string)$currencySymbol : '$';
    $currencySpacer = (preg_match('/^[A-Z]{3}$/', $currencySymbol) ? ' ' : '');
?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Bills</div>
           <div class="routina-sub">Track upcoming bills and payments.</div>
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
            <div class="card-kicker">Add Bill</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Bill Name</label>
                    <input name="name" class="form-control" placeholder="Rent, Utilities" required />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Amount</label>
                        <input name="amount" type="number" step="0.01" class="form-control" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Due Date</label>
                        <input name="due_date" type="date" class="form-control" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <button class="btn btn-primary w-100">Add Bill</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Upcoming Bills</div>
            <?php if (empty($bills)): ?>
                <div class="text-muted text-center py-4">No bills yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bills as $b): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($b['name']); ?></td>
                                    <td><?php echo htmlspecialchars($b['due_date']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($b['status'])); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($currencySymbol); ?><?php echo htmlspecialchars($currencySpacer); ?><?php echo number_format($b['amount'], 2); ?></td>
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
