<?php ob_start(); ?>

<?php
    $entries = is_array($entries ?? null) ? $entries : [];
    $totals = is_array($totals ?? null) ? $totals : ['debt' => 0, 'credit' => 0];
    $byPerson = is_array($byPerson ?? null) ? $byPerson : [];
    $filterEmail = isset($filterEmail) ? (string)$filterEmail : '';
    $editEntry = is_array($editEntry ?? null) ? $editEntry : null;
    $error = isset($error) ? (string)$error : '';

    $totalOutstanding = (float)($totals['credit'] ?? 0) - (float)($totals['debt'] ?? 0);
    $personMap = [];
    foreach ($entries as $entry) {
        $rawEmail = trim((string)($entry['person_email'] ?? ''));
        $displayEmail = $rawEmail !== '' ? $rawEmail : 'unknown';
        $key = strtolower($displayEmail);
        if (!isset($personMap[$key])) {
            $personMap[$key] = [
                'display' => $displayEmail,
                'entries' => [],
                'totals' => ['debt' => 0.0, 'credit' => 0.0]
            ];
        }
        $personMap[$key]['entries'][] = $entry;
        $type = strtolower((string)($entry['debt_type'] ?? ''));
        if ($type === 'debt' || $type === 'credit') {
            $personMap[$key]['totals'][$type] += (float)($entry['amount'] ?? 0);
        }
    }
?>

