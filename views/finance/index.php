<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Finance Tracker</div>
           <div class="routina-sub">Track your income and expenses.</div>
       </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/finance/assets">Assets</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/bills">Bills</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/budgets">Budgets</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/income">Income</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/savings">Savings</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/reflection">Reflection</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/diary">Diary</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">New Transaction</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input name="description" class="form-control" required placeholder="Groceries, Salary, etc." />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Amount</label>
                        <input name="amount" type="number" step="0.01" class="form-control" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input name="date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                </div>
                <button class="btn btn-primary w-100">Add Transaction</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Recent Activity</div>
            <?php if (empty($transactions)): ?>
                <div class="text-center py-4 text-muted">No transactions recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $t): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['date']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $t['type'] == 'income' ? 'success' : 'secondary'; ?> me-2">
                                            <?php echo htmlspecialchars(ucfirst($t['type'])); ?>
                                        </span>
                                        <?php echo htmlspecialchars($t['description']); ?>
                                    </td>
                                    <td class="text-end <?php echo $t['type'] == 'income' ? 'text-success' : ''; ?>">
                                        <?php echo $t['type'] == 'expense' ? '-' : '+'; ?>
                                        $<?php echo number_format($t['amount'], 2); ?>
                                    </td>
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
