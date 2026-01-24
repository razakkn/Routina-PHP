<?php ob_start(); ?>

<?php
    $currencySymbol = isset($currencySymbol) ? (string)$currencySymbol : '$';
    $currencySpacer = (preg_match('/^[A-Z]{3}$/', $currencySymbol) ? ' ' : '');
?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Savings Goals</div>
           <div class="routina-sub">Track savings targets and progress.</div>
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
            <div class="card-kicker">Add Goal</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Goal</label>
                    <input name="goal" class="form-control" placeholder="Emergency Fund" required />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Target Amount</label>
                        <input name="target_amount" type="number" step="0.01" class="form-control" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Current Amount</label>
                        <input name="current_amount" type="number" step="0.01" class="form-control" />
                    </div>
                </div>
                <button class="btn btn-primary w-100">Add Goal</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Goals</div>
            <?php if (empty($savings)): ?>
                <div class="text-muted text-center py-4">No savings goals yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Goal</th>
                                <th class="text-end">Current</th>
                                <th class="text-end">Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($savings as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['goal']); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($currencySymbol); ?><?php echo htmlspecialchars($currencySpacer); ?><?php echo number_format($s['current_amount'], 2); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($currencySymbol); ?><?php echo htmlspecialchars($currencySpacer); ?><?php echo number_format($s['target_amount'], 2); ?></td>
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