<div class="routina-wrap finance-page">
    <div class="routina-header">
        <div>
            <div class="routina-title">Debt &amp; Credit</div>
            <div class="routina-sub">Track amounts, dates, and people to stay on top of what’s outstanding.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/finance/debts?export=csv<?php echo $filterEmail !== '' ? '&person_email=' . urlencode($filterEmail) : ''; ?>">Export CSV</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance/debts?export=excel<?php echo $filterEmail !== '' ? '&person_email=' . urlencode($filterEmail) : ''; ?>">Export Excel</a>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="/finance/debts?export=print<?php echo $filterEmail !== '' ? '&person_email=' . urlencode($filterEmail) : ''; ?>">Export PDF</a>
            <a class="btn btn-outline-secondary btn-sm" href="/finance">Back</a>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card p-3">
                <div class="card-kicker"><?php echo $editEntry ? 'Edit entry' : 'Add entry'; ?></div>
                <form method="post" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?php echo $editEntry ? 'update' : 'create'; ?>" />
                    <?php if ($editEntry): ?>
                        <input type="hidden" name="id" value="<?php echo (int)($editEntry['id'] ?? 0); ?>" />
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="debt_type" required>
                            <option value="debt" <?php echo ($editEntry && ($editEntry['debt_type'] ?? '') === 'debt') ? 'selected' : ''; ?>>Debt (I owe)</option>
                            <option value="credit" <?php echo ($editEntry && ($editEntry['debt_type'] ?? '') === 'credit') ? 'selected' : ''; ?>>Credit (They owe)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00" value="<?php echo htmlspecialchars((string)($editEntry['amount'] ?? '')); ?>" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input class="form-control" type="date" name="date" value="<?php echo htmlspecialchars((string)($editEntry['entry_date'] ?? date('Y-m-d'))); ?>" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Person email</label>
                        <input class="form-control" type="email" name="person_email" placeholder="name@example.com" value="<?php echo htmlspecialchars((string)($editEntry['person_email'] ?? '')); ?>" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description (optional)</label>
                        <input class="form-control" type="text" name="description" placeholder="What was this for?" value="<?php echo htmlspecialchars((string)($editEntry['description'] ?? '')); ?>" />
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit"><?php echo $editEntry ? 'Update entry' : 'Save entry'; ?></button>
                        <?php if ($editEntry): ?>
                            <a class="btn btn-outline-secondary" href="/finance/debts">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card mt-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="card-kicker">Entries</div>
                        <?php if ($filterEmail !== ''): ?>
                            <div class="text-muted small">Filtered for: <?php echo htmlspecialchars($filterEmail); ?></div>
                        <?php endif; ?>
                    </div>
                    <form method="get" class="d-flex gap-2">
                        <input class="form-control form-control-sm" type="email" name="person_email" placeholder="Filter by email" value="<?php echo htmlspecialchars($filterEmail); ?>" />
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Filter</button>
                        <a class="btn btn-outline-secondary btn-sm" href="/finance/debts">Clear</a>
                    </form>
                </div>

                <div class="mt-3">
                    <?php if (empty($personMap)): ?>
                        <div class="text-muted">No entries yet.</div>
                    <?php else: ?>
                        <?php foreach ($personMap as $key => $person): ?>
                            <?php
                                $email = (string)($person['display'] ?? '');
                                $items = $person['entries'] ?? [];
                                $pd = (float)($person['totals']['debt'] ?? 0);
                                $pc = (float)($person['totals']['credit'] ?? 0);
                                $po = $pc - $pd;
                                $isOpen = ($filterEmail !== '' && strcasecmp($filterEmail, $email) === 0);
                                $count = count($items);
                                $lastDate = '';
                                foreach ($items as $it) {
                                    $d = (string)($it['entry_date'] ?? '');
                                    if ($d > $lastDate) {
                                        $lastDate = $d;
                                    }
                                }
                            ?>
                            <details class="mb-3 border rounded p-2" <?php echo $isOpen ? 'open' : ''; ?>>
                                <summary class="d-flex flex-wrap align-items-center justify-content-between gap-2" style="list-style: none; cursor: pointer;">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($email); ?></div>
                                    <div class="d-flex flex-wrap gap-3 small">
                                        <span>Debt: <?php echo number_format($pd, 2); ?></span>
                                        <span>Credit: <?php echo number_format($pc, 2); ?></span>
                                        <span>Outstanding: <?php echo number_format($po, 2); ?></span>
                                        <span>Entries: <?php echo (int)$count; ?></span>
                                        <?php if ($lastDate !== ''): ?>
                                            <span>Last: <?php echo htmlspecialchars($lastDate); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a class="btn btn-outline-secondary btn-sm" href="/finance/debts?person_email=<?php echo urlencode($email); ?>">Filter</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="/finance/debts?export=csv&person_email=<?php echo urlencode($email); ?>">CSV</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="/finance/debts?export=excel&person_email=<?php echo urlencode($email); ?>">Excel</a>
                                        <a class="btn btn-outline-secondary btn-sm" target="_blank" href="/finance/debts?export=print&person_email=<?php echo urlencode($email); ?>">PDF</a>
                                    </div>
                                </summary>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Person</th>
                                                <th>Description</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $e): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string)($e['entry_date'] ?? '')); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst((string)($e['debt_type'] ?? ''))); ?></td>
                                                    <td><?php echo htmlspecialchars((string)($e['person_email'] ?? '')); ?></td>
                                                    <td><?php echo htmlspecialchars((string)($e['description'] ?? '')); ?></td>
                                                    <td class="text-end"><?php echo number_format((float)($e['amount'] ?? 0), 2); ?></td>
                                                    <td class="text-end">
                                                        <a class="btn btn-outline-secondary btn-sm" href="/finance/debts?edit=<?php echo (int)($e['id'] ?? 0); ?>">Edit</a>
                                                        <form method="post" action="/finance/debts" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="delete" />
                                                            <input type="hidden" name="id" value="<?php echo (int)($e['id'] ?? 0); ?>" />
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this entry?')">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-3">
                <div class="card-kicker">Statement</div>
                <div class="d-flex justify-content-between mt-2">
                    <div class="text-muted">Total debt (I owe)</div>
                    <div><strong><?php echo number_format((float)($totals['debt'] ?? 0), 2); ?></strong></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div class="text-muted">Total credit (They owe)</div>
                    <div><strong><?php echo number_format((float)($totals['credit'] ?? 0), 2); ?></strong></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div class="text-muted">Outstanding</div>
                    <div><strong><?php echo number_format($totalOutstanding, 2); ?></strong></div>
                </div>
            </div>

            <div class="card p-3 mt-3">
                <div class="card-kicker">By person</div>
                <div class="text-muted small mb-2">Individual totals to share.</div>
                <?php if (empty($personMap)): ?>
                    <div class="text-muted">No entries yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Person</th>
                                    <th class="text-end">Debt</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personMap as $key => $person): ?>
                                    <?php
                                        $email = (string)($person['display'] ?? '');
                                        $d = (float)($person['totals']['debt'] ?? 0);
                                        $c = (float)($person['totals']['credit'] ?? 0);
                                        $o = $c - $d;
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="/finance/debts?person_email=<?php echo urlencode($email); ?>">
                                                <?php echo htmlspecialchars($email); ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?php echo number_format($d, 2); ?></td>
                                        <td class="text-end"><?php echo number_format($c, 2); ?></td>
                                        <td class="text-end"><?php echo number_format($o, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
