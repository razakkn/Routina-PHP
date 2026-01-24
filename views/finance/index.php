<?php ob_start(); ?>

<?php
    $currencySymbol = isset($currencySymbol) ? (string)$currencySymbol : '$';
    $currencySpacer = (preg_match('/^[A-Z]{3}$/', $currencySymbol) ? ' ' : '');

    $baseCurrencyCode = isset($baseCurrencyCode) ? (string)$baseCurrencyCode : (isset($currencyCode) ? (string)$currencyCode : 'USD');
    $month = isset($month) ? (string)$month : date('Y-m');
    $totalsBase = is_array($totalsBase ?? null) ? $totalsBase : ['income' => 0, 'expense' => 0];
    $expenseByCurrency = is_array($expenseByCurrency ?? null) ? $expenseByCurrency : [];
    $incomeByCurrency = is_array($incomeByCurrency ?? null) ? $incomeByCurrency : [];
    $currencyOptions = is_array($currencyOptions ?? null) ? $currencyOptions : [];
    $vacations = is_array($vacations ?? null) ? $vacations : [];
    $vacationMap = [];
    foreach ($vacations as $v) {
        if (isset($v['id'])) {
            $vacationMap[(int)$v['id']] = (string)($v['destination'] ?? '');
        }
    }

    $fmtBase = function ($amount) use ($currencySymbol, $currencySpacer) {
        return htmlspecialchars($currencySymbol) . htmlspecialchars($currencySpacer) . number_format((float)$amount, 2);
    };
