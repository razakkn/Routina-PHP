<?php ob_start(); ?>

<?php
    $currencySymbol = isset($currencySymbol) ? (string)$currencySymbol : '$';
    $currencySpacer = (preg_match('/^[A-Z]{3}$/', $currencySymbol) ? ' ' : '');
?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Assets</div>
           <div class="routina-sub">Track your valuable assets.</div>
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
            <div class="card-kicker">Add Asset</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" placeholder="Laptop, House, Stocks" required />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Type</label>
                        <input name="asset_type" class="form-control" placeholder="Property, Vehicle, Cash" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Value</label>
                        <input name="value" type="number" step="0.01" class="form-control" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <input name="notes" class="form-control" placeholder="Optional" />
                </div>
                <button class="btn btn-primary w-100">Add Asset</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Your Assets</div>
            <?php if (empty($assets)): ?>
                <div class="text-muted text-center py-4">No assets recorded.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['name']); ?></td>
                                    <td><?php echo htmlspecialchars($a['asset_type']); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($currencySymbol); ?><?php echo htmlspecialchars($currencySpacer); ?><?php echo number_format($a['value'], 2); ?></td>
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