?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Finance Tracker</div>
           <div class="routina-sub">Track your income and expenses (base currency: <?php echo htmlspecialchars($baseCurrencyCode); ?>).</div>
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

    <div class="card mt-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <div class="card-kicker">Month</div>
                <div class="text-muted">View totals and currency breakdown for the selected month.</div>
            </div>
            <form method="get" class="d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label mb-1">Month</label>
                    <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>" />
                </div>
                <button class="btn btn-outline-secondary" type="submit">Apply</button>
            </form>
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
                    <label class="form-label">Related vacation (optional)</label>
                    <select name="vacation_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($vacations as $vac): ?>
                            <?php
                                $vid = (int)($vac['id'] ?? 0);
                                $label = trim((string)($vac['destination'] ?? ''));
                                $status = (string)($vac['status'] ?? '');
                                $suffix = $status !== '' ? (' • ' . $status) : '';
                                if ($status === 'Completed') {
                                    continue;
                                }
                            ?>
                            <option value="<?php echo $vid; ?>"><?php echo htmlspecialchars($label . $suffix); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Use this to track planned vacation expenses and actual spend.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Transaction currency</label>
                        <select name="currency" id="txCurrency" class="form-select" data-base-currency="<?php echo htmlspecialchars($baseCurrencyCode); ?>">
                            <?php if (!empty($currencyOptions)): ?>
                                <?php foreach ($currencyOptions as $code => $label): ?>
                                    <?php
                                        if (is_int($code)) {
                                            $code = (string)$label;
                                            $label = (string)$label;
                                        } else {
                                            $code = (string)$code;
                                            $label = (string)$label;
                                        }
                                        $opt = ($label !== '' && $label !== $code) ? ($code . ' — ' . $label) : $code;
                                    ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($code === $baseCurrencyCode) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?php echo htmlspecialchars($baseCurrencyCode); ?>" selected><?php echo htmlspecialchars($baseCurrencyCode); ?></option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">If you paid in a foreign currency while traveling, pick it here.</div>
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
            <div class="card-kicker">Month summary</div>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="p-3 border rounded">
                        <div class="text-muted">Total income (<?php echo htmlspecialchars($baseCurrencyCode); ?>)</div>
                        <div style="font-size: 22px; font-weight: 800;">+<?php echo $fmtBase((float)($totalsBase['income'] ?? 0)); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded">
                        <div class="text-muted">Total expense (<?php echo htmlspecialchars($baseCurrencyCode); ?>)</div>
                        <div style="font-size: 22px; font-weight: 800;">-<?php echo $fmtBase((float)($totalsBase['expense'] ?? 0)); ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="mt-3">
                        <div class="card-kicker">Expenses by currency (original)</div>
                        <?php if (empty($expenseByCurrency)): ?>
                            <div class="text-muted">No expenses for this month.</div>
                        <?php else: ?>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Currency</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expenseByCurrency as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)($row['currency'] ?? '')); ?></td>
                                                <td class="text-end"><?php echo htmlspecialchars((string)($row['currency'] ?? '')); ?> <?php echo number_format((float)($row['total'] ?? 0), 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mt-3">
                        <div class="card-kicker">Income by currency (original)</div>
                        <?php if (empty($incomeByCurrency)): ?>
                            <div class="text-muted">No income for this month.</div>
                        <?php else: ?>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Currency</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incomeByCurrency as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)($row['currency'] ?? '')); ?></td>
                                                <td class="text-end"><?php echo htmlspecialchars((string)($row['currency'] ?? '')); ?> <?php echo number_format((float)($row['total'] ?? 0), 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($vacationSummaries)): ?>
                <div class="mt-3">
                    <div class="card-kicker">Vacation budgets</div>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Trip</th>
                                    <th>Status</th>
                                    <th class="text-end">Planned</th>
                                    <th class="text-end">Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vacationSummaries as $vs): ?>
                                    <?php
                                        $budget = $vs['budget'];
                                        $actual = (float)$vs['actual'];
                                        $pct = ($budget !== null && $budget > 0) ? min(100, ($actual / $budget) * 100) : null;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vs['destination']); ?></td>
                                        <td><?php echo htmlspecialchars($vs['status']); ?></td>
                                        <td class="text-end">
                                            <?php echo $vs['budget'] !== null ? $fmtBase($vs['budget']) : '—'; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo $fmtBase($vs['actual']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4">
                                            <?php if ($budget !== null && $budget > 0): ?>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar <?php echo $actual > $budget ? 'bg-danger' : 'bg-success'; ?>" role="progressbar" style="width: <?php echo number_format($pct, 2); ?>%" aria-valuenow="<?php echo number_format($pct, 2); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <?php if ($actual > $budget): ?>
                                                    <div class="text-danger small mt-1">Over budget by <?php echo $fmtBase($actual - $budget); ?></div>
                                                <?php else: ?>
                                                    <div class="text-muted small mt-1"><?php echo $fmtBase(max(0, $budget - $actual)); ?> remaining</div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-muted small">Set a budget to track progress.</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="grid-column: span 3;">
            <div class="card-kicker">Transactions (<?php echo htmlspecialchars($month); ?>)</div>
            <?php if (empty($transactions)): ?>
                <div class="text-center py-4 text-muted">No transactions recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Vacation</th>
                                <th>Currency</th>
                                <th class="text-end">Original</th>
                                <th class="text-end">Base (<?php echo htmlspecialchars($baseCurrencyCode); ?>)</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $t): ?>
                                <?php
                                    $tType = (string)($t['type'] ?? '');
                                    $sign = ($tType === 'expense') ? '-' : '+';
                                    $origCcy = (string)($t['original_currency'] ?? '');
                                    $baseCcy = (string)($t['base_currency'] ?? $baseCurrencyCode);
                                    $origAmt = isset($t['original_amount']) && $t['original_amount'] !== null ? (float)$t['original_amount'] : (float)($t['amount'] ?? 0);
                                    $baseAmt = (float)($t['amount'] ?? 0);
                                    if ($origCcy === '') {
                                        $origCcy = ($baseCcy !== '' ? $baseCcy : $baseCurrencyCode);
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['date']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $t['type'] == 'income' ? 'success' : 'secondary'; ?> me-2">
                                            <?php echo htmlspecialchars(ucfirst($t['type'])); ?>
                                        </span>
                                        <?php echo htmlspecialchars($t['description']); ?>
                                    </td>
                                    <td>
                                        <?php
                                            $vId = isset($t['vacation_id']) ? (int)$t['vacation_id'] : 0;
                                            echo htmlspecialchars($vId && isset($vacationMap[$vId]) ? $vacationMap[$vId] : '—');
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($origCcy); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($sign); ?><?php echo htmlspecialchars($origCcy); ?> <?php echo number_format($origAmt, 2); ?></td>
                                    <td class="text-end <?php echo $t['type'] == 'income' ? 'text-success' : ''; ?>"><?php echo htmlspecialchars($sign); ?><?php echo $fmtBase($baseAmt); ?></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this transaction?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="transaction_id" value="<?php echo (int)($t['id'] ?? 0); ?>" />
                                            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>" />
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
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
